<?php
function timNguoiDungTheoEmail(mysqli $db,string $email): ?array {
    $stmt=$db->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email=?');
    $stmt->bind_param('s',$email); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
function layNguoiDungTheoId(mysqli $db,int $id): ?array {
    $stmt=$db->prepare('SELECT id,name,email,password_hash,role,phone,birthday,created_at FROM users WHERE id=?');
    $stmt->bind_param('i',$id);$stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
function emailDaDuocSuDung(mysqli $db,string $email,int $exceptId): bool {
    $stmt=$db->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
    $stmt->bind_param('si',$email,$exceptId);$stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}
function capNhatHoSoNguoiDung(mysqli $db,int $id,string $name,string $email,string $phone,?string $birthday,?string $passwordHash=null): bool {
    if($passwordHash!==null){
        $stmt=$db->prepare('UPDATE users SET name=?,email=?,phone=?,birthday=?,password_hash=? WHERE id=?');
        $stmt->bind_param('sssssi',$name,$email,$phone,$birthday,$passwordHash,$id);
    }else{
        $stmt=$db->prepare('UPDATE users SET name=?,email=?,phone=?,birthday=? WHERE id=?');
        $stmt->bind_param('ssssi',$name,$email,$phone,$birthday,$id);
    }
    return $stmt->execute();
}
function taoNguoiDung(mysqli $db,string $name,string $email,string $password): bool {
    $hash=password_hash($password,PASSWORD_DEFAULT);
    $stmt=$db->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'customer')");
    $stmt->bind_param('sss',$name,$email,$hash);
    return $stmt->execute();
}
function dangNhap(mysqli $db,string $email,string $password): bool {
    $record=timNguoiDungTheoEmail($db,$email);
    if (!$record || !password_verify($password,$record['password_hash'])) return false;
    session_regenerate_id(true);
    $_SESSION['user']=['id'=>(int)$record['id'],'name'=>$record['name'],'email'=>$record['email'],'role'=>$record['role']];
    return true;
}
