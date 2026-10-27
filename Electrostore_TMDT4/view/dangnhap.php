<?php require __DIR__.'/app.php';render_header('Đăng nhập');?>
<section class="form-page">
 <div class="form-card">
  <span class="eyebrow">CHÀO MỪNG TRỞ LẠI</span>
  <h1>Đăng nhập</h1>
  <?php if(!empty($error)):?><div class="error"><?=e($error)?></div><?php endif;?>
  <form method="post" data-login-form>
   <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
   <label>Email<input type="email" name="email" value="<?=e($_POST['email']??'')?>" required autocomplete="email"></label>
   <label>Mật khẩu<div class="password-field"><input id="login-password" type="password" name="password" required autocomplete="current-password"><button class="password-toggle" type="button" data-toggle-password="login-password" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div></label>
   <button class="button button-orange full" type="submit">Đăng nhập</button>
  </form>
  <p>Chưa có tài khoản? <a href="index.php?page=dangky">Đăng ký</a></p>
 </div>
</section>
<?php render_footer();?>