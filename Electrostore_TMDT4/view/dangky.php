<?php define('AUTH_PAGE',true);require __DIR__.'/app.php';render_header('Tạo tài khoản');?>
<section class="form-page login-page register-page">
 <div class="login-shell register-shell">
  <aside class="login-showcase" aria-label="Giới thiệu Electro Store">
   <a class="login-showcase-brand" href="index.php" aria-label="Electro Store - Trang chủ"><span>E</span><span>Electro Store<small>THIẾT BỊ CÔNG NGHỆ</small></span></a>
   <div class="login-showcase-copy"><span class="login-kicker">BẮT ĐẦU HÀNH TRÌNH</span><h2>Tạo tài khoản,<br><em>mở rộng trải nghiệm.</em></h2><p>Lưu thông tin của bạn, theo dõi đơn hàng và khám phá các thiết bị phù hợp tại Electro Store.</p></div>
   <div class="login-orbit" aria-hidden="true"><span class="login-orbit-core">E</span><i></i><b></b><small></small></div>
   <span class="login-showcase-foot">ELECTRO STORE <span>·</span> MUA SẮM CÔNG NGHỆ</span>
  </aside>
  <div class="form-card login-card register-card">
   <nav class="auth-tabs" aria-label="Tài khoản"><a href="index.php?page=dangnhap<?=($_GET['next']??'')==='thanhtoan'?'&next=thanhtoan':''?>">Đăng nhập</a><a class="active" aria-current="page" href="index.php?page=dangky<?=($_GET['next']??'')==='thanhtoan'?'&next=thanhtoan':''?>">Đăng ký</a></nav>
   <h1>Tạo tài khoản</h1>
   <p class="login-intro">Điền thông tin để tham gia cùng Electro Store.</p>
   <?php if(!empty($error)):?><div class="error"><?=e($error)?></div><?php endif;?>
   <form method="post" data-password-match>
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <?php if(($_GET['next']??'')==='thanhtoan'):?><input type="hidden" name="next" value="thanhtoan"><?php endif;?>
    <label>Họ và tên<input name="name" value="<?=e($_POST['name']??'')?>" placeholder="Nhập họ và tên" required maxlength="120" autocomplete="name"></label>
    <label>Email<input type="email" name="email" value="<?=e($_POST['email']??'')?>" placeholder="ban@example.com" required autocomplete="email"></label>
    <label>Mật khẩu<div class="password-field"><input id="register-password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" required minlength="8" autocomplete="new-password"><button class="password-toggle" type="button" data-toggle-password="register-password" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div></label>
    <label>Nhập lại mật khẩu<div class="password-field"><input id="register-password-confirm" type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required minlength="8" autocomplete="new-password"><button class="password-toggle" type="button" data-toggle-password="register-password-confirm" aria-label="Hiện mật khẩu nhập lại" title="Hiện mật khẩu nhập lại"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div></label>
    <button class="button button-orange full" type="submit"><span>Đăng ký</span><span aria-hidden="true">→</span></button>
   </form>
   <p class="login-register">Đã có tài khoản? <a href="index.php?page=dangnhap<?=($_GET['next']??'')==='thanhtoan'?'&next=thanhtoan':''?>">Đăng nhập</a></p>
   <div class="login-secure-note"><span aria-hidden="true">✓</span> Thông tin của bạn được bảo vệ an toàn</div>
  </div>
 </div>
</section>
<?php render_footer();?>
