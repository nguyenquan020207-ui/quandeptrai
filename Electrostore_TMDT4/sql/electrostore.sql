CREATE DATABASE IF NOT EXISTS electrostore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE electrostore;
CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('customer','admin','staff_products','staff_orders','staff_support') NOT NULL DEFAULT 'customer',
 phone VARCHAR(30) NOT NULL DEFAULT '',
 birthday DATE NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff_attendance (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 ended_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_staff_attendance_user_started(user_id,started_at),
 INDEX idx_staff_attendance_open(user_id,ended_at),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS staff_payroll_adjustments (
 user_id INT PRIMARY KEY,
 adjustment_hours DECIMAL(10,2) NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS user_addresses (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 street VARCHAR(500) NOT NULL,
 zip VARCHAR(30) NOT NULL DEFAULT '',
 city VARCHAR(120) NOT NULL DEFAULT '',
 country VARCHAR(120) NOT NULL DEFAULT 'Việt Nam',
 is_default TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(user_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS categories (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 slug VARCHAR(140) NOT NULL UNIQUE,
 description VARCHAR(255) NOT NULL DEFAULT '',
 icon VARCHAR(16) NOT NULL DEFAULT '📦',
 active TINYINT(1) NOT NULL DEFAULT 1,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS brands (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 website VARCHAR(255) NOT NULL DEFAULT '',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 sku VARCHAR(30) NOT NULL UNIQUE,
 name VARCHAR(190) NOT NULL,
 brand VARCHAR(100) NOT NULL DEFAULT '',
 category_id INT NOT NULL,
 list_price DECIMAL(12,0) NOT NULL,
 sale_price DECIMAL(12,0) NOT NULL,
 description TEXT NOT NULL,
 specs VARCHAR(255) NOT NULL DEFAULT '',
 image_url VARCHAR(500) NOT NULL DEFAULT '',
 stock INT NOT NULL DEFAULT 0,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(category_id) REFERENCES categories(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 total DECIMAL(12,0) NOT NULL,
 shipping_fee DECIMAL(12,0) NOT NULL DEFAULT 0,
 payment_method VARCHAR(40) NOT NULL,
 shipping_address VARCHAR(500) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 status ENUM('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL,
 product_id INT NOT NULL,
 product_name VARCHAR(190) NOT NULL,
 quantity INT NOT NULL,
 unit_price DECIMAL(12,0) NOT NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS contacts (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL,
 message TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS reviews (
 id INT AUTO_INCREMENT PRIMARY KEY,
 product_id INT NOT NULL,
 user_id INT NOT NULL,
 stars TINYINT NOT NULL,
 comment TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_review_user_product(user_id,product_id),
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
INSERT INTO categories(name,slug,description,icon) VALUES
('Laptop','laptop','Máy tính xách tay cho học tập, làm việc và sáng tạo','💻'),
('Điện thoại','dien-thoai','Smartphone chính hãng, camera đẹp, pin bền','📱'),
('Máy tính bảng','may-tinh-bang','Tablet giải trí, ghi chú và làm việc di động','📲'),
('Màn hình','man-hinh','Màn hình văn phòng, đồ họa và chơi game','🖥️'),
('Tai nghe','tai-nghe','Tai nghe chống ồn, không dây và gaming','🎧'),
('Bàn phím','ban-phim','Bàn phím cơ và bàn phím văn phòng','⌨️'),
('Chuột máy tính','chuot','Chuột công thái học và chuột gaming','🖱️'),
('Linh kiện máy tính','linh-kien','SSD, RAM và linh kiện nâng cấp','🧩'),
('Phụ kiện','phu-kien','Sạc, cáp và phụ kiện tiện dụng','🔌'),
('Thiết bị mạng','thiet-bi-mang','Router, mesh Wi-Fi và thiết bị kết nối','📡') ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), icon=VALUES(icon);
INSERT INTO products(sku,name,brand,category_id,list_price,sale_price,description,specs,featured) VALUES
('SP001','MacBook Air M3 13 inch','Apple',1,28990000,26990000,'MacBook Air M3 mỏng nhẹ cho học tập, làm việc và sáng tạo.','M3|8GB|256GB',1),
('SP002','ASUS Vivobook 15','ASUS',1,16990000,14990000,'Laptop phổ thông hiệu năng cân bằng cho công việc hằng ngày.','Core i5-13420H|16GB|512GB',1),
('SP003','Dell XPS 13','Dell',1,35990000,35990000,'Laptop cao cấp nhỏ gọn với màn hình sắc nét.','Core Ultra 7|16GB|512GB',0),
('SP004','iPhone 15 128GB','Apple',2,22990000,19990000,'Điện thoại Apple camera đẹp, hiệu năng ổn định.','A16 Bionic|6GB|128GB',1),
('SP005','Samsung Galaxy S24','Samsung',2,22990000,18990000,'Điện thoại Android cao cấp với màn hình sáng và camera linh hoạt.','Snapdragon 8 Gen 3|8GB|256GB',1),
('SP006','Xiaomi 14T','Xiaomi',2,13990000,11990000,'Smartphone hiệu năng mạnh trong phân khúc.','Dimensity 8300|12GB|256GB',0),
('SP007','iPad Gen 10','Apple',3,11990000,10490000,'Máy tính bảng phục vụ học tập và giải trí.','10.9 inch|A14 Bionic|64GB',1),
('SP008','Galaxy Tab S9 FE','Samsung',3,10990000,9490000,'Tablet linh hoạt cho ghi chú, xem nội dung và làm việc.','10.9 inch|Exynos 1380|128GB',0),
('SP009','LG UltraGear 27 inch','LG',4,6990000,5990000,'Màn hình gaming độ phân giải cao và tần số quét nhanh.','27 inch|2560x1440|165Hz',0),
('SP010','Dell UltraSharp U2723QE','Dell',4,13990000,12490000,'Màn hình 4K cho công việc và sáng tạo nội dung.','27 inch|3840x2160|60Hz',0),
('SP011','Sony WH-1000XM5','Sony',5,8490000,6990000,'Tai nghe chống ồn chủ động với thời lượng pin dài.','Chụp tai|Bluetooth 5.2|30 giờ',1),
('SP012','AirPods Pro 2','Apple',5,6190000,5690000,'Tai nghe không dây gọn nhẹ, chống ồn chủ động.','Nhét tai|Bluetooth 5.3|6 giờ',0),
('SP013','Keychron K2','Keychron',6,2290000,1990000,'Bàn phím cơ gọn cho góc làm việc hiện đại.','Cơ 75%|Bluetooth/USB-C|ANSI',0),
('SP014','Logitech MX Keys','Logitech',6,2790000,2790000,'Bàn phím văn phòng gõ êm và kết nối nhiều thiết bị.','Màng mỏng|Bluetooth/USB|Fullsize',0),
('SP015','Logitech MX Master 3S','Logitech',7,2490000,2190000,'Chuột không dây công thái học cho năng suất cao.','Không dây|8000 DPI|70 ngày',1),
('SP016','Razer DeathAdder V3','Razer',7,1990000,1590000,'Chuột gaming nhẹ với cảm biến độ chính xác cao.','Có dây|26000 DPI|Không dùng pin',0),
('SP017','SSD Samsung 990 Pro 1TB','Samsung',8,3990000,3290000,'Ổ SSD NVMe tốc độ cao cho máy tính tương thích.','SSD NVMe|1TB|PCIe 4.0',1),
('SP018','RAM Kingston Fury 16GB','Kingston',8,1290000,990000,'Bộ nhớ RAM cho máy tính để bàn tương thích.','RAM DDR5|16GB|5600MHz',0),
('SP019','Sạc nhanh Anker 65W','Anker',9,890000,690000,'Củ sạc nhanh nhiều cổng cho thiết bị điện tử.','65W|2 USB-C, 1 USB-A|Củ sạc',0),
('SP020','Router TP-Link Archer AX55','TP-Link',10,1790000,1490000,'Router Wi-Fi 6 cho mạng gia đình ổn định.','Wi-Fi 6|3000Mbps|2.4/5GHz',0) ON DUPLICATE KEY UPDATE name=VALUES(name), brand=VALUES(brand), category_id=VALUES(category_id), list_price=VALUES(list_price), sale_price=VALUES(sale_price), description=VALUES(description), specs=VALUES(specs), featured=VALUES(featured);

-- Original Electrostore product images, stored per product SKU.
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSY6pA-GwtsHCEt52Rq3mnCzbcw8sQHSn8NFA--jtaWpQ&s=10' WHERE sku='SP001';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQsLwj7p6kf2uMugh9tKfGR9JM6KTN3uWQ0I3WO7Bf-PQ&s' WHERE sku='SP002';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQPzZloMHIVkP_VNbhoQWFOw6PeMVTeSuOSqWKjAZmo0A&s=10' WHERE sku='SP003';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR4--NnsG7arMvdABiKfxTssR7MGs641Mn55Trm7v94Tg&s=10' WHERE sku='SP004';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTDVT7CZJ-0gtJi-cEAOyejVqWT-qRsCZXZ6Uk-l-UGHA&s=10' WHERE sku='SP005';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRTT788rhv0x5NxnmJxi-_eYUaxc9JlZ_R9PO95kiNwXA&s=10' WHERE sku='SP006';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRXko_pBcBKpQlOQDfWlNFUu0Eqvyf7_Fdl-UFvQ9WdsA&s=10' WHERE sku='SP007';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRvqXDoaC3bkKO9NkaEoN7-lzoCoiSdzccXJjiX_U7a1Q&s=10' WHERE sku='SP008';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQwEHNwR9_HIcYYDcID8hfSzWJVLDg8sgKiG5biFTxR4Q&s=10' WHERE sku='SP009';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRGoQA-pITgMtG8w79Dzr8ngU_4mJv2cYuxfWxx_4HO0Q&s=10' WHERE sku='SP010';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSNnomutReNnaRNv2IY0iwAafTtNSAz0Om78ICFiGDSDA&s=10' WHERE sku='SP011';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQMzkPQ9TEg8U0SnkaqTDw7P98ZCQ3NHqeBl7J2bdKCoQ&s=10' WHERE sku='SP012';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRMDoEmTNwBkipc6MyOTk8vdy_trGBxh-VWgx389xiLbg&s' WHERE sku='SP013';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQJaKvTpFUY92D1HuZPKx3Y5_UMu04HpojcVny2saqpFg&s=10' WHERE sku='SP014';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRWH2ZSX-F_YndrkGvlM3pnO-ehK1JuAVF4ZeiAG8MfAQ&s=10' WHERE sku='SP015';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTFTG1boeskfYqLXPRKFhgJj7pLcgTJGayUiPKAFURlEw&s=10' WHERE sku='SP016';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRF2spGGuPy-B_Yn0vs2ivcXTMLk0fIghf8BmuZv5N5Tg&s' WHERE sku='SP017';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTmXwHfgrItTnFl8M7pBkUZWtoKqigSiTF-qNOeG-WvTg&s=10' WHERE sku='SP018';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQrtqqNZT1CZKbUlH1c8dZOH7wjlediuvDBPqfAq86WAQ&s=10' WHERE sku='SP019';
UPDATE products SET image_url='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS0xZQCn-TN1t35RTmKYw6_HE54gL3UB8paADV5k74gjw&s=10' WHERE sku='SP020';


