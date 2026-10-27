<?php
require_once __DIR__.'/config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function go(string $url): never { header('Location: '.$url); exit; }
function money(float|int|string $amount): string { return number_format((float)$amount, 0, ',', '.') . '₫'; }
function flash(?string $message = null): string {
    if ($message !== null) { $_SESSION['flash'] = $message; return ''; }
    $value = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']); return $value;
}
function image_url(array $product): string {
    if (!empty($product['image_url'])) return (string)$product['image_url'];
    return 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=900&q=80';
}
function nhanTrangThaiDonHang(string $status): string {
    return match ($status) {
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Đã giao hàng',
        'cancelled' => 'Đã hủy',
        default => 'Không xác định',
    };
}
function lopTrangThaiDonHang(string $status): string {
    return match ($status) {
        'pending' => 'waiting',
        'confirmed' => 'confirmed',
        'shipping' => 'shipping',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        default => 'waiting',
    };
}
require_once __DIR__.'/config/auth.php';