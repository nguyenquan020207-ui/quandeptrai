<?php define('AUTH_PAGE',true);require __DIR__.'/app.php';render_header('Đăng nhập');?>
<section class="form-page login-page">
 <div class="login-shell">
  <aside class="login-showcase" aria-label="Giới thiệu Electro Store">
   <a class="login-showcase-brand" href="index.php" aria-label="Electro Store - Trang chủ"><span>E</span><span>Electro Store<small>THIẾT BỊ CÔNG NGHỆ</small></span></a>
   <div class="login-showcase-copy"><span class="login-kicker">KHÔNG GIAN CỦA BẠN</span><h2>Công nghệ tốt,<br><em>trải nghiệm trọn vẹn.</em></h2><p>Đăng nhập để tiếp tục khám phá sản phẩm và quản lý đơn hàng của bạn.</p></div>
   <div class="login-orbit" aria-hidden="true"><span class="login-orbit-core">E</span><i></i><b></b><small></small></div>
   <span class="login-showcase-foot">ELECTRO STORE <span>·</span> MUA SẮM CÔNG NGHỆ</span>
  </aside>
  <div class="form-card login-card">
   <nav class="auth-tabs" aria-label="Tài khoản"><a class="active" aria-current="page" href="index.php?page=dangnhap<?=($_GET['next']??'')==='thanhtoan'?'&next=thanhtoan':''?>">Đăng nhập</a><a href="index.php?page=dangky<?=($_GET['next']??'')==='thanhtoan'?'&next=thanhtoan':''?>">Đăng ký</a></nav>
   <h1>Đăng nhập</h1>
   <p class="login-intro">Nhập thông tin tài khoản để tiếp tục.</p>
   <?php if(!empty($error)):?><div class="error"><?=e($error)?></div><?php endif;?>
   <form method="post" data-login-form>
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <?php if(($_GET['next']??'')==='thanhtoan'):?><input type="hidden" name="next" value="thanhtoan"><?php endif;?>
    <label>Email<input type="email" name="email" value="<?=e($_POST['email']??'')?>" placeholder="ban@example.com" required autocomplete="email"></label>
    <label>Mật khẩu<div class="password-field"><input id="login-password" type="password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password"><button class="password-toggle" type="button" data-toggle-password="login-password" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div></label>
    <button class="button button-orange full" type="submit"><span>Đăng nhập</span><span aria-hidden="true">→</span></button>
   </form>
   <p class="login-register">Chưa có tài khoản? <a href="index.php?page=dangky<?=($_GET['next']??'')==='thanhtoan'?'&next=thanhtoan':''?>">Tạo tài khoản</a></p>
   <div class="login-secure-note"><span aria-hidden="true">✓</span> Đăng nhập an toàn vào Electro Store</div>
  </div>
 </div>
</section>
<?php render_footer();?>
