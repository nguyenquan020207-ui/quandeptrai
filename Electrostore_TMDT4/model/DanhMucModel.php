<?php
function layTatCaDanhMuc(mysqli $db): array {
    return $db->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.active=1) AS product_count FROM categories c WHERE c.active=1 ORDER BY c.name')->fetch_all(MYSQLI_ASSOC);
}
function layDanhMucTheoId(mysqli $db, int $id): ?array {
    $stmt=$db->prepare('SELECT * FROM categories WHERE id=? AND active=1');
    $stmt->bind_param('i',$id); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
function layDanhMucTheoSlug(mysqli $db, string $slug): ?array {
    $stmt=$db->prepare('SELECT * FROM categories WHERE slug=? AND active=1');
    $stmt->bind_param('s',$slug); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}