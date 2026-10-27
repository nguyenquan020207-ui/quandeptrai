<?php
function adminRun(mysqli $db,string $sql,string $types='',array $values=[]): mysqli_stmt {
    $stmt=$db->prepare($sql);
    if($types!==''){$args=[$types];foreach($values as $key=>&$value)$args[]=&$value;call_user_func_array([$stmt,'bind_param'],$args);}
    $stmt->execute();return $stmt;
}
function capNhatDonHang(mysqli $db,int $id,string $status): void {
    $allowed=['pending','confirmed','shipping','completed','cancelled'];if(!in_array($status,$allowed,true))return;
    $stmt=adminRun($db,'UPDATE orders SET status=? WHERE id=?','si',[$status,$id]);
}
function luuSanPham(mysqli $db,array $data): void {
    $id=(int)($data['id']??0);$sku=trim((string)($data['sku']??''));$name=trim((string)($data['name']??''));$brand=trim((string)($data['brand']??''));$category=(int)($data['category_id']??0);$list=(float)($data['list_price']??0);$sale=(float)($data['sale_price']??0);$description=trim((string)($data['description']??''));$specs=trim((string)($data['specs']??''));$image=trim((string)($data['image_url']??''));$stock=max(0,(int)($data['stock']??0));$active=isset($data['active'])?1:0;$featured=isset($data['featured'])?1:0;
    if($name===''||$category<1||$list<0||$sale<0||$sale>$list)throw new RuntimeException('Hãy kiểm tra tên, danh mục và giá sản phẩm.');
    if($brand!=='')adminRun($db,'INSERT IGNORE INTO brands(name) VALUES(?)','s',[$brand]);
    if($id){if($sku===''){$row=adminRun($db,'SELECT sku FROM products WHERE id=?','i',[$id])->get_result()->fetch_assoc();$sku=$row['sku']??'';}adminRun($db,'UPDATE products SET sku=?,name=?,brand=?,category_id=?,list_price=?,sale_price=?,description=?,specs=?,image_url=?,stock=?,active=?,featured=? WHERE id=?','sssiddsssiiii',[$sku,$name,$brand,$category,$list,$sale,$description,$specs,$image,$stock,$active,$featured,$id]);}
    else{$sku=$sku!==''?$sku:'ES'.date('ymdHis').bin2hex(random_bytes(2));adminRun($db,'INSERT INTO products(sku,name,brand,category_id,list_price,sale_price,description,specs,image_url,stock,active,featured) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)','sssiddsssiii',[$sku,$name,$brand,$category,$list,$sale,$description,$specs,$image,$stock,$active,$featured]);}
}
function luuDanhMuc(mysqli $db,array $data): void {
    $id=(int)($data['id']??0);$name=trim($data['name']??'');$slug=trim($data['slug']??'');$description=trim($data['description']??'');$icon=trim($data['icon']??'📦');$active=isset($data['active'])?1:0;
    if($name==='')throw new RuntimeException('Hãy nhập tên danh mục.');
    if($slug===''){$ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$name)?:$name;$slug=strtolower(trim(preg_replace('/[^a-z0-9]+/','-',$ascii),'-'));}
    if($id)adminRun($db,'UPDATE categories SET name=?,slug=?,description=?,icon=?,active=? WHERE id=?','ssssii',[$name,$slug,$description,$icon,$active,$id]);
    else adminRun($db,'INSERT INTO categories(name,slug,description,icon,active) VALUES(?,?,?,?,?)','ssssi',[$name,$slug,$description,$icon,$active]);
}
function adminThongKe(mysqli $db): array {
    return $db->query("SELECT (SELECT COUNT(*) FROM products) AS products,(SELECT COUNT(*) FROM products WHERE active=1) AS active_products,(SELECT COUNT(*) FROM orders) AS orders,(SELECT COUNT(*) FROM orders WHERE status='pending') AS pending_orders,(SELECT COUNT(*) FROM users WHERE role='customer') AS customers,(SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completed') AS revenue,(SELECT COUNT(*) FROM reviews) AS reviews,(SELECT COUNT(*) FROM contacts) AS contacts")->fetch_assoc()?:[];
}
function adminThongKeSanPham(mysqli $db): array {
    return $db->query('SELECT COUNT(*) AS total,COALESCE(SUM(active=1),0) AS active_count,COALESCE(SUM(active=0),0) AS hidden_count,COALESCE(AVG(sale_price),0) AS average_price,COALESCE(SUM(stock),0) AS stock_total,COALESCE(SUM(stock<=5),0) AS low_stock_count FROM products')->fetch_assoc()?:[];
}
function adminThongKeDonHang(mysqli $db): array {
    return $db->query("SELECT COUNT(*) AS total,COALESCE(SUM(status IN ('pending','confirmed','shipping')),0) AS open_count,COALESCE(AVG(total),0) AS average_total,COALESCE(SUM(total),0) AS total_value,COALESCE(SUM(status='pending'),0) AS pending_count,COALESCE(SUM(status='confirmed'),0) AS confirmed_count,COALESCE(SUM(status='shipping'),0) AS shipping_count,COALESCE(SUM(status='completed'),0) AS completed_count,COALESCE(SUM(status='cancelled'),0) AS cancelled_count FROM orders")->fetch_assoc()?:[];
}
function adminSanPham(mysqli $db,string $q='',int $category=0,string $visibility='all',int $limit=12,int $offset=0): array {
    $where=['1=1'];$types='';$values=[];if($q!==''){$where[]='(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)';$term='%'.$q.'%';$types.='sss';array_push($values,$term,$term,$term);}if($category>0){$where[]='p.category_id=?';$types.='i';$values[]=$category;}if($visibility==='active'||$visibility==='hidden'){$where[]='p.active=?';$types.='i';$values[]=$visibility==='active'?1:0;}
    $base=' FROM products p JOIN categories c ON c.id=p.category_id WHERE '.implode(' AND ',$where);$total=(int)adminRun($db,'SELECT COUNT(*)'.$base,$types,$values)->get_result()->fetch_row()[0];$types2=$types.'ii';$values2=$values;array_push($values2,$limit,$offset);$rows=adminRun($db,'SELECT p.*,c.name AS category_name'.$base.' ORDER BY p.created_at DESC,p.id DESC LIMIT ? OFFSET ?',$types2,$values2)->get_result()->fetch_all(MYSQLI_ASSOC);return ['rows'=>$rows,'total'=>$total];
}
function adminDanhMuc(mysqli $db,string $q='',int $limit=12,int $offset=0): array {
    $where=' WHERE 1=1';$types='';$values=[];if($q!==''){$where.=' AND (c.name LIKE ? OR c.slug LIKE ?)';$term='%'.$q.'%';$types='ss';$values=[$term,$term];}
    $total=(int)adminRun($db,'SELECT COUNT(*) FROM categories c'.$where,$types,$values)->get_result()->fetch_row()[0];$pageValues=$values;array_push($pageValues,$limit,$offset);
    $rows=adminRun($db,'SELECT c.*,(SELECT COUNT(*) FROM products p WHERE p.category_id=c.id) AS product_count FROM categories c'.$where.' ORDER BY c.name LIMIT ? OFFSET ?',$types.'ii',$pageValues)->get_result()->fetch_all(MYSQLI_ASSOC);return ['rows'=>$rows,'total'=>$total];
}
function adminDanhSachThuongHieu(mysqli $db,string $q='',int $limit=12,int $offset=0): array {
    $where=' WHERE 1=1';$types='';$values=[];if($q!==''){$where.=' AND b.name LIKE ?';$types='s';$values[]='%'.$q.'%';}
    $total=(int)adminRun($db,'SELECT COUNT(*) FROM brands b'.$where,$types,$values)->get_result()->fetch_row()[0];$pageValues=$values;array_push($pageValues,$limit,$offset);
    $rows=adminRun($db,'SELECT b.*,COUNT(p.id) AS product_count,COALESCE(SUM(p.active=1),0) AS active_products FROM brands b LEFT JOIN products p ON p.brand=b.name'.$where.' GROUP BY b.id ORDER BY b.name LIMIT ? OFFSET ?',$types.'ii',$pageValues)->get_result()->fetch_all(MYSQLI_ASSOC);return ['rows'=>$rows,'total'=>$total];
}
function luuThuongHieu(mysqli $db,array $data): void {
    $id=(int)($data['id']??0);$name=trim((string)($data['name']??''));$website=trim((string)($data['website']??''));$active=isset($data['active'])?1:0;if($name==='')throw new RuntimeException('Hãy nhập tên thương hiệu.');if(mb_strlen($name)>100)throw new RuntimeException('Tên thương hiệu tối đa 100 ký tự.');if($website!==''&&!filter_var($website,FILTER_VALIDATE_URL))throw new RuntimeException('Đường dẫn website không hợp lệ.');
    if($id){$oldRow=adminRun($db,'SELECT name FROM brands WHERE id=?','i',[$id])->get_result()->fetch_assoc();if(!$oldRow)throw new RuntimeException('Không tìm thấy thương hiệu.');$oldName=$oldRow['name'];adminRun($db,'UPDATE brands SET name=?,website=?,active=? WHERE id=?','ssii',[$name,$website,$active,$id]);if($oldName!==$name)adminRun($db,'UPDATE products SET brand=? WHERE brand=?','ss',[$name,$oldName]);}
    else adminRun($db,'INSERT INTO brands(name,website,active) VALUES(?,?,?)','ssi',[$name,$website,$active]);
}function adminKhachHangChiTiet(mysqli $db,int $id): ?array {
    $row=adminRun($db,"SELECT u.id,u.name,u.email,u.phone,u.birthday,u.created_at,u.updated_at,COUNT(o.id) AS order_count,COALESCE(SUM(o.total),0) AS order_total FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE u.id=? AND u.role='customer' GROUP BY u.id",'i',[$id])->get_result()->fetch_assoc();if(!$row)return null;
    $row['orders']=adminRun($db,'SELECT id,total,status,payment_method,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 50','i',[$id])->get_result()->fetch_all(MYSQLI_ASSOC);
    $row['addresses']=adminRun($db,'SELECT id,street,zip,city,country,is_default FROM user_addresses WHERE user_id=? ORDER BY is_default DESC,id DESC','i',[$id])->get_result()->fetch_all(MYSQLI_ASSOC);return $row;
}
function luuKhachHang(mysqli $db,array $data): void {
    $id=(int)($data['id']??0);$name=trim((string)($data['name']??''));$email=trim((string)($data['email']??''));$phone=trim((string)($data['phone']??''));$birthday=trim((string)($data['birthday']??''));$password=(string)($data['password']??'');if($name===''||mb_strlen($name)>120)throw new RuntimeException('Hãy nhập tên khách hàng hợp lệ.');if(!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>190)throw new RuntimeException('Hãy nhập địa chỉ email hợp lệ.');if(mb_strlen($phone)>30)throw new RuntimeException('Số điện thoại tối đa 30 ký tự.');if($birthday!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$birthday))throw new RuntimeException('Ngày sinh không hợp lệ.');$birthday=$birthday!==''?$birthday:null;
    if($id>0){if($password!==''){if(strlen($password)<8)throw new RuntimeException('Mật khẩu mới cần có ít nhất 8 ký tự.');$hash=password_hash($password,PASSWORD_DEFAULT);adminRun($db,"UPDATE users SET name=?,email=?,phone=?,birthday=?,password_hash=? WHERE id=? AND role='customer'",'sssssi',[$name,$email,$phone,$birthday,$hash,$id]);}else adminRun($db,"UPDATE users SET name=?,email=?,phone=?,birthday=? WHERE id=? AND role='customer'",'ssssi',[$name,$email,$phone,$birthday,$id]);}
    else{if(strlen($password)<8)throw new RuntimeException('Mật khẩu cần có ít nhất 8 ký tự.');$hash=password_hash($password,PASSWORD_DEFAULT);adminRun($db,"INSERT INTO users(name,email,phone,birthday,password_hash,role) VALUES(?,?,?,?,?,'customer')",'sssss',[$name,$email,$phone,$birthday,$hash]);}
}
function luuDiaChiKhachHang(mysqli $db,array $data): void {
    $userId=(int)($data['user_id']??0);$addressId=(int)($data['address_id']??0);$street=trim((string)($data['street']??''));$zip=trim((string)($data['zip']??''));$city=trim((string)($data['city']??''));$country=trim((string)($data['country']??'Việt Nam'));$isDefault=isset($data['is_default'])?1:0;if($userId<1||$street===''||$country==='')throw new RuntimeException('Hãy nhập địa chỉ và quốc gia.');if(!adminRun($db,"SELECT id FROM users WHERE id=? AND role='customer'",'i',[$userId])->get_result()->fetch_assoc())throw new RuntimeException('Không tìm thấy khách hàng.');if($isDefault)adminRun($db,'UPDATE user_addresses SET is_default=0 WHERE user_id=?','i',[$userId]);if($addressId)adminRun($db,'UPDATE user_addresses SET street=?,zip=?,city=?,country=?,is_default=? WHERE id=? AND user_id=?','ssssiii',[$street,$zip,$city,$country,$isDefault,$addressId,$userId]);else adminRun($db,'INSERT INTO user_addresses(user_id,street,zip,city,country,is_default) VALUES(?,?,?,?,?,?)','issssi',[$userId,$street,$zip,$city,$country,$isDefault]);
}function adminDonHangChiTiet(mysqli $db,int $id): ?array {
    $order=adminRun($db,'SELECT o.*,u.name AS customer_name,u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?','i',[$id])->get_result()->fetch_assoc();if(!$order)return null;
    $order['items']=adminRun($db,'SELECT oi.product_name,oi.quantity,oi.unit_price,oi.quantity*oi.unit_price AS line_total FROM order_items oi WHERE oi.order_id=? ORDER BY oi.id','i',[$id])->get_result()->fetch_all(MYSQLI_ASSOC);return $order;
}
function adminDanhSachDon(mysqli $db,string $q='',string $status='',int $limit=12,int $offset=0): array {
    $where=['1=1'];$types='';$values=[];if($q!==''){$where[]='(CAST(o.id AS CHAR) LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';$term='%'.$q.'%';$types.='sss';array_push($values,$term,$term,$term);}if(in_array($status,['pending','confirmed','shipping','completed','cancelled'],true)){$where[]='o.status=?';$types.='s';$values[]=$status;}$base=' FROM orders o JOIN users u ON u.id=o.user_id WHERE '.implode(' AND ',$where);$total=(int)adminRun($db,'SELECT COUNT(*)'.$base,$types,$values)->get_result()->fetch_row()[0];$values2=$values;array_push($values2,$limit,$offset);$rows=adminRun($db,'SELECT o.*,u.name AS customer_name,u.email'.$base.' ORDER BY o.id DESC LIMIT ? OFFSET ?',$types.'ii',$values2)->get_result()->fetch_all(MYSQLI_ASSOC);return ['rows'=>$rows,'total'=>$total];
}
function adminKhachHang(mysqli $db,string $q='',int $limit=12,int $offset=0): array {
    $term='%'.$q.'%';$where=" WHERE u.role='customer'";$types='';$values=[];if($q!==''){$where.=' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';$types='sss';$values=[$term,$term,$term];}$total=(int)adminRun($db,'SELECT COUNT(*) FROM users u'.$where,$types,$values)->get_result()->fetch_row()[0];$values2=$values;array_push($values2,$limit,$offset);$rows=adminRun($db,"SELECT u.id,u.name,u.email,u.phone,u.created_at,(SELECT a.country FROM user_addresses a WHERE a.user_id=u.id ORDER BY a.is_default DESC,a.id DESC LIMIT 1) AS country,COUNT(o.id) AS order_count,COALESCE(SUM(o.total),0) AS order_total FROM users u LEFT JOIN orders o ON o.user_id=u.id".$where.' GROUP BY u.id ORDER BY u.name,u.id LIMIT ? OFFSET ?',$types.'ii',$values2)->get_result()->fetch_all(MYSQLI_ASSOC);return ['rows'=>$rows,'total'=>$total];
}function danhSachDonAdmin(mysqli $db): array {return adminDanhSachDon($db,'','',8,0)['rows'];}

function adminAnalytics(mysqli $db,string $from,string $to,string $status='',int $category=0): array {
    $where=['o.created_at>=?','o.created_at<?'];$types='ss';$values=[$from,$to];
    if(in_array($status,['pending','confirmed','shipping','completed','cancelled'],true)){$where[]='o.status=?';$types.='s';$values[]=$status;}
    if($category>0){$where[]='EXISTS (SELECT 1 FROM order_items fi JOIN products fp ON fp.id=fi.product_id WHERE fi.order_id=o.id AND fp.category_id=?)';$types.='i';$values[]=$category;}
    $base=' FROM orders o WHERE '.implode(' AND ',$where);
    $summarySql="SELECT COUNT(*) AS order_count,COALESCE(SUM(o.status='cancelled'),0) AS cancelled_orders,COUNT(DISTINCT o.user_id) AS customer_count".$base;
    $summary=adminRun($db,$summarySql,$types,$values)->get_result()->fetch_assoc()?:[];
    $repeatSql='SELECT COUNT(*) AS customer_count,COALESCE(SUM(order_count>1),0) AS repeat_customers FROM (SELECT o.user_id,COUNT(*) AS order_count'.$base.' GROUP BY o.user_id) AS customer_orders';
    $repeat=adminRun($db,$repeatSql,$types,$values)->get_result()->fetch_assoc()?:[];
    $itemWhere=$where;$itemTypes=$types;$itemValues=$values;$itemJoin=' FROM order_items oi JOIN orders o ON o.id=oi.order_id';
    if($category>0){$itemJoin.=' JOIN products p ON p.id=oi.product_id';$itemWhere[]='p.category_id=?';$itemTypes.='i';$itemValues[]=$category;}
    $itemSql='SELECT COALESCE(SUM(oi.quantity),0)'.$itemJoin.' WHERE '.implode(' AND ',$itemWhere);
    $items=(int)adminRun($db,$itemSql,$itemTypes,$itemValues)->get_result()->fetch_row()[0];
    if($category>0){$revenueJoin=' FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id';$revenueWhere=$where;$revenueTypes=$types;$revenueValues=$values;$revenueWhere[]="o.status='completed'";$revenueWhere[]='p.category_id=?';$revenueTypes.='i';$revenueValues[]=$category;$revenueSql='SELECT COALESCE(SUM(oi.quantity*oi.unit_price),0)'.$revenueJoin.' WHERE '.implode(' AND ',$revenueWhere);}
    else{$revenueSql='SELECT COALESCE(SUM(o.total),0)'.$base." AND o.status='completed'";$revenueTypes=$types;$revenueValues=$values;}
    $revenue=(float)adminRun($db,$revenueSql,$revenueTypes,$revenueValues)->get_result()->fetch_row()[0];
    $customerCount=(int)($repeat['customer_count']??0);$orderCount=(int)($summary['order_count']??0);$cancelled=(int)($summary['cancelled_orders']??0);$repeatCount=(int)($repeat['repeat_customers']??0);
    $metrics=['repeat_rate'=>$customerCount?100*$repeatCount/$customerCount:0,'repeat_customers'=>$repeatCount,'customers'=>$customerCount,'avg_items'=>$orderCount?$items/$orderCount:0,'cancel_rate'=>$orderCount?100*$cancelled/$orderCount:0,'cancelled_orders'=>$cancelled,'orders'=>$orderCount,'revenue'=>$revenue,'revenue_per_customer'=>$customerCount?$revenue/$customerCount:0];
    $bucketRows=function(string $rangeFrom,string $rangeTo)use($db,$status,$category):array{
        $w=['o.created_at>=?','o.created_at<?'];$t='ss';$v=[$rangeFrom,$rangeTo];
        if(in_array($status,['pending','confirmed','shipping','completed','cancelled'],true)){$w[]='o.status=?';$t.='s';$v[]=$status;}
        if($category>0){$w[]='EXISTS (SELECT 1 FROM order_items fi JOIN products fp ON fp.id=fi.product_id WHERE fi.order_id=o.id AND fp.category_id=?)';$t.='i';$v[]=$category;}
        if($category>0){$revenueExpr="COALESCE(SUM(CASE WHEN o.status='completed' THEN (SELECT COALESCE(SUM(ci.quantity*ci.unit_price),0) FROM order_items ci JOIN products cp ON cp.id=ci.product_id WHERE ci.order_id=o.id AND cp.category_id=$category) ELSE 0 END),0)";}else{$revenueExpr="COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total ELSE 0 END),0)";}
        $sql="SELECT DATE_FORMAT(o.created_at,'%Y-%m') AS bucket,COUNT(*) AS order_count,".$revenueExpr." AS revenue FROM orders o WHERE ".implode(' AND ',$w)." GROUP BY DATE_FORMAT(o.created_at,'%Y-%m')";
        return adminRun($db,$sql,$t,$v)->get_result()->fetch_all(MYSQLI_ASSOC);
    };
    $previousFrom=(new DateTimeImmutable($from))->modify('-12 months')->format('Y-m-d H:i:s');$previousTo=(new DateTimeImmutable($to))->modify('-12 months')->format('Y-m-d H:i:s');$currentBuckets=[];foreach($bucketRows($from,$to)as $row)$currentBuckets[$row['bucket']]=$row;$previousBuckets=[];foreach($bucketRows($previousFrom,$previousTo)as $row)$previousBuckets[$row['bucket']]=$row;
    $chartCursor=(new DateTimeImmutable(substr($from,0,10)))->modify('first day of this month');$chartEnd=(new DateTimeImmutable(substr($to,0,10)))->modify('first day of this month')->modify('+1 month');$ordersSeries=[];
    for($cursor=$chartCursor;$cursor<$chartEnd;$cursor=$cursor->modify('+1 month')){$key=$cursor->format('Y-m');$previousKey=$cursor->modify('-12 months')->format('Y-m');$current=$currentBuckets[$key]??[];$previous=$previousBuckets[$previousKey]??[];$ordersSeries[]=['key'=>$key,'label'=>$cursor->format('m/Y'),'orders'=>(int)($current['order_count']??0),'previous_orders'=>(int)($previous['order_count']??0),'revenue'=>(float)($current['revenue']??0),'previous_revenue'=>(float)($previous['revenue']??0)];}
    $customerSql="SELECT DATE_FORMAT(created_at,'%Y-%m') AS bucket,COUNT(*) AS customer_count FROM users WHERE role='customer' AND created_at>=? AND created_at<? GROUP BY DATE_FORMAT(created_at,'%Y-%m')";
    $customerRows=adminRun($db,$customerSql,'ss',[$from,$to])->get_result()->fetch_all(MYSQLI_ASSOC);$customerMap=[];foreach($customerRows as $row)$customerMap[$row['bucket']]=(int)$row['customer_count'];$customerSeries=[];
    foreach($ordersSeries as $point)$customerSeries[]=['key'=>$point['key'],'label'=>$point['label'],'customers'=>$customerMap[$point['key']]??0];
    return ['metrics'=>$metrics,'orders_series'=>$ordersSeries,'customer_series'=>$customerSeries];
}
