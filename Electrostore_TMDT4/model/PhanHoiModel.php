<?php
function guiLienHe(mysqli $db,string $name,string $email,string $message): void {
    $stmt=$db->prepare('INSERT INTO contacts(name,email,message) VALUES(?,?,?)');$stmt->bind_param('sss',$name,$email,$message);$stmt->execute();
}
function themDanhGia(mysqli $db,int $productId,int $userId,int $stars,string $comment): void {
    $stmt=$db->prepare('INSERT INTO reviews(product_id,user_id,stars,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE stars=VALUES(stars),comment=VALUES(comment)');
    $stmt->bind_param('iiis',$productId,$userId,$stars,$comment);$stmt->execute();
}
function laySanPhamDanhGiaDonHang(mysqli $db,int $orderId,int $userId): array {
    $stmt=$db->prepare("SELECT p.id AS product_id,p.name,p.image_url,oi.quantity,r.stars,r.comment FROM orders o JOIN order_items oi ON oi.order_id=o.id JOIN products p ON p.id=oi.product_id LEFT JOIN reviews r ON r.product_id=p.id AND r.user_id=o.user_id WHERE o.id=? AND o.user_id=? AND o.status='completed' ORDER BY oi.id");
    $stmt->bind_param('ii',$orderId,$userId);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function guiDanhGiaDonHang(mysqli $db,int $orderId,int $userId,int $productId,int $stars,string $comment): bool {
    if($orderId<1||$userId<1||$productId<1||$stars<1||$stars>5||trim($comment)===''||mb_strlen($comment)>2000)return false;
    $db->begin_transaction();
    try{
        $stmt=$db->prepare("SELECT o.id FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.id=? AND o.user_id=? AND o.status='completed' AND oi.product_id=? FOR UPDATE");
        $stmt->bind_param('iii',$orderId,$userId,$productId);$stmt->execute();$eligible=(bool)$stmt->get_result()->fetch_row();
        if(!$eligible){$db->rollback();return false;}
        themDanhGia($db,$productId,$userId,$stars,trim($comment));$db->commit();return true;
    }catch(Throwable $error){$db->rollback();throw $error;}
}
function layDanhGia(mysqli $db,int $productId): array {
    $stmt=$db->prepare('SELECT r.*,u.name AS user_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? ORDER BY r.id DESC');$stmt->bind_param('i',$productId);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function thongKeDanhGia(mysqli $db,int $productId): array {
    $stmt=$db->prepare('SELECT AVG(stars) AS average,COUNT(*) AS count FROM reviews WHERE product_id=?');$stmt->bind_param('i',$productId);$stmt->execute();return $stmt->get_result()->fetch_assoc()?:['average'=>0,'count'=>0];
}
