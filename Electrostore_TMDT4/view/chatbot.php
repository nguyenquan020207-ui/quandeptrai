<div class="shop-chatbot" data-chatbot data-endpoint="<?=$base?>api/chatbot.php">
  <section class="shop-chatbot-panel" data-chat-panel hidden aria-label="Trợ lý Electro Store">
    <header>
      <span class="chat-avatar">E</span>
      <div><b>Trợ lý Electro Store</b><small>Tư vấn sản phẩm và hỗ trợ đơn hàng</small></div>
      <button type="button" data-chat-close aria-label="Đóng trò chuyện">×</button>
    </header>
    <nav class="chat-contact-links" aria-label="Liên hệ nhanh">
      <a href="tel:19006868"><span class="chat-contact-icon">☎</span><b>Gọi điện</b><small>1900 6868</small></a>
      <a href="https://zalo.me/0838612236" target="_blank" rel="noopener noreferrer"><span class="chat-contact-icon chat-contact-zalo">Z</span><b>Zalo</b><small>Nhắn cửa hàng</small></a>
      <a href="https://www.facebook.com/huunguyen.quan.50" target="_blank" rel="noopener noreferrer"><span class="chat-contact-icon chat-contact-facebook">f</span><b>Facebook</b><small>Trang cửa hàng</small></a>
    </nav>
    <div class="chat-messages" data-chat-messages aria-live="polite">
      <p class="chat-bubble bot-message">Chào bạn 👋 Mình có thể gợi ý thiết bị theo nhu cầu/ngân sách, tra giá và tồn kho hoặc hỗ trợ đơn hàng. Bạn đang cần gì nè?</p>
      <div class="chat-quick-replies" data-chat-suggestions>
        <button type="button" data-chat-prompt="Tư vấn laptop cho học tập">Tư vấn laptop</button>
        <button type="button" data-chat-prompt="Điện thoại dưới 10 triệu">Điện thoại dưới 10 triệu</button>
        <button type="button" data-chat-prompt="Phí giao hàng thế nào?">Phí giao hàng</button>
      </div>
    </div>
    <form data-chat-form>
      <label class="sr-only" for="shop-chat-input">Nhập câu hỏi</label>
      <input id="shop-chat-input" data-chat-input maxlength="500" placeholder="Bạn đang cần tìm gì?" autocomplete="off" required>
      <button type="submit" aria-label="Gửi câu hỏi">➤</button>
    </form>
  </section>
  <button class="shop-chatbot-trigger" type="button" data-chat-open aria-expanded="false"><span aria-hidden="true">✦</span><span>Hỗ trợ</span></button>
</div>
