<?php
function sanPhamDieuKien(array $filter): array {
    $where=['p.active=1'];$types='';$values=[];
    if(!empty($filter['category_id'])){$where[]='p.category_id=?';$types.='i';$values[]=(int)$filter['category_id'];}
    if(!empty($filter['q'])){$where[]='(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)';$term='%'.trim((string)$filter['q']).'%';$types.='sss';array_push($values,$term,$term,$term);}
    if(!empty($filter['sale']))$where[]='p.sale_price<p.list_price';
    if(!empty($filter['featured_only']))$where[]='p.featured=1';
    return [' WHERE '.implode(' AND ',$where),$types,$values];
}
function laySanPham(mysqli $db,array $filter=[]): array {
    [$where,$types,$values]=sanPhamDieuKien($filter);$sort=$filter['sort']??'featured';$order=match($sort){'price_asc'=>'p.sale_price ASC','price_desc'=>'p.sale_price DESC','newest'=>'p.id DESC',default=>'p.featured DESC,p.id ASC'};$sql='SELECT p.*,c.name AS category_name,c.slug AS category_slug FROM products p JOIN categories c ON c.id=p.category_id'.$where.' ORDER BY '.$order;
    if(!empty($filter['limit'])){$sql.=' LIMIT ? OFFSET ?';$types.='ii';$values[]=max(1,min(60,(int)$filter['limit']));$values[]=max(0,(int)($filter['offset']??0));}
    if($types==='')return $db->query($sql)->fetch_all(MYSQLI_ASSOC);$stmt=$db->prepare($sql);$stmt->bind_param($types,...$values);$stmt->execute();return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function demSanPham(mysqli $db,array $filter=[]): int {[$where,$types,$values]=sanPhamDieuKien($filter);$stmt=$db->prepare('SELECT COUNT(*) FROM products p'.$where);if($types!=='')$stmt->bind_param($types,...$values);$stmt->execute();return (int)$stmt->get_result()->fetch_row()[0];}
function laySanPhamTheoId(mysqli $db, int $id): ?array {
    $stmt=$db->prepare('SELECT p.*,c.name AS category_name,c.slug AS category_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.active=1');
    $stmt->bind_param('i',$id); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
