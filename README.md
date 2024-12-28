# Hệ thống Quản lý Câu lạc bộ HSTV

Hệ thống quản lý câu lạc bộ với đầy đủ chức năng từ quản lý thành viên, hoạt động, tài chính đến báo cáo.

## Yêu cầu hệ thống

- PHP 7.4+
- MySQL
- Composer
- XAMPP (Apache + MySQL)

## Cài đặt

1. Clone repository:
```bash
git clone [repository-url]
```

2. Cài đặt dependencies:
```bash
composer install
```

3. Import cơ sở dữ liệu:
- Tạo database mới tên 'clb_hstv'
- Import file csdl.sql vào database vừa tạo

4. Cấu hình:
- Chỉnh sửa thông tin kết nối database trong file config/database.php
- Cấu hình email trong file forgot-password.php (nếu sử dụng chức năng quên mật khẩu)

5. Phân quyền:
- Đảm bảo thư mục uploads có quyền ghi
- Tài khoản admin mặc định:
  - Username: admin
  - Password: admin123

## Tính năng

- Quản lý người dùng
- Quản lý hoạt động
- Quản lý tài chính
- Quản lý tài liệu
- Quản lý tin tức
- Quản lý nhiệm vụ
- Báo cáo và thống kê
- Phân quyền người dùng

## Công nghệ sử dụng

- PHP 7.4+
- MySQL
- HTML5
- TailwindCSS + Flowbite
- jQuery
- AJAX

## Bảo mật

- Mã hóa mật khẩu
- Chống SQL Injection
- Xác thực và phân quyền
- Kiểm tra dữ liệu đầu vào
- Log hoạt động người dùng

## Tác giả

[Tên tác giả]

## License

MIT
