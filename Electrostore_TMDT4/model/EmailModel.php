<?php
function smtpReadResponse($socket): string {
    $response='';
    do {
        $line=fgets($socket, 515);
        if($line===false)throw new RuntimeException('SMTP không trả lời.');
        $response.=$line;
    } while(isset($line[3])&&$line[3]==='-');
    return $response;
}

function smtpExpect($socket,array $codes): string {
    $response=smtpReadResponse($socket);$code=(int)substr($response,0,3);
    if(!in_array($code,$codes,true))throw new RuntimeException('SMTP từ chối thao tác: '.trim($response));
    return $response;
}

function smtpCommand($socket,string $command,array $codes): string {
    if(fwrite($socket,$command."\r\n")===false)throw new RuntimeException('Không gửi được lệnh SMTP.');
    return smtpExpect($socket,$codes);
}

function guiEmailSmtp(array $config,string $recipient,string $subject,string $html): bool {
    if(!filter_var($recipient,FILTER_VALIDATE_EMAIL)||!filter_var($config['from_email']??'',FILTER_VALIDATE_EMAIL)||trim((string)($config['host']??''))==='')return false;
    $host=(string)$config['host'];$port=(int)$config['port'];$encryption=(string)$config['encryption'];$username=(string)$config['username'];$password=(string)$config['password'];
    if($port<1||$port>65535||!in_array($encryption,['tls','ssl','none'],true)||($username!==''&&$encryption==='none'))return false;
    $endpoint=($encryption==='ssl'?'ssl://':'tcp://').$host.':'.$port;$errno=0;$error='';$socket=@stream_socket_client($endpoint,$errno,$error,12,STREAM_CLIENT_CONNECT);
    if(!$socket){error_log('Electro Store SMTP connect failed: '.$error.' ('.$errno.')');return false;}
    stream_set_timeout($socket,12);
    try {
        smtpExpect($socket,[220]);smtpCommand($socket,'EHLO electrostore.local',[250]);
        if($encryption==='tls'){smtpCommand($socket,'STARTTLS',[220]);if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('Không thiết lập được mã hóa TLS.');smtpCommand($socket,'EHLO electrostore.local',[250]);}
        if($username!==''){smtpCommand($socket,'AUTH LOGIN',[334]);smtpCommand($socket,base64_encode($username),[334]);smtpCommand($socket,base64_encode($password),[235]);}
        smtpCommand($socket,'MAIL FROM:<'.$config['from_email'].'>',[250]);smtpCommand($socket,'RCPT TO:<'.$recipient.'>',[250,251]);smtpCommand($socket,'DATA',[354]);
        $fromName=trim(str_replace(["\r","\n"],'',(string)$config['from_name']));$encodedName='=?UTF-8?B?'.base64_encode($fromName).'?=';$encodedSubject='=?UTF-8?B?'.base64_encode($subject).'?=';
        $body=chunk_split(base64_encode($html),76,"\r\n");$message='From: '.$encodedName.' <'.$config['from_email'].">\r\n".'To: <'.$recipient.">\r\n".'Subject: '.$encodedSubject."\r\n".'MIME-Version: 1.0' ."\r\n".'Content-Type: text/html; charset=UTF-8' ."\r\n".'Content-Transfer-Encoding: base64' ."\r\n\r\n".$body;
        $message=preg_replace('/(?<!\r)\n/','\r\n',$message)??$message;$message=preg_replace('/\r\n\.\./','\r\n..',$message)??$message;
        if(fwrite($socket,$message."\r\n.\r\n")===false)throw new RuntimeException('Không gửi được nội dung email.');smtpExpect($socket,[250]);smtpCommand($socket,'QUIT',[221]);fclose($socket);return true;
    } catch(Throwable $error) {error_log('Electro Store SMTP send failed: '.$error->getMessage());fclose($socket);return false;}
}

function guiEmailXacNhanDonHang(mysqli $db,int $orderId): bool {
    $config=require __DIR__.'/../config/mail.php';
    try {
        $stmt=$db->prepare('SELECT o.id,o.total,o.shipping_fee,o.payment_method,o.shipping_address,o.phone,o.created_at,u.name,u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?');$stmt->bind_param('i',$orderId);$stmt->execute();$order=$stmt->get_result()->fetch_assoc();if(!$order||!filter_var($order['email'],FILTER_VALIDATE_EMAIL))return false;
        $itemsStmt=$db->prepare('SELECT product_name,quantity,unit_price FROM order_items WHERE order_id=? ORDER BY id');$itemsStmt->bind_param('i',$orderId);$itemsStmt->execute();$items=$itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);if(!$items)return false;
        $rows='';$subtotal=0.0;foreach($items as $item){$lineTotal=(float)$item['unit_price']*(int)$item['quantity'];$subtotal+=$lineTotal;$rows.='<tr><td style="padding:10px;border-bottom:1px solid #e5eaf0">'.e($item['product_name']).'</td><td style="padding:10px;text-align:center;border-bottom:1px solid #e5eaf0">'.(int)$item['quantity'].'</td><td style="padding:10px;text-align:right;border-bottom:1px solid #e5eaf0">'.e(money($lineTotal)).'</td></tr>';}
        $payment=match((string)$order['payment_method']){'cod'=>'Thanh toán khi nhận hàng','bank'=>'Chuyển khoản',default=>'Thanh toán khi nhận hàng'};
        $html='<!doctype html><html lang="vi"><meta charset="UTF-8"><body style="margin:0;background:#f2f6fa;font-family:Arial,sans-serif;color:#172b43"><div style="max-width:640px;margin:24px auto;background:#fff;border:1px solid #dce5ed;border-radius:16px;overflow:hidden"><div style="padding:24px 28px;background:#10324e;color:#fff"><div style="font-size:13px;letter-spacing:2px;color:#6bd5e9;font-weight:bold">ELECTRO STORE</div><h1 style="margin:10px 0 0;font-size:24px">Đặt hàng thành công</h1></div><div style="padding:26px 28px"><p>Chào '.e($order['name']).',</p><p>Cửa hàng đã tiếp nhận đơn hàng <strong>#'.(int)$order['id'].'</strong>. Đơn hiện đang chờ xác nhận.</p><table style="width:100%;border-collapse:collapse;margin:20px 0"><thead><tr style="background:#f4f7fa;text-align:left"><th style="padding:10px">Sản phẩm</th><th style="padding:10px;text-align:center">SL</th><th style="padding:10px;text-align:right">Thành tiền</th></tr></thead><tbody>'.$rows.'</tbody></table><div style="line-height:1.9"><div>Tiền hàng: <strong>'.e(money($subtotal)).'</strong></div><div>Phí vận chuyển: <strong>'.(((float)$order['shipping_fee'])>0?e(money($order['shipping_fee'])):'Miễn phí').'</strong></div><div style="font-size:18px;margin-top:6px">Tổng thanh toán: <strong style="color:#0786a7">'.e(money($order['total'])).'</strong></div></div><hr style="border:0;border-top:1px solid #e5eaf0;margin:22px 0"><p><strong>Thông tin nhận hàng</strong><br>'.e($order['name']).' · '.e($order['phone']).'<br>'.e($order['shipping_address']).'<br>Thanh toán: '.e($payment).'</p><p style="color:#63758b;font-size:13px">Bạn có thể theo dõi trạng thái đơn hàng trong mục Đơn hàng trên website.</p></div></div></body></html>';
        return guiEmailSmtp($config,(string)$order['email'],'Xác nhận đơn hàng #'.(int)$order['id'].' - Electro Store',$html);
    } catch(Throwable $error) {error_log('Electro Store order email failed: '.$error->getMessage());return false;}
}
