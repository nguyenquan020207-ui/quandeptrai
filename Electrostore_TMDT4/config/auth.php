<?php
function require_login(): void {
    if (!user()) go('index.php?page=dangnhap');
}
function require_admin(): void {
    if (!user()) go('../index.php?page=dangnhap');
    if (!in_array((user()['role'] ?? ''), ['admin','staff_products','staff_orders','staff_support'], true)) {
        http_response_code(403);
        exit('403: Chỉ quản trị viên được truy cập.');
    }
}
function phamViNhanVien(string $role): array {
    return match($role) {
        'staff_products'=>['products','categories','brands','attendance'],
        'staff_orders'=>['orders','attendance'],
        'staff_support'=>['customers','reviews','contacts','attendance'],
        default=>[],
    };
}
function require_section_access(string $section): void {
    if((user()['role']??'')==='admin')return;
    if(!in_array($section,phamViNhanVien((string)(user()['role']??'')),true)){
        http_response_code(403);
        exit('403: Tài khoản nhân viên không được phân quyền vào mục này.');
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Phiên biểu mẫu đã hết hạn. Hãy tải lại trang và thử lại.');
    }
}
