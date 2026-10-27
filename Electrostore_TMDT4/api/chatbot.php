<?php
require_once __DIR__.'/../global.php';
require_once __DIR__.'/../model/SanPhamModel.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function electroChatbotAiReply(string $message, array $history): ?string {
    $settings=require __DIR__.'/../config/ai.php';
    $apiKey=trim((string)($settings['api_key']??''));
    $model=trim((string)($settings['model']??'gpt-5-mini'));
    if($apiKey===''||!function_exists('curl_init'))return null;

    try {
        $db=db();
        $rows=$db->query("SELECT p.id,p.name,p.brand,p.sale_price,p.list_price,p.stock,p.description,p.specs,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.featured DESC,(p.stock>0) DESC,p.id DESC LIMIT 24")->fetch_all(MYSQLI_ASSOC);
        $catalog=array_map(static fn(array $p): array=>[
            'id'=>(int)$p['id'],'name'=>(string)$p['name'],'brand'=>(string)$p['brand'],
            'category'=>(string)$p['category_name'],'price'=>(int)$p['sale_price'],
            'list_price'=>(int)$p['list_price'],'stock'=>(int)$p['stock'],
            'description'=>mb_substr(trim((string)$p['description']),0,180,'UTF-8'),
            'specs'=>mb_substr(trim((string)$p['specs']),0,180,'UTF-8'),
        ],$rows);
        $categories=array_column($db->query('SELECT name FROM categories WHERE active=1 ORDER BY name')->fetch_all(MYSQLI_ASSOC),'name');
        $instructions="Bạn là trợ lý tư vấn Electro Store, nói tiếng Việt tự nhiên, thân thiện như nhân viên cửa hàng. Trả lời ngắn gọn, hỏi thêm nhu cầu/ngân sách khi cần. Chỉ khẳng định giá, tồn kho, cấu hình và chính sách khi có dữ liệu bên dưới; không bịa thông tin. Giá là VND. Phí giao hàng 100.000đ, miễn phí khi tiền hàng từ 5.000.000đ. Hotline 1900 6868. Có thể hướng dẫn khách xem đơn ở mục Đơn hàng; không tự nhận đã sửa/hủy đơn. Nếu dữ liệu không có, nói rõ chưa tra được và đề nghị khách gửi thêm thông tin hoặc gọi hotline. Nếu khách hỏi chuyện không liên quan đến sản phẩm, đơn hàng, mua sắm hoặc dịch vụ Electro Store, không trả lời lan man ngoài chủ đề; hãy nhẹ nhàng hỏi: 'Mình là trợ lý Electro Store. Bạn cần mình hỗ trợ gì về sản phẩm, đơn hàng hay dịch vụ của cửa hàng?'\n\nDANH MỤC ĐANG HOẠT ĐỘNG: ".json_encode($categories,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\nDỮ LIỆU SẢN PHẨM ĐANG KINH DOANH (nguồn sự thật): ".json_encode($catalog,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $input=[];
        foreach(array_slice($history,-8) as $turn){
            if(!is_array($turn))continue;
            $role=(string)($turn['role']??'');$content=trim((string)($turn['content']??''));
            if(!in_array($role,['user','assistant'],true)||$content===''||mb_strlen($content,'UTF-8')>500)continue;
            $input[]=['role'=>$role,'content'=>$content];
        }
        $last=$input?end($input):null;
        if(!$last||$last['role']!=='user'||$last['content']!==$message)$input[]=['role'=>'user','content'=>$message];

        $requestBody=json_encode(['model'=>$model,'instructions'=>$instructions,'input'=>$input,'max_output_tokens'=>420,'store'=>false],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if($requestBody===false)return null;
        $curl=curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($curl,[
            CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>25,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$apiKey,'Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>$requestBody,
        ]);
        $raw=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);$curlError=curl_error($curl);curl_close($curl);
        if(!is_string($raw)||$status<200||$status>=300){error_log('Electro chatbot AI request failed; HTTP '.$status.($curlError!==''?' '.$curlError:''));return null;}
        $result=json_decode($raw,true);$answer=trim((string)($result['output_text']??''));
        if($answer==='')foreach(($result['output']??[]) as $item)foreach(($item['content']??[]) as $part){if(($part['type']??'')==='output_text')$answer.=($answer!==""?"\n":"").(string)($part['text']??'');}
        return trim($answer)!==''?trim($answer):null;
    } catch(Throwable $exception) {
        error_log('Electro chatbot AI unavailable: '.$exception->getMessage());
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Phương thức không hợp lệ.'],JSON_UNESCAPED_UNICODE); exit; }
$payload=json_decode(file_get_contents('php://input'),true)?:[];
$message=trim((string)($payload['message']??''));
if ($message==='' || mb_strlen($message)>500) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Vui lòng nhập câu hỏi tối đa 500 ký tự.'],JSON_UNESCAPED_UNICODE); exit; }
$text=mb_strtolower($message,'UTF-8');
$state=$_SESSION['electro_chatbot']??['last_ids'=>[],'last_category'=>0,'last_min'=>0,'last_max'=>0,'last_topic'=>''];
$reply=''; $products=[]; $topic='';
$findProducts=static function(array $terms,?int $maxPrice=null,?int $minPrice=null,array $exclude=[]): array {
    $db=db();
    if (!$terms) return [];
    $terms=array_slice(array_values(array_unique($terms)),0,6);
    $score=[]; $where=[]; $types=''; $values=[];
    foreach($terms as $term){$like='%'.$term.'%';$score[]='(CASE WHEN p.name LIKE ? THEN 6 WHEN p.brand LIKE ? THEN 5 WHEN c.name LIKE ? THEN 4 WHEN p.specs LIKE ? THEN 3 WHEN p.description LIKE ? THEN 1 ELSE 0 END)';array_push($values,$like,$like,$like,$like,$like);$types.='sssss';$where[]='(p.name LIKE ? OR p.brand LIKE ? OR c.name LIKE ? OR p.specs LIKE ? OR p.description LIKE ?)';array_push($values,$like,$like,$like,$like,$like);$types.='sssss';}
    $sql='SELECT p.id,p.name,p.brand,p.description,p.specs,p.image_url,p.sale_price,p.list_price,p.stock,p.category_id,c.name AS category_name,('.implode(' + ',$score).') AS relevance FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 AND ('.implode(' OR ',$where).')';
    if($maxPrice!==null){$sql.=' AND p.sale_price<=?';$types.='i';$values[]=$maxPrice;}
    if($minPrice!==null){$sql.=' AND p.sale_price>=?';$types.='i';$values[]=$minPrice;}
    if($exclude){$exclude=array_slice(array_map('intval',$exclude),0,8);$sql.=' AND p.id NOT IN ('.implode(',',array_fill(0,count($exclude),'?')).')';$types.=str_repeat('i',count($exclude));array_push($values,...$exclude);}
    $sql.=' ORDER BY relevance DESC,p.featured DESC,(p.stock>0) DESC,p.id DESC LIMIT 3';
    $stmt=$db->prepare($sql);$stmt->bind_param($types,...$values);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
};
$loadIds=static function(array $ids): array {if(!$ids)return []; $ids=array_slice(array_map('intval',$ids),0,3);$marks=implode(',',array_fill(0,count($ids),'?'));$types=str_repeat('i',count($ids));$stmt=db()->prepare('SELECT p.id,p.name,p.brand,p.description,p.specs,p.image_url,p.sale_price,p.list_price,p.stock,p.category_id,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 AND p.id IN ('.$marks.') ORDER BY FIELD(p.id,'.$marks.')');$all=array_merge($ids,$ids);$stmt->bind_param($types.$types,...$all);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);};
$greeting=(bool)preg_match('/^(xin chào|chào|hello|hi|shop ơi|ê shop|alo)[.!?\s]*$/u',$text);
if($greeting){$reply='Chào bạn 😊 Mình là trợ lý của Electro Store. Bạn đang tìm thiết bị nào, hoặc muốn mình gợi ý theo ngân sách và nhu cầu?';}
elseif(preg_match('/^(cảm ơn|cám ơn|thanks|thank you)[.!?\s]*$/u',$text)){$reply='Không có gì nha 😊 Cần tìm thêm món nào cứ nói mình biết.';}
elseif(preg_match('/phí ship|phí giao|vận chuyển|giao hàng mất|miễn phí giao/u',$text)){$reply='Phí giao hàng là 100.000₫. Đơn có tiền hàng từ 5.000.000₫ sẽ được miễn phí vận chuyển nhé.';}
elseif(preg_match('/bảo hành|đổi trả|đổi hàng|trả hàng/u',$text)){$reply='Mỗi sản phẩm có chính sách bảo hành theo hãng và thời hạn riêng. Bạn gửi tên hoặc mã sản phẩm, mình tra thông tin đang có; nếu cần xử lý bảo hành/đổi trả, hotline là 1900 6868.';}
elseif(preg_match('/hủy đơn|hủy hàng|huy don/u',$text)){$reply='Nếu đơn còn ở trạng thái “Chờ xác nhận”, bạn có thể vào mục Đơn hàng và chọn “Hủy đơn hàng”. Đơn đã được xác nhận thì bạn gọi hotline 1900 6868 để được hỗ trợ nhé.';}
elseif(preg_match('/đơn hàng|theo dõi đơn|trạng thái đơn|đơn của tôi/u',$text)){$reply=user()?'Bạn vào mục “Đơn hàng” trên thanh đầu trang để xem trạng thái từng đơn. Nếu gửi mã đơn ở đây, mình có thể hướng dẫn bạn tìm đúng đơn.':'Bạn đăng nhập trước, rồi chọn “Đơn hàng” trên thanh đầu trang để xem trạng thái đơn đã đặt nhé.';}
elseif(preg_match('/hotline|liên hệ|gọi shop|số điện thoại/u',$text)){$reply='Bạn có thể gọi Electro Store qua hotline 1900 6868. Mình cũng có thể hỗ trợ tìm sản phẩm ngay tại đây.';}
else{
    $budget=null;
    if(preg_match('/(?:dưới|tối đa|không quá|tầm|khoảng|budget)\s*(\d{1,3})(?:[.,](\d))?\s*(triệu|tr|trieu|m)\b/u',$text,$m)){$budget=(int)$m[1]*1000000+(isset($m[2])?(int)$m[2]*100000:0);}
    elseif(preg_match('/(\d{1,3})(?:[.,](\d))?\s*(triệu|tr|trieu)\b/u',$text,$m)){$budget=(int)$m[1]*1000000+(isset($m[2])?(int)$m[2]*100000:0);}
    $isCheaper=(bool)preg_match('/rẻ hơn|giá thấp hơn|mềm hơn/u',$text);
    $isDifferent=(bool)preg_match('/mẫu khác|loại khác|cái khác|còn mẫu nào|gợi ý thêm|thêm lựa chọn/u',$text);
    $isFollowup=(bool)preg_match('/còn hàng|còn không|chi tiết hơn|nói thêm|mẫu này|cái này|rẻ hơn|giá thấp hơn|mềm hơn|mẫu khác|loại khác|cái khác|còn mẫu nào|gợi ý thêm|thêm lựa chọn/u',$text);
    if($isFollowup&&!empty($state['last_ids'])){
        if($isCheaper&& !empty($state['last_min'])){$topic='cheaper';$products=$findProducts([],null,null);}
        $ids=array_map('intval',$state['last_ids']);
        if($isCheaper){$cap=(int)($state['last_min']??0);$cat=(int)($state['last_category']??0);if($cap>0&&$cat>0){$stmt=db()->prepare('SELECT p.id,p.name,p.brand,p.description,p.specs,p.image_url,p.sale_price,p.list_price,p.stock,p.category_id,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 AND p.category_id=? AND p.sale_price<? ORDER BY p.sale_price DESC,p.stock DESC LIMIT 3');$stmt->bind_param('ii',$cat,$cap);$stmt->execute();$products=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$reply=$products?'Mình lọc thử vài lựa chọn cùng nhóm có giá thấp hơn sản phẩm vừa xem nhé:':'Mình chưa thấy mẫu cùng nhóm rẻ hơn trong dữ liệu hiện có. Bạn cho mình ngân sách tối đa, mình tìm rộng hơn cho nhé.';}else{$products=[];$reply='Bạn muốn tìm nhóm sản phẩm nào rẻ hơn và ngân sách khoảng bao nhiêu?';}}
        elseif($isDifferent){$cat=(int)($state['last_category']??0);if($cat>0){$stmt=db()->prepare('SELECT p.id,p.name,p.brand,p.description,p.specs,p.image_url,p.sale_price,p.list_price,p.stock,p.category_id,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 AND p.category_id=? AND p.id NOT IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY p.featured DESC,(p.stock>0) DESC,p.id DESC LIMIT 3');$types='i'.str_repeat('i',count($ids));$vals=array_merge([$cat],$ids);$stmt->bind_param($types,...$vals);$stmt->execute();$products=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$reply=$products?'Được chứ, đây là vài lựa chọn khác cùng nhóm để bạn so sánh:':'Mình đã hiện các mẫu phù hợp nhất đang có. Bạn muốn đổi ngân sách hay chuyển sang nhóm sản phẩm khác?';}else{$products=$loadIds($ids);$reply='Đây là những sản phẩm mình vừa gợi ý. Bạn muốn mình tìm mẫu rẻ hơn hay xem kỹ cấu hình nào?';}}
        else{$products=$loadIds($ids);$reply= preg_match('/còn hàng|còn không/u',$text)?'Mình kiểm tra tồn kho của các mẫu vừa nhắc đây nhé:': 'Mình gửi lại thông tin các mẫu vừa nói để bạn tiện xem. Bạn đang quan tâm mẫu nào nhất?';}
    } else {
        $clean=$text;
        $clean=preg_replace('/\b(cho tôi biết|cho mình biết|giúp tôi|giúp mình|tư vấn|gợi ý|tìm|xem|giá|bao nhiêu|thông tin|chi tiết|sản phẩm|của shop|shop có|có bán|không|là gì|mua|muốn|mình|tôi|em|anh|chị|nhé|nha|với|cho|được|tốt|phù hợp|để|dùng|học|làm việc|chơi game|giải trí|pin|ngân sách|dưới|tối đa|khoảng|tầm|triệu|trieu|tr|m|đồng|vnd|vnđ|hơn|tầm giá)\b/ui',' ',$clean);
        $clean=trim(preg_replace('/[^\p{L}\p{N}]+/u',' ',$clean));$stop=['cần','đang','có','cái','loại','nào','này','kia','một','vài','thì','sao','và','hay','hoặc','của','mua','muốn','giúp','shop','ơi','nhỉ','là','rẻ','cao','thấp','hàng','còn','màu','đen','trắng','tốt','không','xin','chào','tư','vấn','cho','mình','tôi'];$terms=[];foreach(preg_split('/\s+/u',$clean)?:[] as $word){if(mb_strlen($word,'UTF-8')>=2&&!in_array($word,$stop,true)&&!preg_match('/^\d+$/',$word))$terms[]=$word;}
        if($terms){$products=$findProducts($terms,$budget);$topic=implode(' ',$terms);if($products){$reply=$budget?'Mình hiểu bạn đang tìm '.implode(' ',$terms).' với ngân sách khoảng '.number_format($budget,0,',','.').'₫. Mình lọc được vài mẫu phù hợp đây, bạn thích ưu tiên giá, cấu hình hay thương hiệu?':'Mình tìm được vài mẫu '.implode(' ',$terms).' để bạn tham khảo. Bạn muốn mình so sánh giá, cấu hình hay tình trạng còn hàng?';}else{$reply='Mình là trợ lý Electro Store nên hỗ trợ về sản phẩm, đơn hàng và dịch vụ cửa hàng. Mình chưa tìm thấy nội dung bạn hỏi trong dữ liệu hiện có. Bạn cần mình hỗ trợ gì về cửa hàng?';}}
        else{$reply='Bạn đang tìm sản phẩm nào vậy? Cho mình biết loại thiết bị, ngân sách dự kiến và nhu cầu chính (học tập, văn phòng hay chơi game), mình sẽ gợi ý sát hơn nhé.';}
    }
}
$aiReply=electroChatbotAiReply($message,is_array($payload['history']??null)?$payload['history']:[]);
if($aiReply!==null)$reply=$aiReply;
if($products){$state['last_ids']=array_map('intval',array_column($products,'id'));$state['last_category']=(int)($products[0]['category_id']??$state['last_category']??0);$prices=array_map('intval',array_column($products,'sale_price'));$state['last_min']=min($prices);$state['last_max']=max($prices);$state['last_topic']=$topic?:($state['last_topic']??'');}
$_SESSION['electro_chatbot']=$state;
echo json_encode(['ok'=>true,'reply'=>$reply,'products'=>$products],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
