<?php
require_once __DIR__.'/global.php';
foreach(['SanPhamModel','DanhMucModel','UserModel','DonHangModel','PhanHoiModel','EmailModel'] as $model) require_once __DIR__.'/model/'.$model.'.php';
$db=db();$shippingColumn=$db->query("SHOW COLUMNS FROM orders LIKE 'shipping_fee'");if($shippingColumn&&$shippingColumn->num_rows===0)$db->query('ALTER TABLE orders ADD shipping_fee DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER total');$page=(string)($_GET['page']??'index');
switch($page){
case 'index':
    $categories=layTatCaDanhMuc($db);$products=laySanPham($db,['limit'=>8]);$latestOrder=user()?layDonHangMoiNhat($db,(int)user()['id']):null;require __DIR__.'/view/index.php';break;
case 'danhmuc':
    go('index.php?page=sanpham');break;
case 'sanpham':
case 'cuahang':
    $category=null;$filter=['q'=>$_GET['q']??'','brand'=>trim((string)($_GET['brand']??'')),'sale'=>!empty($_GET['sale']),'sort'=>$_GET['sort']??'featured'];
    if(!empty($_GET['cat'])){$category=layDanhMucTheoSlug($db,(string)$_GET['cat']);if($category)$filter['category_id']=$category['id'];}
    $categories=layTatCaDanhMuc($db);$productPage=max(1,(int)($_GET['page_no']??1));$productsPerPage=8;$totalProducts=demSanPham($db,$filter);$productPages=max(1,(int)ceil($totalProducts/$productsPerPage));if($productPage>$productPages)$productPage=$productPages;$filter['limit']=$productsPerPage;$filter['offset']=($productPage-1)*$productsPerPage;$products=laySanPham($db,$filter);$showHighlights=$productPage===1&&$category===null&&empty($filter['q'])&&empty($filter['brand'])&&empty($filter['sale']);$featuredProductCount=$showHighlights?demSanPham($db,['featured_only'=>true]):0;$saleProductCount=$showHighlights?demSanPham($db,['sale'=>true]):0;$featuredProducts=$showHighlights?laySanPham($db,['featured_only'=>true,'limit'=>4]):[];$saleProducts=$showHighlights?laySanPham($db,['sale'=>true,'sort'=>'price_desc','limit'=>4]):[];require __DIR__.'/view/sanpham.php';break;
case 'chitiet':
    $product=laySanPhamTheoId($db,(int)($_GET['id']??0));if(!$product){http_response_code(404);$product=null;}
    $reviews=$product?layDanhGia($db,(int)$product['id']):[];$rating=$product?thongKeDanhGia($db,(int)$product['id']):['average'=>0,'count'=>0];require __DIR__.'/view/chitiet.php';break;
case 'dangnhap':
    if($_SERVER['REQUEST_METHOD']==='POST'){
        check_csrf();$jsonRequest=str_contains($_SERVER['HTTP_ACCEPT']??'','application/json');
        if(dangNhap($db,trim($_POST['email']??''),$_POST['password']??'')){
            $next=(string)($_POST['next']??'');
            if($next==='thanhtoan')$_SESSION['open_checkout_after_login']=true;
            $redirect=$next==='thanhtoan'?'index.php?checkout=1':'index.php';
            if($jsonRequest){header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'redirect'=>$redirect],JSON_UNESCAPED_UNICODE);exit;}
            flash('Đăng nhập thành công.');go($redirect);
        }
        $error='Email hoặc mật khẩu chưa đúng.';
        if($jsonRequest){http_response_code(401);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'message'=>$error],JSON_UNESCAPED_UNICODE);exit;}
    }
    require __DIR__.'/view/dangnhap.php';break;
case 'dangky':
    if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$password=$_POST['password']??'';$confirmPassword=$_POST['confirm_password']??'';
        if($password!==$confirmPassword){$error='Mật khẩu nhập lại không khớp.';}
        elseif($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8){$error='Hãy nhập tên, email hợp lệ và mật khẩu từ 8 ký tự.';}
        elseif(timNguoiDungTheoEmail($db,$email)){$error='Email này đã được đăng ký.';}
        else{$next=(string)($_POST['next']??'');taoNguoiDung($db,$name,$email,$password);flash('Tạo tài khoản thành công. Mời bạn đăng nhập.');go('index.php?page=dangnhap'.($next==='thanhtoan'?'&next=thanhtoan':''));}}
    require __DIR__.'/view/dangky.php';break;
case 'dangxuat':
    $_SESSION=[];session_regenerate_id(true);flash('Bạn đã đăng xuất.');go('index.php');break;
case 'them_gio':
    if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Phương thức không hợp lệ.');}check_csrf();themVaoGio((int)($_POST['product_id']??0),(int)($_POST['quantity']??1));if(str_contains($_SERVER['HTTP_ACCEPT']??'','application/json')){header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'cart_count'=>array_sum($_SESSION['cart']??[])],JSON_UNESCAPED_UNICODE);exit;}flash('Đã thêm sản phẩm vào giỏ hàng.');go('index.php?page=giohang');break;
case 'capnhat_don':
    check_csrf();if(isset($_POST['clear_cart'])){$_SESSION['cart']=[];$cartMessage='Đã xóa toàn bộ sản phẩm khỏi giỏ hàng.';}elseif(isset($_POST['remove_id'])){xoaKhoiGio((int)$_POST['remove_id']);$cartMessage='Đã xóa sản phẩm khỏi giỏ hàng.';}else{$cartMessage=capNhatGio($db,$_POST['quantity']??[])?:'Giỏ hàng đã được cập nhật.';}if(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest'){$items=layMatHangGio($db);$subtotal=array_sum(array_column($items,'line_total'));$shippingFee=tinhPhiVanChuyen($subtotal);$total=$subtotal+$shippingFee;$_GET['fragment']='1';require __DIR__.'/view/giohang.php';exit;}flash($cartMessage);go('index.php?page=giohang');break;
case 'giohang':
    $items=layMatHangGio($db);$subtotal=array_sum(array_column($items,'line_total'));$shippingFee=tinhPhiVanChuyen($subtotal);$total=$subtotal+$shippingFee;require __DIR__.'/view/giohang.php';break;
case 'thanhtoan':
    $checkoutFragment=!empty($_GET['fragment']);
    if(!user()){
        if($checkoutFragment){$checkoutGuest=true;require __DIR__.'/view/thanhtoan.php';break;}
        flash('Vui lòng đăng nhập hoặc đăng ký trước khi đặt hàng. Giỏ hàng của bạn vẫn được giữ lại.');go('index.php?page=dangnhap&next=thanhtoan');
    }
    $items=layMatHangGio($db);$subtotal=array_sum(array_column($items,'line_total'));$shippingFee=tinhPhiVanChuyen($subtotal);$total=$subtotal+$shippingFee;
    if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();try{$id=datHang($db,(int)user()['id'],(string)($_POST['payment']??'cod'),trim($_POST['address']??''),trim($_POST['phone']??''));$emailSent=guiEmailXacNhanDonHang($db,$id);$checkoutNotice=$emailSent?'Đặt hàng thành công. Email xác nhận đã được gửi.':'Đặt hàng thành công nhưng email xác nhận chưa gửi được. Vui lòng kiểm tra cấu hình SMTP.';if($checkoutFragment){$order=layDonHangTheoId($db,$id,(int)user()['id']);$lines=$order?layChiTietDonHang($db,(int)$order['id']):[];require __DIR__.'/view/thanhtoan.php';break;}flash($checkoutNotice);go('index.php?page=thanhtoan&id='.$id);}catch(Throwable $ex){$error=$ex->getMessage();}}
    if(isset($_GET['id'])){$order=layDonHangTheoId($db,(int)$_GET['id'],(int)user()['id']);$lines=$order?layChiTietDonHang($db,(int)$order['id']):[];require __DIR__.'/view/thanhtoan.php';break;}
    require __DIR__.'/view/thanhtoan.php';break;
case 'taikhoan':
    require_login();$userId=(int)user()['id'];$profile=layNguoiDungTheoId($db,$userId);$profileValues=['name'=>$profile['name']??user()['name'],'email'=>$profile['email']??user()['email'],'phone'=>$profile['phone']??'','birthday'=>$profile['birthday']??''];require __DIR__.'/view/taikhoan.php';break;
case 'donhang':
    require_login();$userId=(int)user()['id'];$orders=layDonHangNguoiDung($db,$userId);foreach($orders as &$accountOrder){$accountOrder['review_products']=$accountOrder['status']==='completed'?laySanPhamDanhGiaDonHang($db,(int)$accountOrder['id'],$userId):[];}unset($accountOrder);require __DIR__.'/view/donhang.php';break;
case 'hoso':
    require_login();$profile=layNguoiDungTheoId($db,(int)user()['id']);if(!$profile){$_SESSION=[];flash('Không tìm thấy tài khoản. Vui lòng đăng nhập lại.');go('index.php?page=dangnhap');}
    $profileValues=['name'=>$profile['name'],'email'=>$profile['email'],'phone'=>$profile['phone']??'','birthday'=>$profile['birthday']??''];
    if($_SERVER['REQUEST_METHOD']==='POST'){
        check_csrf();$profileValues=['name'=>trim((string)($_POST['name']??'')),'email'=>trim((string)($_POST['email']??'')),'phone'=>trim((string)($_POST['phone']??'')),'birthday'=>trim((string)($_POST['birthday']??''))];
        $currentPassword=(string)($_POST['current_password']??'');$newPassword=(string)($_POST['new_password']??'');$confirmPassword=(string)($_POST['confirm_password']??'');$newPasswordHash=null;
        if($profileValues['name']===''||mb_strlen($profileValues['name'])>120||!filter_var($profileValues['email'],FILTER_VALIDATE_EMAIL))$error='Vui lòng nhập họ tên và địa chỉ email hợp lệ.';
        elseif(mb_strlen($profileValues['phone'])>30)$error='Số điện thoại không được dài quá 30 ký tự.';
        elseif($profileValues['birthday']!==''&&(!DateTime::createFromFormat('!Y-m-d',$profileValues['birthday'])||$profileValues['birthday']>date('Y-m-d')))$error='Ngày sinh không hợp lệ.';
        elseif(emailDaDuocSuDung($db,$profileValues['email'],(int)$profile['id']))$error='Email này đã được một tài khoản khác sử dụng.';
        elseif($currentPassword!==''||$newPassword!==''||$confirmPassword!==''){
            if($currentPassword===''||!password_verify($currentPassword,$profile['password_hash']))$error='Mật khẩu hiện tại chưa đúng.';
            elseif(strlen($newPassword)<8)$error='Mật khẩu mới cần có ít nhất 8 ký tự.';
            elseif($newPassword!==$confirmPassword)$error='Mật khẩu mới nhập lại không khớp.';
            else $newPasswordHash=password_hash($newPassword,PASSWORD_DEFAULT);
        }
        if(empty($error)){
            try{
                $birthdayValue=$profileValues['birthday']!==''?$profileValues['birthday']:null;
                capNhatHoSoNguoiDung($db,(int)$profile['id'],$profileValues['name'],$profileValues['email'],$profileValues['phone'],$birthdayValue,$newPasswordHash);
                $_SESSION['user']['name']=$profileValues['name'];$_SESSION['user']['email']=$profileValues['email'];
                flash('Thông tin cá nhân đã được cập nhật.');go('index.php?page=taikhoan');
            }catch(mysqli_sql_exception $exception){$error='Không thể lưu thông tin. Hãy kiểm tra lại địa chỉ email.';}
        }
    }
    require __DIR__.'/view/hoso.php';break;
case 'huy_don':
    require_login();if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Phương thức không hợp lệ.');}check_csrf();
    $orderId=(int)($_POST['order_id']??0);
    if(huyDonHangNguoiDung($db,$orderId,(int)user()['id']))flash('Đơn hàng đã được hủy.');
    else flash('Không thể hủy đơn này. Chỉ đơn đang chờ xác nhận mới được hủy.');
    go('index.php?page=donhang');break;
case 'gioithieu':require __DIR__.'/view/gioithieu.php';break;
case 'lienhe':
    if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$message=trim($_POST['message']??'');if(strlen($message)<10)$error='Nội dung cần ít nhất 10 ký tự.';else{guiLienHe($db,$name,$email,$message);flash('Cảm ơn bạn đã liên hệ.');go('index.php?page=lienhe');}}
    require __DIR__.'/view/lienhe.php';break;
case 'gui_danhgia':
    require_login();if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Phương thức không hợp lệ.');}check_csrf();$productId=(int)($_POST['product_id']??0);$orderId=(int)($_POST['order_id']??0);$stars=(int)($_POST['stars']??5);$comment=trim((string)($_POST['comment']??''));
    if(guiDanhGiaDonHang($db,$orderId,(int)user()['id'],$productId,$stars,$comment))flash('Cảm ơn bạn đã đánh giá sản phẩm.');else flash('Chỉ có thể đánh giá sản phẩm trong đơn đã giao; hãy nhập nhận xét từ 1 đến 2.000 ký tự.');
    go('index.php?page=donhang#don-'.$orderId);break;
case 'admin':go('admin/index.php');
default:http_response_code(404);echo 'Không tìm thấy trang được yêu cầu.';
}
