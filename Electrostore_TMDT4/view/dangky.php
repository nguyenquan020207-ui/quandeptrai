<?php require __DIR__.'/app.php';render_header('Tạo tài khoản');?>
<section class="form-page">
 <div class="form-card">
  <span class="eyebrow">TÀI KHOẢN ELECTRO STORE</span>
  <h1>Tạo tài khoản</h1>
  <?php if(!empty($error)):?><div class="error"><?=e($error)?></div><?php endif;?>
  <form method="post" data-password-match>
   <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
   <label>Họ và tên<input name="name" required maxlength="120" autocomplete="name"></label>
   <label>Email<input type="email" name="email" required autocomplete="email"></label>
   <label>Mật khẩu<div class="password-field"><input id="register-password" type="password" name="password" required minlength="8" autocomplete="new-password"><button class="password-toggle" type="button" data-toggle-password="register-password" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div></label>
   <label>Nhập lại mật khẩu<div class="password-field"><input id="register-password-confirm" type="password" name="confirm_password" required minlength="8" autocomplete="new-password"><button class="password-toggle" type="button" data-toggle-password="register-password-confirm" aria-label="Hiện mật khẩu nhập lại" title="Hiện mật khẩu nhập lại"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div></label>
   <button class="button button-orange full">Đăng ký</button>
  </form>
  <p>Đã có tài khoản? <a href="index.php?page=dangnhap">Đăng nhập</a></p>
 </div>
</section>
<?php render_footer();?>