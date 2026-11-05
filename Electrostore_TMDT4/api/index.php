<?php
require_once __DIR__.'/../global.php';
require_once __DIR__.'/../model/SanPhamModel.php';
require_once __DIR__.'/../model/DanhMucModel.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $action=$_GET['action']??'products';
    $data=match($action) {
        'categories'=>layTatCaDanhMuc(db()),
        'product'=>laySanPhamTheoId(db(),(int)($_GET['id']??0)),
        default=>laySanPham(db(),['q'=>$_GET['q']??'','category_id'=>(int)($_GET['category_id']??0),'sale'=>!empty($_GET['sale'])])
    };
    echo json_encode(['ok'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(Throwable $ex) {
    http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Không thể tải dữ liệu.'],JSON_UNESCAPED_UNICODE);
}