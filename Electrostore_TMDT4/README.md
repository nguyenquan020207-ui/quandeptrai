# Electro Store - PHP/MySQL MVC theo cấu trúc tmdt4

## Cài đặt trên XAMPP
1. Giải nén thư mục này vào `C:\xampp\htdocs\Electrostore_TMDT4`.
2. Mở XAMPP Control Panel, bật **Apache** và **MySQL**.
3. Mở `http://localhost/phpmyadmin/`.
4. Chọn **Import**, tải `sql/electrostore.sql` lên và bấm **Import/Go**. File tạo database `electrostore`, các bảng, 10 danh mục và 20 sản phẩm mẫu. Schema dùng InnoDB và UTF-8.
5. Truy cập `http://localhost/Electrostore_TMDT4/`.

Mặc định XAMPP dùng MySQL `localhost`, user `root`, mật khẩu trống. Nếu máy bạn đặt mật khẩu khác, chỉnh `config/database.php` hoặc cấu hình các biến `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`.

## Phân quyền khách hàng và quản trị viên
- Khách đăng ký trên website được lưu với `role = customer` mặc định. Khách có thể mua hàng, xem đơn của mình và gửi đánh giá; không được vào trang quản trị.
- Quản trị viên là tài khoản đã đăng ký nhưng được cấp `role = admin` trong cơ sở dữ liệu. Chỉ admin thấy liên kết **Quản trị** và truy cập được `admin/index.php`; máy chủ kiểm tra quyền trước khi tải trang.

Để tạo admin lần đầu:
1. Mở website, đăng ký tài khoản với email bạn muốn làm admin.
2. Trong phpMyAdmin chọn database `electrostore` > tab **SQL**.
3. Mở `sql/phan_quyen_admin.sql`, sửa email `admin@electrostore.local` thành email bạn vừa đăng ký, rồi dán nội dung vào tab SQL và chạy.
4. Xác nhận cột `role` trả về `admin`; đăng xuất rồi đăng nhập lại. Liên kết **Quản trị** sẽ xuất hiện.

Để hạ lại thành khách, chạy trong phpMyAdmin (thay email):
`UPDATE users SET role = 'customer' WHERE email = 'email-cua-ban';`

Không thể chọn vai trò admin ở form đăng ký; chỉ người quản lý database mới cấp được quyền. Đăng nhập thường chỉ thay đổi quyền sau khi đăng nhập lại.

## Lưu ý dữ liệu
Có thể import lại `electrostore.sql` mà không xóa tài khoản, đơn hàng hoặc bảng hiện có. Dữ liệu danh mục/sản phẩm mẫu chỉ được chèn nếu chưa có khóa trùng. Cơ sở dữ liệu do dự án tạo tên `electrostore`.

## Chức năng
Trang chủ, danh mục, lọc/tìm kiếm, chi tiết sản phẩm, tài khoản, giỏ hàng session, đặt hàng và lịch sử đơn bằng MySQL, liên hệ, đánh giá và API JSON. Trang quản trị có tổng quan doanh thu/đơn hàng/sản phẩm/khách hàng, biểu đồ doanh thu và số đơn đã giao theo 7 ngày/30 ngày/12 tháng; quản lý, tìm kiếm, lọc và ẩn/hiện sản phẩm/danh mục; cập nhật trạng thái đơn; tra cứu khách hàng; xem đánh giá và tin nhắn liên hệ. Giao diện admin được thiết kế theo hướng bảng điều khiển tài nguyên như Filament, nhưng vẫn chạy trên PHP/MySQL hiện có. Mật khẩu dùng `password_hash()`/`password_verify()`, truy vấn dùng prepared statements và biểu mẫu có CSRF token.

Chạy website bằng Apache/PHP, không mở trực tiếp bằng `file:///`.
