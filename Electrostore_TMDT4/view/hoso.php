<?php require __DIR__.'/app.php';render_header('Thông tin cá nhân');$profileName=$profileValues['name']??'';$profileEmail=$profileValues['email']??'';$profilePhone=$profileValues['phone']??'';$profileBirthday=$profileValues['birthday']??'';?>
<section class="page-heading"><span class="eyebrow">TÀI KHOẢN CỦA BẠN</span><h1>Thông tin cá nhân</h1><p>Cập nhật thông tin tài khoản và mật khẩu của bạn.</p></section>
<?php if(!empty($error)):?><div class="error profile-message" role="alert"><?=e($error)?></div><?php endif;?>
<div class="profile-page-layout"><section class="profile-panel profile-edit-panel"><form method="post" class="profile-edit-form">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<h2>Thông tin tài khoản</h2><div class="profile-fields">
<label>Họ và tên<input name="name" required maxlength="120" autocomplete="name" value="<?=e($profileName)?>"></label>
<label>Email<input type="email" name="email" required maxlength="190" autocomplete="email" value="<?=e($profileEmail)?>"></label>
<label>Số điện thoại<input type="tel" name="phone" maxlength="30" autocomplete="tel" value="<?=e($profilePhone)?>"></label>
<label>Ngày sinh<input type="date" name="birthday" max="<?=date('Y-m-d')?>" value="<?=e($profileBirthday)?>"></label>
</div><div class="profile-password-section"><h2>Đổi mật khẩu <small>(không bắt buộc)</small></h2><p>Để trống các ô bên dưới nếu bạn không muốn đổi mật khẩu.</p><div class="profile-fields">
<label>Mật khẩu hiện tại<input type="password" name="current_password" autocomplete="current-password"></label>
<label>Mật khẩu mới<input type="password" name="new_password" minlength="8" autocomplete="new-password"></label>
<label>Nhập lại mật khẩu mới<input type="password" name="confirm_password" minlength="8" autocomplete="new-password"></label>
</div></div><div class="profile-form-actions"><button class="button button-dark" type="submit">Lưu thay đổi</button><a class="button button-light" href="index.php?page=taikhoan">Đơn hàng của tôi</a></div>
</form></section><aside class="profile-panel profile-summary-panel"><span class="profile-avatar-large"><?=e(mb_strtoupper(mb_substr($profileName!==''?$profileName:user()['name'],0,1)))?></span><h2><?=e($profileName!==''?$profileName:user()['name'])?></h2><p><?=e($profileEmail)?></p><span class="profile-role-pill"><?=user()['role']==='admin'?'Quản trị viên':(str_starts_with((string)user()['role'],'staff_')?'Nhân viên':'Khách hàng')?></span><a href="index.php?page=taikhoan">Theo dõi đơn hàng →</a></aside></div>
<?php render_footer();?>
