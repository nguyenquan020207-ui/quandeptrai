<?php
function tinhPhiVanChuyen(float|int $subtotal): float { return $subtotal>=5000000?0.0:100000.0; }
function themVaoGio(int $productId,int $quantity=1): void {
    if($productId>0)$_SESSION['cart'][$productId]=min(99,max(1,(int)($_SESSION['cart'][$productId]??0)+$quantity));
}
function capNhatGio(array $quantities): void {
    $cart=[];foreach($quantities as $id=>$quantity){$id=(int)$id;$quantity=(int)$quantity;if($id>0&&$quantity>0)$cart[$id]=min(99,$quantity);}$_SESSION['cart']=$cart;
}
function xoaKhoiGio(int $productId): void {unset($_SESSION['cart'][$productId]);}
function layMatHangGio(mysqli $db): array {
    $items=[];foreach($_SESSION['cart']??[] as $id=>$quantity){$p=laySanPhamTheoId($db,(int)$id);if($p){$p['quantity']=(int)$quantity;$p['line_total']=(float)$p['sale_price']*(int)$quantity;$items[]=$p;}}return $items;
}
function datHang(mysqli $db,int $userId,string $payment,string $address,string $phone): int {
    $items=layMatHangGio($db);if(!$items)throw new RuntimeException('Giỏ hàng đang trống.');
    if(trim($address)===''||trim($phone)==='')throw new RuntimeException('Hãy nhập địa chỉ và số điện thoại nhận hàng.');
    $subtotal=array_sum(array_column($items,'line_total'));$shippingFee=tinhPhiVanChuyen($subtotal);$total=$subtotal+$shippingFee;$db->begin_transaction();
    try{
        $status='pending';$stmt=$db->prepare('INSERT INTO orders(user_id,total,shipping_fee,payment_method,shipping_address,phone,status) VALUES(?,?,?,?,?,?,?)');
        $stmt->bind_param('iddssss',$userId,$total,$shippingFee,$payment,$address,$phone,$status);$stmt->execute();$orderId=(int)$db->insert_id;
        $line=$db->prepare('INSERT INTO order_items(order_id,product_id,product_name,quantity,unit_price) VALUES(?,?,?,?,?)');
        foreach($items as $item){$pid=(int)$item['id'];$name=$item['name'];$qty=(int)$item['quantity'];$price=(float)$item['sale_price'];$line->bind_param('iisid',$orderId,$pid,$name,$qty,$price);$line->execute();}
        $db->commit();unset($_SESSION['cart']);return $orderId;
    }catch(Throwable $ex){$db->rollback();throw $ex;}
}
function layDonHangNguoiDung(mysqli $db,int $userId): array {$stmt=$db->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC');$stmt->bind_param('i',$userId);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);}
function layDonHangTheoId(mysqli $db,int $orderId,int $userId): ?array {$stmt=$db->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');$stmt->bind_param('ii',$orderId,$userId);$stmt->execute();return $stmt->get_result()->fetch_assoc()?:null;}
function layChiTietDonHang(mysqli $db,int $orderId): array {$stmt=$db->prepare('SELECT * FROM order_items WHERE order_id=?');$stmt->bind_param('i',$orderId);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);}function layDonHangMoiNhat(mysqli $db,int $userId): ?array {
    $stmt=$db->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i',$userId);$stmt->execute();
    return $stmt->get_result()->fetch_assoc()?:null;
}
function huyDonHangNguoiDung(mysqli $db,int $orderId,int $userId): bool {
    if($orderId<1||$userId<1)return false;
    $stmt=$db->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND user_id=? AND status='pending'");
    $stmt->bind_param('ii',$orderId,$userId);$stmt->execute();
    return $stmt->affected_rows===1;
}
