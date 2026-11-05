<?php
declare(strict_types=1);
require_once __DIR__ . '/../global.php';
require_once __DIR__ . '/../model/AdminModel.php';
require_admin();
require_section_access('orders');
$orderId = max(0, (int)($_GET['id'] ?? 0));
$order = adminDonHangChiTiet(db(), $orderId);
if (!$order) { http_response_code(404); exit('Không tìm thấy đơn hàng.'); }
if (!in_array((string)$order['status'], ['shipping', 'completed'], true)) {
    http_response_code(403);
    exit('Chỉ có thể in hóa đơn khi đơn hàng đang giao hoặc đã giao.');
}
$shippingFee = (float)($order['shipping_fee'] ?? 0);
$subtotal = max(0, (float)$order['total'] - $shippingFee);
$paymentLabels = ['cod'=>'Thanh toán khi nhận hàng','bank'=>'Chuyển khoản ngân hàng','bank_transfer'=>'Chuyển khoản ngân hàng'];
$paymentLabel = $paymentLabels[(string)($order['payment_method'] ?? '')] ?? 'Chưa cập nhật';
$orderCode = 'OR' . str_pad((string)(int)$order['id'], 6, '0', STR_PAD_LEFT);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hóa đơn <?=e($orderCode)?> · Electro Store</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eef3f7;color:#172b42;font:15px/1.55 Arial,sans-serif}.invoice{width:min(850px,calc(100% - 32px));margin:32px auto;padding:42px;background:#fff;border:1px solid #dbe4ec;border-radius:14px;box-shadow:0 12px 36px #18344b12}.toolbar{display:flex;justify-content:flex-end;gap:10px;margin-bottom:26px}.toolbar button,.toolbar a{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 16px;border:1px solid #d5e0e8;border-radius:8px;background:#fff;color:#17334f;font-weight:700;text-decoration:none;cursor:pointer}.toolbar button{border-color:#087f9f;background:#087f9f;color:#fff}.header{display:flex;justify-content:space-between;gap:24px;padding-bottom:24px;border-bottom:2px solid #17334f}.brand{font-size:26px;font-weight:800;letter-spacing:-.5px;color:#12314d}.brand span{color:#0785a5}.store-info,.invoice-meta{color:#64778a;font-size:13px}h1{margin:28px 0 4px;font-size:26px;text-align:center;text-transform:uppercase;letter-spacing:.6px}.subtitle{text-align:center;color:#64778a;margin:0 0 28px}.parties{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:26px}.parties section{padding:16px;border:1px solid #e1e8ef;border-radius:9px}.parties h2{margin:0 0 10px;font-size:14px;color:#087f9f}.parties p{margin:4px 0;overflow-wrap:anywhere}table{width:100%;border-collapse:collapse}th,td{padding:11px 10px;border-bottom:1px solid #e1e8ef;text-align:left}th{background:#f1f6f9;color:#29445e;font-size:12px;text-transform:uppercase}td.num,th.num{text-align:right;white-space:nowrap}.totals{width:min(350px,100%);margin:20px 0 0 auto}.totals div{display:flex;justify-content:space-between;gap:20px;padding:6px 0}.totals .grand{margin-top:6px;padding-top:12px;border-top:2px solid #17334f;font-size:18px;font-weight:800;color:#087f9f}.note{margin-top:36px;padding-top:16px;border-top:1px dashed #cbd7e1;text-align:center;color:#64778a;font-size:13px}@media(max-width:600px){.invoice{width:100%;margin:0;padding:22px 16px;border:0;border-radius:0}.header{flex-direction:column}.parties{grid-template-columns:1fr}table{font-size:12px}th,td{padding:8px 5px}.toolbar{justify-content:center}}@media print{body{background:#fff;color:#111;font-size:12px}.invoice{width:100%;margin:0;padding:0;border:0;border-radius:0;box-shadow:none}.toolbar{display:none}.header{border-color:#333}th{background:#eee!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.parties section{break-inside:avoid}.note{margin-top:22px}}
</style></head><body><main class="invoice">
<div class="toolbar"><a href="index.php?section=orders&amp;action=view&amp;id=<?=(int)$order['id']?>">Quay lại đơn hàng</a><button type="button" onclick="window.print()">In hóa đơn</button></div>
<header class="header"><div><div class="brand">Electro <span>Store</span></div><div class="store-info">Thiết bị chính hãng, giá tốt<br>Hotline: 1900 6868</div></div><div class="invoice-meta"><strong>Mã hóa đơn:</strong> <?=e($orderCode)?><br><strong>Ngày đặt:</strong> <?=e(date('d/m/Y H:i',strtotime((string)$order['created_at'])))?><br><strong>Trạng thái:</strong> <?=e(nhanTrangThaiDonHang((string)$order['status']))?></div></header>
<h1>Hóa đơn bán hàng</h1><p class="subtitle">Cảm ơn quý khách đã mua hàng tại Electro Store.</p>
<div class="parties"><section><h2>Thông tin khách hàng</h2><p><strong>Người nhận:</strong> <?=e($order['customer_name'])?></p><p><strong>Email:</strong> <?=e($order['email'])?></p><p><strong>Số điện thoại:</strong> <?=e($order['phone'])?></p><p><strong>Địa chỉ giao hàng:</strong> <?=nl2br(e($order['shipping_address']))?></p></section><section><h2>Thông tin thanh toán</h2><p><strong>Phương thức:</strong> <?=e($paymentLabel)?></p><p><strong>Mã đơn hàng:</strong> <?=e($orderCode)?></p><p><strong>Ngày lập:</strong> <?=e(date('d/m/Y'))?></p></section></div>
<table><thead><tr><th>STT</th><th>Sản phẩm</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Thành tiền</th></tr></thead><tbody><?php foreach($order['items'] as $index=>$item):?><tr><td><?=($index+1)?></td><td><?=e($item['product_name'])?></td><td class="num"><?=number_format((int)$item['quantity'])?></td><td class="num"><?=money($item['unit_price'])?></td><td class="num"><?=money($item['line_total'])?></td></tr><?php endforeach;?></tbody></table>
<div class="totals"><div><span>Tiền hàng</span><strong><?=money($subtotal)?></strong></div><div><span>Phí vận chuyển</span><strong><?=$shippingFee>0?money($shippingFee):'Miễn phí'?></strong></div><div class="grand"><span>Tổng thanh toán</span><strong><?=money($order['total'])?></strong></div></div>
<footer class="note">Hóa đơn được lập từ thông tin đơn hàng trên hệ thống Electro Store.</footer></main></body></html>
