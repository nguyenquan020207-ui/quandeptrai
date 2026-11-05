<?php
function damBaoCotTonKhoDonHang(mysqli $db): void {
    static $ready = false;
    if ($ready) return;
    $column = $db->query("SHOW COLUMNS FROM orders LIKE 'stock_reserved'");
    if (!$column || $column->num_rows === 0) {
        $db->query('ALTER TABLE orders ADD stock_reserved TINYINT(1) NOT NULL DEFAULT 0 AFTER shipping_fee');
    }
    $ready = true;
}

function tinhPhiVanChuyen(float|int $subtotal): float { return $subtotal>=5000000?0.0:100000.0; }
function themVaoGio(int $productId,int $quantity=1): void {
    if($productId>0)$_SESSION['cart'][$productId]=min(99,max(1,(int)($_SESSION['cart'][$productId]??0)+$quantity));
}
function capNhatGio(mysqli $db,array $quantities): ?string {
    $cart=[];$limited=[];foreach($quantities as $id=>$quantity){$id=(int)$id;$quantity=(int)$quantity;if($id<1||$quantity<1)continue;$product=laySanPhamTheoId($db,$id);if(!$product)continue;$stock=max(0,(int)($product['stock']??0));if($stock<1){$limited[]=$product['name'].' đã hết hàng';continue;}if($quantity>$stock){$quantity=$stock;$limited[]=$product['name'].' chỉ còn '.$stock.' sản phẩm';}$cart[$id]=min(99,$quantity);}$_SESSION['cart']=$cart;return $limited?'Đã cập nhật giỏ hàng. '.implode('; ',$limited).'.':'';
}
function xoaKhoiGio(int $productId): void {unset($_SESSION['cart'][$productId]);}
function layMatHangGio(mysqli $db): array {
    $items=[];foreach($_SESSION['cart']??[] as $id=>$quantity){$p=laySanPhamTheoId($db,(int)$id);if($p){$p['quantity']=(int)$quantity;$p['line_total']=(float)$p['sale_price']*(int)$quantity;$items[]=$p;}}return $items;
}
function datHang(mysqli $db,int $userId,string $payment,string $address,string $phone): int {
    $cart=$_SESSION['cart']??[];if(!$cart)throw new RuntimeException('Giỏ hàng đang trống.');
    if(trim($address)===''||trim($phone)==='')throw new RuntimeException('Hãy nhập địa chỉ và số điện thoại nhận hàng.');
    damBaoCotTonKhoDonHang($db);$db->begin_transaction();
    try{
        // Lock products in ascending id order so concurrent checkouts cannot oversell stock.
        $quantities=[];foreach($cart as $productId=>$quantity){$productId=(int)$productId;$quantity=(int)$quantity;if($productId<1||$quantity<1)continue;$quantities[$productId]=min(99,$quantity);}ksort($quantities,SORT_NUMERIC);
        if(!$quantities)throw new RuntimeException('Giỏ hàng đang trống.');
        $productStmt=$db->prepare('SELECT id,name,sale_price,stock,active FROM products WHERE id=? FOR UPDATE');$items=[];$subtotal=0.0;
        foreach($quantities as $productId=>$quantity){$productStmt->bind_param('i',$productId);$productStmt->execute();$product=$productStmt->get_result()->fetch_assoc();if(!$product||(int)$product['active']!==1)throw new RuntimeException('Một sản phẩm trong giỏ không còn được bán. Vui lòng cập nhật giỏ hàng.');if((int)$product['stock']<$quantity)throw new RuntimeException('Số lượng tồn kho của “'.$product['name'].'” không đủ. Còn '.$product['stock'].' sản phẩm.');$product['quantity']=$quantity;$product['line_total']=(float)$product['sale_price']*$quantity;$items[]=$product;$subtotal+=$product['line_total'];}
        $shippingFee=tinhPhiVanChuyen($subtotal);$total=$subtotal+$shippingFee;$status='pending';$stmt=$db->prepare('INSERT INTO orders(user_id,total,shipping_fee,stock_reserved,payment_method,shipping_address,phone,status) VALUES(?,?,?,1,?,?,?,?)');
        $stmt->bind_param('iddssss',$userId,$total,$shippingFee,$payment,$address,$phone,$status);$stmt->execute();$orderId=(int)$db->insert_id;
        $line=$db->prepare('INSERT INTO order_items(order_id,product_id,product_name,quantity,unit_price) VALUES(?,?,?,?,?)');
        $decrement=$db->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
        foreach($items as $item){$pid=(int)$item['id'];$name=$item['name'];$qty=(int)$item['quantity'];$price=(float)$item['sale_price'];$line->bind_param('iisid',$orderId,$pid,$name,$qty,$price);$line->execute();$decrement->bind_param('iii',$qty,$pid,$qty);$decrement->execute();if($decrement->affected_rows!==1)throw new RuntimeException('Không đủ hàng trong kho để hoàn tất đơn. Hãy tải lại giỏ hàng.');}
        $db->commit();unset($_SESSION['cart']);return $orderId;
    }catch(Throwable $ex){$db->rollback();throw $ex;}
}

function capNhatTrangThaiDonHangVaKho(mysqli $db,int $orderId,string $status,?string $expectedStatus=null): bool {
    $allowed=['pending','confirmed','shipping','completed','cancelled'];if($orderId<1||!in_array($status,$allowed,true))throw new RuntimeException('Trạng thái đơn hàng không hợp lệ.');damBaoCotTonKhoDonHang($db);$db->begin_transaction();
    try{$stmt=$db->prepare('SELECT status,stock_reserved FROM orders WHERE id=? FOR UPDATE');$stmt->bind_param('i',$orderId);$stmt->execute();$order=$stmt->get_result()->fetch_assoc();if(!$order)throw new RuntimeException('Không tìm thấy đơn hàng.');if($expectedStatus!==null&&$order['status']!==$expectedStatus){$db->rollback();return false;}$reserved=(int)$order['stock_reserved']===1;$wasCancelled=$order['status']==='cancelled';$isCancelled=$status==='cancelled';
        if(!$wasCancelled&&$isCancelled&&$reserved){$lines=$db->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=? ORDER BY product_id FOR UPDATE');$lines->bind_param('i',$orderId);$lines->execute();$result=$lines->get_result();$restore=$db->prepare('UPDATE products SET stock=stock+? WHERE id=?');while($line=$result->fetch_assoc()){$qty=(int)$line['quantity'];$pid=(int)$line['product_id'];$restore->bind_param('ii',$qty,$pid);$restore->execute();}$db->query('UPDATE orders SET stock_reserved=0 WHERE id='.(int)$orderId);}
        elseif($wasCancelled&&!$isCancelled&&!$reserved){$lines=$db->prepare('SELECT product_id,product_name,quantity FROM order_items WHERE order_id=? ORDER BY product_id FOR UPDATE');$lines->bind_param('i',$orderId);$lines->execute();$result=$lines->get_result();$reserve=$db->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');while($line=$result->fetch_assoc()){$qty=(int)$line['quantity'];$pid=(int)$line['product_id'];$reserve->bind_param('iii',$qty,$pid,$qty);$reserve->execute();if($reserve->affected_rows!==1)throw new RuntimeException('Không thể mở lại đơn: tồn kho không đủ cho “'.$line['product_name'].'”.');}$db->query('UPDATE orders SET stock_reserved=1 WHERE id='.(int)$orderId);}
        $update=$db->prepare('UPDATE orders SET status=? WHERE id=?');$update->bind_param('si',$status,$orderId);$update->execute();$db->commit();return true;
    }catch(Throwable $error){$db->rollback();throw $error;}
}
function layDonHangNguoiDung(mysqli $db,int $userId): array {$stmt=$db->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC');$stmt->bind_param('i',$userId);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);}
function layDonHangTheoId(mysqli $db,int $orderId,int $userId): ?array {$stmt=$db->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');$stmt->bind_param('ii',$orderId,$userId);$stmt->execute();return $stmt->get_result()->fetch_assoc()?:null;}
function layChiTietDonHang(mysqli $db,int $orderId): array {$stmt=$db->prepare('SELECT * FROM order_items WHERE order_id=?');$stmt->bind_param('i',$orderId);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);}function layDonHangMoiNhat(mysqli $db,int $userId): ?array {
    $stmt=$db->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i',$userId);$stmt->execute();
    return $stmt->get_result()->fetch_assoc()?:null;
}
function huyDonHangNguoiDung(mysqli $db,int $orderId,int $userId): bool {
    if($orderId<1||$userId<1)return false;damBaoCotTonKhoDonHang($db);$stmt=$db->prepare('SELECT id FROM orders WHERE id=? AND user_id=?');$stmt->bind_param('ii',$orderId,$userId);$stmt->execute();if(!$stmt->get_result()->fetch_assoc())return false;return capNhatTrangThaiDonHangVaKho($db,$orderId,'cancelled','pending');
}
