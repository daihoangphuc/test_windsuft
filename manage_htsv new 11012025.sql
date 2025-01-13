-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th1 11, 2025 lúc 08:34 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanlyclb`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chucvu`
--

CREATE TABLE `chucvu` (
  `Id` int(11) NOT NULL,
  `TenChucVu` varchar(50) NOT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chucvu`
--

INSERT INTO `chucvu` (`Id`, `TenChucVu`, `NgayTao`) VALUES
(1, 'Chủ nhiệm', '2024-12-25 10:28:14'),
(2, 'Phó chủ nhiệm', '2024-12-25 10:28:14'),
(3, 'Thư ký', '2024-12-25 10:28:14'),
(4, 'Thành viên', '2024-12-25 10:28:14'),
(11, 'Ủy viên', '2024-12-29 19:37:20'),
(12, 'Cố vấn', '2024-12-29 19:40:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhsachdangky`
--

CREATE TABLE `danhsachdangky` (
  `Id` int(11) NOT NULL,
  `NguoiDungId` int(11) DEFAULT NULL,
  `HoatDongId` int(11) DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT 1,
  `ThoiGianDangKy` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danhsachdangky`
--

INSERT INTO `danhsachdangky` (`Id`, `NguoiDungId`, `HoatDongId`, `TrangThai`, `ThoiGianDangKy`) VALUES
(5, 7, 3, 1, '2024-12-28 18:29:55'),
(6, 10, 3, 1, '2024-12-28 21:51:31'),
(30, 7, 4, 1, '2025-01-03 11:13:07'),
(31, 7, 5, 1, '2025-01-05 18:05:39'),
(33, 6, 6, 1, '2025-01-05 18:36:01'),
(34, 7, 20, 1, '2025-01-06 15:32:24'),
(35, 7, 21, 1, '2025-01-06 16:15:27'),
(36, 7, 23, 1, '2025-01-09 20:31:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhsachthamgia`
--

CREATE TABLE `danhsachthamgia` (
  `Id` int(11) NOT NULL,
  `NguoiDungId` int(11) DEFAULT NULL,
  `HoatDongId` int(11) DEFAULT NULL,
  `DiemDanhLuc` datetime DEFAULT current_timestamp(),
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danhsachthamgia`
--

INSERT INTO `danhsachthamgia` (`Id`, `NguoiDungId`, `HoatDongId`, `DiemDanhLuc`, `TrangThai`) VALUES
(6, 7, 3, '2024-12-28 21:58:43', 1),
(8, 10, 3, '2024-12-29 09:56:57', 1),
(9, 7, 5, '2025-01-05 18:31:32', 1),
(10, 6, 6, '2025-01-06 15:33:25', 0),
(11, 7, 4, '2025-01-06 15:33:25', 0),
(12, 7, 20, '2025-01-06 15:43:49', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoatdong`
--

CREATE TABLE `hoatdong` (
  `Id` int(11) NOT NULL,
  `TenHoatDong` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `DiaDiem` varchar(255) DEFAULT NULL,
  `ToaDo` varchar(50) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT 0,
  `TrangThai` tinyint(4) DEFAULT 0 COMMENT '0: Sắp diễn ra, 1 Đang diễn ra; 2: Đã kết thúc',
  `NgayTao` datetime DEFAULT current_timestamp(),
  `NguoiTaoId` int(11) NOT NULL,
  `DuongDanMinhChung` text DEFAULT NULL COMMENT 'đường dẫn file minh chứng (danh sách tham gia hoạt động có mộc đỏ)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hoatdong`
--

INSERT INTO `hoatdong` (`Id`, `TenHoatDong`, `MoTa`, `NgayBatDau`, `NgayKetThuc`, `DiaDiem`, `ToaDo`, `SoLuong`, `TrangThai`, `NgayTao`, `NguoiTaoId`, `DuongDanMinhChung`) VALUES
(2, 'Vệ sinh C5', 'Vệ sinh C5', '2024-12-29 14:27:00', '2024-12-30 14:27:00', 'Đại học trà vinh', '10.666, 9.33', 50, 2, '2024-12-28 14:27:44', 1, 'uploads/activities/minhchung/evidence_67701179747730.64089827.docx'),
(3, 'Tham gia chủ nhật xanh', 'Tham gia chủ nhật xanh', '2024-12-29 14:30:00', '2024-12-30 16:00:00', 'Đại học trà vinh', '9.920012,106.347005', 200, 2, '2024-12-28 14:41:38', 1, 'uploads/activities/minhchung/evidence_6770104d4a9340.06584386.docx'),
(4, 'Hoạt động chào đón Tết Nguyên Đán 24/01/2025', 'Hoạt động chào đón Tết Nguyên Đán 24/01/2025', '2025-01-04 23:30:00', '2025-01-04 15:00:00', 'Đại học Trà Vinh', '9.961303,106.224731', 60, 2, '2025-01-01 21:29:34', 1, NULL),
(5, 'Cỗ vũ tuyển Việt Nam chung kết AFF cúp 2024', 'Cỗ vũ tuyển Việt Nam chung kết AFF cúp 2024', '2025-01-05 18:00:00', '2025-01-05 18:37:00', 'Trường DHTV', '9.9352,106.3377', 1000, 2, '2025-01-05 17:53:01', 1, NULL),
(6, 'Hỗ trợ trực phòng ban câu lạc bộ 06/012025', 'Hỗ trợ trực phòng ban câu lạc bộ 06/012025', '2025-01-06 11:00:00', '2025-01-06 13:00:00', 'Phong C61104', '9.9352,106.3377', 5, 2, '2025-01-05 18:35:13', 1, 'uploads/activities/minhchung/evidence_677f8b35b15912.75833535.pdf'),
(20, 'Vệ Sinh Khuôn Viên C5 ', 'Vệ Sinh Khuôn Viên C5 vào lúc chiều 07/01/2025', '2025-01-06 07:00:00', '2025-01-06 15:45:00', 'Trường DHTV', '9.961303,106.224731', 50, 2, '2025-01-06 11:39:37', 1, NULL),
(21, 'Chạy bộ từ thiện', 'Hoạt động chạy bộ gây quỹ từ thiện cho các trẻ em nghèo', '2025-02-10 06:00:00', '2025-02-10 09:00:00', 'Công viên trung tâm', '21.0285, 105.8542', 100, 0, '2025-01-06 14:14:11', 1, ''),
(22, 'Hội thảo kỹ năng lãnh đạo', 'Chương trình hội thảo về kỹ năng lãnh đạo và phát triển cá nhân', '2025-03-15 09:00:00', '2025-03-15 17:00:00', 'Hội trường D5', '21.0123, 105.8421', 50, 0, '2025-01-06 14:14:11', 2, ''),
(23, 'Lễ kỷ niệm CLB', 'Lễ kỷ niệm 5 năm thành lập câu lạc bộ', '2025-01-20 19:00:00', '2025-01-20 22:00:00', 'Nhà hàng XYZ', '21.0456, 105.8247', 200, 0, '2025-01-06 14:14:11', 3, ''),
(24, 'Đào tạo kỹ năng mềm', 'Khóa đào tạo về các kỹ năng mềm cho sinh viên', '2025-04-01 08:00:00', '2025-04-01 12:00:00', 'Trường Đại học ABC', '21.0111, 105.8399', 150, 0, '2025-01-06 14:14:11', 4, ''),
(25, 'Hoạt động Xuân Yêu Thương ', 'Hoạt động Xuân Yêu Thương ', '2025-01-10 18:00:00', '2025-01-10 21:00:00', 'Trường DHTV', '9.9352,106.3377', 300, 1, '2025-01-10 17:43:30', 1, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoatruong`
--

CREATE TABLE `khoatruong` (
  `Id` int(11) NOT NULL,
  `TenKhoaTruong` varchar(255) NOT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khoatruong`
--

INSERT INTO `khoatruong` (`Id`, `TenKhoaTruong`, `NgayTao`) VALUES
(1, 'Khoa Công nghệ Thông tin', '2024-12-25 10:28:14'),
(2, 'Khoa Kinh tế', '2024-12-25 10:28:14'),
(3, 'Khoa Ngoại ngữ', '2024-12-25 10:28:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `log`
--

CREATE TABLE `log` (
  `Id` int(11) NOT NULL,
  `IP` varchar(45) DEFAULT NULL,
  `NguoiDung` varchar(50) DEFAULT NULL,
  `HanhDong` varchar(255) DEFAULT NULL,
  `KetQua` varchar(50) DEFAULT NULL,
  `ChiTiet` text DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `log`
--

INSERT INTO `log` (`Id`, `IP`, `NguoiDung`, `HanhDong`, `KetQua`, `ChiTiet`, `NgayTao`) VALUES
(14, '::1', 'nguyendaihoangphuc961', 'Đăng xuất', 'Thành công', 'Người dùng đăng xuất khỏi hệ thống', '2025-01-01 21:27:12'),
(15, '::1', 'phucndh4', 'Đăng nhập', 'Thành công', 'Đăng nhập thành công vào hệ thống', '2025-01-01 21:27:20'),
(16, '::1', 'admin', 'Đăng nhập', 'Thành công', 'Đăng nhập thành công vào hệ thống', '2025-01-01 21:28:15'),
(17, '::1', 'admin', 'Cập nhật hoạt động', 'Thành công', 'Cập nhật hoạt động ID 4: Hoạt động chào đón tết dương lịch 1/1/2024', '2025-01-01 21:57:47'),
(18, '::1', 'System', 'Cập nhật nhiệm vụ quá hạn', 'Thành công', 'Cập nhật trạng thái các nhiệm vụ quá hạn', '2025-01-01 22:07:14'),
(19, '::1', 'System', 'Cập nhật nhiệm vụ quá hạn', 'Thành công', 'Cập nhật trạng thái các nhiệm vụ quá hạn', '2025-01-01 22:08:02'),
(20, '::1', 'System', 'Cập nhật nhiệm vụ quá hạn', 'Thành công', 'Cập nhật trạng thái quá hạn cho các nhiệm vụ chưa hoàn thành', '2025-01-01 22:11:32'),
(21, '::1', 'System', 'Cập nhật nhiệm vụ quá hạn', 'Thành công', 'Cập nhật trạng thái quá hạn cho các nhiệm vụ chưa hoàn thành', '2025-01-01 22:11:38'),
(22, '::1', 'phucndh4', 'Cập nhật trạng thái nhiệm vụ', 'Thành công', 'Cập nhật trạng thái nhiệm vụ ID 2 từ Đang thực hiện sang ', '2025-01-01 22:11:38'),
(23, '::1', 'System', 'Cập nhật nhiệm vụ quá hạn', 'Thành công', 'Cập nhật trạng thái quá hạn cho các nhiệm vụ chưa hoàn thành', '2025-01-01 22:11:38'),
(24, '::1', 'System', 'Cập nhật nhiệm vụ quá hạn', 'Thành công', 'Cập nhật trạng thái quá hạn cho các nhiệm vụ chưa hoàn thành', '2025-01-01 22:11:44'),
(25, '::1', '6', 'Yêu cầu khôi phục mật khẩu', 'Thành công', 'Đã gửi email khôi phục mật khẩu', '2025-01-02 10:32:35'),
(26, '::1', '6', 'Yêu cầu khôi phục mật khẩu', 'Thành công', 'Đã gửi email khôi phục mật khẩu', '2025-01-02 10:35:21'),
(27, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 18', '2025-01-03 12:14:39'),
(28, '::1', '1', 'Thêm tài liệu', 'Thành công', 'Đã thêm tài liệu: Nội quy CLB Hành trình sinh viên', '2025-01-09 15:45:06'),
(29, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 18', '2025-01-09 19:51:39'),
(30, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 18', '2025-01-09 19:51:44'),
(31, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 18', '2025-01-09 19:52:01'),
(32, '::1', '1', 'Thêm tài liệu', 'Thành công', 'Đã thêm tài liệu: Báo cáo thu chi Tháng 12/2024', '2025-01-09 19:52:30'),
(33, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 19:52:38'),
(34, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 19:52:48'),
(35, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 19:53:58'),
(36, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 19:57:42'),
(37, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 20:00:03'),
(38, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 20:00:09'),
(39, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 20:00:18'),
(40, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 20', '2025-01-09 20:03:55'),
(41, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 20:04:12'),
(42, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 20:14:34'),
(43, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 20', '2025-01-09 20:16:03'),
(44, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 20:18:12'),
(45, '::1', '1', 'Cập nhật tài liệu', 'Thành công', 'Đã cập nhật tài liệu ID: 19', '2025-01-09 20:18:19'),
(46, '::1', 'admin', 'Import Users', 'Error', 'Không import được người dùng nào\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=5\n\nErrors:\nDòng 2: Lớp \'5\' không tồn tại trong hệ thống\nDòng 3: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)\nDòng 4: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)\nDòng 5: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)\nDòng 6: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)', '2025-01-09 21:25:44'),
(47, '::1', 'admin', 'Import Users', 'Error', 'Không import được người dùng nào\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=5\n\nErrors:\nDòng 2: Lớp \'5\' không tồn tại trong hệ thống\nDòng 3: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)\nDòng 4: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)\nDòng 5: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)\nDòng 6: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)', '2025-01-09 21:28:10'),
(48, '::1', 'admin', 'Import Users', 'Success', 'Import thành công 1 người dùng\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=DA21TTB', '2025-01-09 21:30:21'),
(49, '::1', 'admin', 'Import Users', 'Success', 'Import thành công 1 người dùng\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=DA21TTB', '2025-01-09 21:31:54'),
(50, '::1', 'admin', 'Import Users', 'Success', 'Import thành công 1 người dùng\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=DA21TTB', '2025-01-09 21:33:47'),
(51, '::1', 'admin', 'Import Users', 'Error', 'Không import được người dùng nào\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=DA21TTB\n\nErrors:\nDòng 2: Người dùng đã tồn tại (trùng tên đăng nhập, email hoặc mã sinh viên)', '2025-01-09 21:34:16'),
(52, '::1', 'admin', 'Import Users', 'Error', 'Không import được người dùng nào\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=DA21TTB\n\nErrors:\nDòng 2: Người dùng đã tồn tại (trùng tên đăng nhập, email hoặc mã sinh viên)', '2025-01-09 21:39:02'),
(53, '::1', 'admin', 'Import Users', 'Error', 'Không import được người dùng nào\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=DA21TTB\n\nErrors:\nDòng 2: Người dùng đã tồn tại (trùng tên đăng nhập, email hoặc mã sinh viên)', '2025-01-09 21:39:24'),
(54, '::1', 'admin', 'Import Users', 'Success', 'Import thành công 1 người dùng\n\nProcessed rows:\nDòng 2: MaSV=110121365, HoTen=Nguyễn Nhật Nam, Email=phucndh123456@gmail.com, Lop=DA21TTB', '2025-01-10 19:46:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lophoc`
--

CREATE TABLE `lophoc` (
  `Id` int(11) NOT NULL,
  `TenLop` varchar(50) NOT NULL,
  `KhoaTruongId` int(11) DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lophoc`
--

INSERT INTO `lophoc` (`Id`, `TenLop`, `KhoaTruongId`, `NgayTao`) VALUES
(1, 'DA21TTB', 1, '2024-12-25 10:28:14'),
(2, 'DA21TTA', 1, '2024-12-25 10:28:14'),
(3, 'DA23KTA', 2, '2024-12-25 10:28:14'),
(4, 'DA21CNOTB', 2, '2024-12-25 10:28:14'),
(5, 'DA21NNA', 3, '2024-12-25 10:28:14'),
(6, 'DA23NNB', 3, '2024-12-25 10:28:14'),
(10, 'DA22TTD', 1, '2025-01-09 16:48:21');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoidung`
--

CREATE TABLE `nguoidung` (
  `Id` int(11) NOT NULL,
  `MaSinhVien` varchar(20) DEFAULT NULL,
  `TenDangNhap` varchar(50) NOT NULL,
  `MatKhauHash` varchar(255) NOT NULL,
  `HoTen` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `anhdaidien` text NOT NULL,
  `GioiTinh` tinyint(4) DEFAULT 1,
  `NgaySinh` date DEFAULT NULL,
  `ChucVuId` int(11) DEFAULT NULL,
  `LopHocId` int(11) DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp(),
  `TrangThai` tinyint(4) DEFAULT 1,
  `VaiTroId` int(11) NOT NULL COMMENT '1 admin, 2 member',
  `lantruycapcuoi` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoidung`
--

INSERT INTO `nguoidung` (`Id`, `MaSinhVien`, `TenDangNhap`, `MatKhauHash`, `HoTen`, `Email`, `anhdaidien`, `GioiTinh`, `NgaySinh`, `ChucVuId`, `LopHocId`, `NgayTao`, `TrangThai`, `VaiTroId`, `lantruycapcuoi`, `reset_token`, `reset_expires`) VALUES
(1, '0', 'admin', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Administrator', 'nguyendaihoangphuc24@gmail.com', '../uploads/users/67811401dd9367.09582063.jpg', 1, '2000-11-19', 4, 1, '2024-12-25 10:28:14', 1, 1, '2025-01-10 19:24:19', NULL, NULL),
(6, '110121335', 'nguyendaihoangphuc1911', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Nguyễn Hữu Luân', 'eduboostvn@gmail.com', '../uploads/users/677f88df57eea.png', 1, '2000-02-16', 1, 1, '2024-12-25 12:37:02', 1, 2, '2025-01-09 15:35:41', NULL, NULL),
(7, '110121087', 'phucndh4', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Nguyễn Đại Hoàng Phúc', 'nguyendaihoangphuc1911@gmail.com', '../uploads/users/67811414973eb.png', 1, '2003-11-19', 2, 1, '2024-12-25 17:27:11', 1, 2, '2025-01-10 19:18:01', NULL, NULL),
(10, '11012122', 'phucndh1', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Đỗ Cao Trí', 'nguyendaihoangphuc2411@gmmail.com', '../uploads/users/avatar_676ff65019036.png', 1, '2003-02-11', 4, 1, '2024-12-28 19:51:51', 1, 2, '2024-12-29 10:06:19', NULL, NULL),
(16, '110121323', 'userphuc', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Nguyễn văn C', 'userphuc@gmail.com', '../uploads/users/677147756448c1.92114892.png', 1, '2000-11-19', 11, 1, '2024-12-29 19:26:16', 0, 2, '2024-12-29 19:58:16', NULL, NULL),
(17, '110121569', 'userphuc1', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Nguyễn Thị Bình', 'userphuc1@gmail.com', '../uploads/users/tvupng.png', 1, '0000-00-00', 4, 1, '2024-12-29 19:34:20', 0, 2, NULL, NULL, NULL),
(20, '110121336', 'nguyendaihoangphuc961', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Đỗ Thành Ý', 'nguyendaihoangphuctv@gmail.com', '../uploads/users/677fe3374dc8f.png', 1, '2000-11-19', 12, 1, '2025-01-01 20:59:35', 1, 2, '2025-01-09 21:15:06', NULL, NULL),
(26, '110121365', 'phucndh123456', '$2y$10$Qvq33AlfwRSnEXkrrGrIlevd4Msl52PSilwCCmgE2YOkXPPoCs9Wu', 'Nguyễn Nhật Nam', 'phucndh123456@gmail.com', '../uploads/users/tvupng.png', 1, '2003-02-27', 4, 1, '2025-01-10 13:46:46', 1, 2, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhiemvu`
--

CREATE TABLE `nhiemvu` (
  `Id` int(11) NOT NULL,
  `TenNhiemVu` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `TrangThai` tinyint(4) DEFAULT 0 COMMENT '0: Chưa bắt đầu; 1: Đang thực hiện; 2: Hoàn thành; 3: Quá hạn',
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhiemvu`
--

INSERT INTO `nhiemvu` (`Id`, `TenNhiemVu`, `MoTa`, `NgayBatDau`, `NgayKetThuc`, `TrangThai`, `NgayTao`) VALUES
(1, 'Nhiệm vụ a', 'Nhiệm vụ a', '2024-12-30 00:00:00', '2024-12-31 00:00:00', 2, '2024-12-27 22:41:55'),
(2, 'Liên hệ phòng máy tổ chức đại hội', 'Liên hệ phòng máy tổ chức đại hội', '2024-12-29 21:24:00', '2025-01-02 21:24:00', 2, '2024-12-28 21:25:03'),
(3, 'Chuẩn bị cho hoạt động Xuân tình nguyện', 'Chuẩn bị cho hoạt động Xuân tình nguyện ', '2025-01-20 06:00:00', '2025-01-21 06:00:00', 0, '2025-01-09 15:46:18'),
(4, 'Lập danh sách điểm danh hoạt động vệ sinh ngày', 'Lập danh sách điểm danh hoạt động vệ sinh ngày 12/01/2025', '2025-01-16 06:00:00', '2025-01-17 06:00:00', 0, '2025-01-09 19:35:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancongnhiemvu`
--

CREATE TABLE `phancongnhiemvu` (
  `Id` int(11) NOT NULL,
  `NguoiDungId` int(11) DEFAULT NULL,
  `NhiemVuId` int(11) DEFAULT NULL,
  `NguoiPhanCong` varchar(50) NOT NULL,
  `NgayPhanCong` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phancongnhiemvu`
--

INSERT INTO `phancongnhiemvu` (`Id`, `NguoiDungId`, `NhiemVuId`, `NguoiPhanCong`, `NgayPhanCong`) VALUES
(11, 7, 2, '1', '2024-12-29 08:21:50'),
(12, 7, 1, '1', '2025-01-06 15:00:11'),
(13, 6, 3, '1', '2025-01-09 15:46:25'),
(16, 10, 4, '1', '2025-01-09 21:58:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phanquyentailieu`
--

CREATE TABLE `phanquyentailieu` (
  `Id` int(11) NOT NULL,
  `TaiLieuId` int(11) DEFAULT NULL,
  `VaiTroId` int(11) DEFAULT NULL,
  `Quyen` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phanquyentailieu`
--

INSERT INTO `phanquyentailieu` (`Id`, `TaiLieuId`, `VaiTroId`, `Quyen`) VALUES
(1, 19, 1, 1),
(2, 19, 2, 1),
(3, 20, 1, 1),
(4, 20, 2, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taichinh`
--

CREATE TABLE `taichinh` (
  `Id` int(11) NOT NULL,
  `LoaiGiaoDich` tinyint(4) NOT NULL,
  `SoTien` bigint(20) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `NgayGiaoDich` datetime NOT NULL,
  `NguoiDungId` int(11) DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `taichinh`
--

INSERT INTO `taichinh` (`Id`, `LoaiGiaoDich`, `SoTien`, `MoTa`, `NgayGiaoDich`, `NguoiDungId`, `NgayTao`) VALUES
(13, 0, 2000000, 'Thu tiền quỹ tháng 1', '2024-01-15 09:00:00', 1, '2024-01-15 09:00:00'),
(14, 0, 4500000, 'Thu tiền quỹ tháng 2', '2024-02-10 10:00:00', 1, '2024-02-10 10:00:00'),
(15, 0, 1500000, 'Thu tiền quỹ tháng 3', '2024-03-05 14:30:00', 1, '2024-03-05 14:30:00'),
(16, 0, 1800000, 'Thu tiền quỹ tháng 4', '2024-04-20 11:15:00', 1, '2024-04-20 11:15:00'),
(17, 0, 500000, 'Thu tiền quỹ tháng 5', '2024-05-25 08:00:00', 1, '2024-05-25 08:00:00'),
(18, 0, 2500000, 'Thu tiền quỹ tháng 6', '2024-06-18 13:30:00', 1, '2024-06-18 13:30:00'),
(19, 0, 4200000, 'Thu tiền quỹ tháng 7', '2024-07-12 09:45:00', 1, '2024-07-12 09:45:00'),
(20, 0, 1300000, 'Thu tiền quỹ tháng 8', '2024-08-22 16:00:00', 1, '2024-08-22 16:00:00'),
(21, 0, 3000000, 'Thu tiền quỹ tháng 9', '2024-09-17 14:00:00', 1, '2024-09-17 14:00:00'),
(22, 0, 1000000, 'Thu tiền quỹ tháng 10', '2024-10-02 11:00:00', 1, '2024-10-02 11:00:00'),
(24, 1, 50000, 'Chi phí tổ chức sự kiện mừng sinh nhật', '2024-12-20 14:00:00', 1, '2024-12-20 14:00:00'),
(25, 1, 750000, 'Mua thiết bị cho phòng học', '2024-11-15 10:30:00', 1, '2024-11-15 10:30:00'),
(26, 1, 500000, 'Chi phí bảo trì hệ thống máy tính', '2024-10-05 15:45:00', 1, '2024-10-05 15:45:00'),
(27, 1, 520000, 'Chi phí tổ chức workshop', '2024-09-10 09:30:00', 1, '2024-09-10 09:30:00'),
(28, 1, 150000, 'Mua quà sinh nhật cho nhân viên', '2024-08-25 11:00:00', 1, '2024-08-25 11:00:00'),
(29, 1, 1000000, 'Mua văn phòng phẩm cho trường học', '2024-07-10 10:30:00', 1, '2024-07-10 10:30:00'),
(30, 1, 60000, 'Chi phí tiếp khách cho đối tác', '2024-06-15 13:15:00', 1, '2024-06-15 13:15:00'),
(31, 1, 2000000, 'Chi phí ăn uống cho nhân viên', '2024-05-01 08:45:00', 1, '2024-05-01 08:45:00'),
(32, 1, 240000, 'Chi phí vận chuyển hàng hóa', '2024-04-12 16:00:00', 1, '2024-04-12 16:00:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tailieu`
--

CREATE TABLE `tailieu` (
  `Id` int(11) NOT NULL,
  `TenTaiLieu` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `DuongDan` varchar(255) NOT NULL,
  `LoaiTaiLieu` varchar(50) DEFAULT NULL,
  `NguoiTaoId` int(11) DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tailieu`
--

INSERT INTO `tailieu` (`Id`, `TenTaiLieu`, `MoTa`, `DuongDan`, `LoaiTaiLieu`, `NguoiTaoId`, `NgayTao`) VALUES
(18, 'Báo cáo thu chi Tháng 11/2024', 'Báo cáo thu chi Tháng 11/2024', '../../uploads/documents/6771546831f61_Expert-based Evaluation_3.docx', 'Báo cáo', 1, '2024-12-29 20:53:44'),
(19, 'Nội quy CLB Hành trình sinh viên', 'Nội quy CLB Hành trình sinh viên', '../../uploads/documents/677fcc9b5965b_1736428699.xlsx', 'Văn bản', 1, '2025-01-09 15:45:06'),
(20, 'Báo cáo thu chi Tháng 12/2024', 'Báo cáo thu chi Tháng 12/2024', '../../uploads/documents/DS_dang_ky_20250106_155126_3.xlsx', 'Báo cáo', 1, '2025-01-09 19:52:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tintuc`
--

CREATE TABLE `tintuc` (
  `Id` int(11) NOT NULL,
  `TieuDe` varchar(255) NOT NULL,
  `NoiDung` text DEFAULT NULL,
  `FileDinhKem` varchar(255) DEFAULT NULL,
  `NguoiTaoId` int(11) DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tintuc`
--

INSERT INTO `tintuc` (`Id`, `TieuDe`, `NoiDung`, `FileDinhKem`, `NguoiTaoId`, `NgayTao`) VALUES
(5, ' Số hóaBlockchainThứ sáu, 13/12/2024, 08:00 (GMT+7) TP HCM đặt mục tiêu thành điểm đến của doanh nghiệp AI, blockchain', 'Để hướng tới mục tiêu trở thành thành phố công nghệ hàng đầu, TP HCM cần được tạo thêm hành lang pháp lý cho công nghệ mới, như AI, blockchain.\r\n\r\nTại tọa đàm ngày 12/12 của Hội Truyền thông Điện tử TP HCM, các lãnh đạo thành phố chia sẻ tầm nhìn chiến lược và giải pháp nhằm đưa TP HCM trở thành điểm đến cho các doanh nghiệp trong lĩnh vực công nghệ, đặc biệt là blockchain và AI.\r\n\r\nÔng Nguyễn Ngọc Hồi, phó giám đốc Sở Thông tin và Truyền thông TP HCM. Ảnh: Bảo Lâm\r\nÔng Nguyễn Ngọc Hồi, Phó giám đốc Sở Thông tin và Truyền thông TP HCM. Ảnh: Bảo Lâm\r\n\r\nTheo ông Nguyễn Ngọc Hồi, Phó giám đốc Sở Thông tin và Truyền thông TP HCM, thành phố đang trên đường tạo ra hành lang pháp lý để khơi dậy sự phát triển của các lĩnh vực tiềm năng và công nghệ mới. \"Phải làm sao để mọi người khi nghĩ đến TP HCM là nơi tạo cơ hội đột phá trong các lĩnh vực công nghệ, điển hình là game và blockchain thời gian tới\", ông Hồi nói.\r\n\r\nĐồng quan điểm, ông Nguyễn Thanh Hòa, trưởng phòng Thông tin điện tử - Sở Thông tin và Truyền thông TP HCM, cho rằng cần cải thiện hành lang pháp lý để phát triển các xu hướng công nghệ mới. \"Chúng ta có ba trung tâm đổi mới sáng tạo trên cả nước và TP HCM là một trong số đó. Các cơ sở đang hình thành như Trung tâm Chuyển đổi số, Công nghệ Sinh học, Viện nghiên cứu VTIS, Trung tâm VIS\", ông Hòa nói.\r\n\r\nTheo ông, thành phố đang tiếp tục chuyển đổi số và thực hiện Nghị quyết 98 của chính phủ. \"Nghị quyết 98 mở ra rất nhiều tiềm năng để phát triển công nghệ blockchain thời gian tới\", ông nói thêm. Nghị quyết 98 có hiệu lực từ 1/8/2023, gồm 44 nhóm chính sách với 7 lĩnh vực, kỳ vọng mang lại nhiều lợi ích cho người dân, doanh nghiệp tại thành phố.\r\n\r\nVề phía Hội Truyền thông Điện tử TP HCM, ông Nguyễn Quý Hòa cho biết Hội đã hoạt động 34 năm và nhận thấy TP HCM luôn là đơn vị dẫn đầu mảng truyền thông số. \"Hiện nay các doanh nghiệp hoạt động trong mảng blockchain chưa hiểu hết các quy định và khung pháp lý. Vì vậy, Hội sẽ đảm nhận vai trò gắn kết và giúp doanh nghiệp hiểu rõ hơn để tận dụng quyền lợi từ các chính sách nhà nước\", ông Quý Hòa nói.\r\n\r\nSự kiện cũng công bố thành lập hai chi hội mới là Chi hội Blockchain TP HCM (HBA) và Chi hội Quảng cáo và Truyền thông số TP HCM (DACA). HBA sẽ có nhiệm vụ phát triển cộng đồng blockchain TP HCM, còn DACA được thành lập để đáp ứng nhu cầu về chiến lược truyền thông sáng tạo và quảng cáo số hóa trong nền kinh tế số.\r\n\r\nÔng Lê Thanh, đại diện HBA, đánh giá TP HCM có thể trở thành điểm đến lý tưởng cho các startup blockchain khi sở hữu nguồn nhân lực chất lượng cao, điều những ngành mới như blockchain rất cần và đang thiếu. Các công ty SkyMavis, Ninety Eight, Kyber ra đời tại đây 7-8 năm và được thế giới công nhận qua nhiều giai đoạn.\r\n\r\nTheo ông Thanh Hòa, blockchain sẽ gắn với từ khóa \"tương lai\", còn ông Quý Hòa nhận định đây là công nghệ \"không giới hạn\", giúp vận hành thành phố, giảm tải cho hạ tầng nhưng vẫn đảm bảo tính chính xác của thông tin.', '/uploads/news/tvupng_1.png', 1, '2024-12-25 20:14:00'),
(6, 'Radar quân sự 3D do kỹ sư Việt Nam phát triển', 'Radar 3D có thể xác định được độ cao của mục tiêu dưới 25 km, cự ly 360 km, do các kỹ sư Viettel phát triển, chế tạo.\r\n\r\nCác sản phẩm được trưng bày tại Triển lãm Quốc phòng ở Hà Nội ngày 19-22/12, thu hút sự chú ý lớn từ khách tham quan, với kích thước \"khổng lồ\", được thiết kế linh hoạt khi đặt cố định dưới đất hoặc gắn trên xe chuyên dụng. Điểm độc đáo của sản phẩm là khả năng cung cấp thông tin ba tọa độ, còn gọi là 3D, và do kỹ sư Việt Nam thực hiện.\r\n\r\nTrước đây, radar thế hệ cũ 2D cung cấp được thông tin hai chiều là khoảng cách và phương vị, cho phép theo dõi mục tiêu di chuyển trên một mặt phẳng. Ngoài ra, tốc độ quét chậm khiến loại radar này gặp khó khăn khi theo dõi các vũ khí mới.\r\n\r\nTrong khi đó, một số vũ khí hiện đại như UAV có kích thước nhỏ, di chuyển linh hoạt, hoạt động ở độ cao thấp và tấn công vào các mục tiêu trọng yếu. Điều này được đánh giá \"làm thay đổi các học thuyết quân sự trước đây\" và đặt ra bài toán mới về phòng không, đó là các radar có thể theo dõi nhanh và chi tiết thông số của vật thể.\r\n\r\nRadar 3D cảnh giới tầm trung được lắp trên xe (trái), trình diễn tại Triển lãm Quốc phòng 2024. Ảnh: Giang Huy\r\nRadar 3D cảnh giới tầm trung được lắp trên xe (trái), trình diễn tại Triển lãm Quốc phòng 2024. Ảnh: Giang Huy\r\n\r\nTheo đại diện đơn vị phát triển Viettel High Tech (VHT), trên thế giới, một số giải pháp radar 3D đã có, nhưng không nhiều bên sở hữu, đồng nghĩa phải mua với giá cao, phụ thuộc về công nghệ và vận hành. Năm 2018, đơn vị này nhận nhiệm vụ nghiên cứu radar 3D. Sản phẩm đầu tiên ra đời năm 2020 và Việt Nam \"hoàn toàn làm chủ công nghệ lõi\".\r\n\r\nKhác với radar 2D, radar 3D có thể xác định thêm độ cao, có tốc độ quét nhanh và hiển thị vị trí chính xác của mục tiêu trong không gian. Điều này yêu cầu về công nghệ ăng-ten phức tạp và lượng dữ liệu cần xử lý nhiều hơn.\r\n\r\nCác chuyên gia của Viettel cho biết chọn nghiên cứu công nghệ ăng-ten mảng pha quét búp sóng điện tử (beamforming), tương tự công nghệ được ứng dụng trong phát sóng 5G. Công nghệ này sử dụng các phần tử ăng-ten khác nhau hoạt động đồng bộ để điều chỉnh hướng phát và nhận sóng của radar. Cách này tạo ra độ phân giải cao hơn so với việc phát tín hiệu ra tất cả hướng của radar truyền thống.\r\n\r\n\"Rất khó làm chủ về độ chính xác và đồng bộ của các phần tử khi phát và thu, lượng dữ liệu cần xử lý cũng tăng gấp nhiều lần\", ông Trần Hoàng Việt, trưởng phòng Công nghệ ăng-ten mảng pha chủ động của VHT, cho biết.\r\n\r\nSau hai năm phát triển, những sản phẩm đầu tiên ra đời vào năm 2020 và liên tục được tối ưu, cải tiến. Đến nay, ngoài tính năng phát hiện UAV và tên lửa hành trình bay ở độ cao thấp, radar 3D của Viettel cũng được thiết kế dạng module, giúp dễ dàng tháo lắp, vận chuyển và triển khai ở nhiều địa hình trận địa, bao gồm khu vực đồi núi hiểm trở.\r\n\r\nTại Triển lãm Quốc phòng năm nay, hàng loạt radar 3D được trình diễn, bao gồm đài radar cảnh giới tầm trung băng tần S để phát hiện, định vị các mục tiêu bay ở độ cao tầm trung, cự ly tầm trung; radar chiến thuật 3D băng S tầm gần; đài radar 3D chiến thuật băng L...\r\n\r\nTrong đó, đài radar 3D cảnh giới tầm trung băng tần S có khả năng phát hiện, định vị mục tiêu ở cự ly đến 360 km, độ cao dưới 25 km; radar chiến thuật băng S tầm gần với khả năng định vị 100 km, độ cao 10 km. Chúng có thể cung cấp thông tin về cự ly, phương vị, độ cao và vận tốc; nhắm đến mục tiêu như máy bay hàng không dân dụng, máy bay chiến đấu, máy bay trực thăng, máy bay trực thăng treo.\r\n\r\nMô hình xe với radar 3D cảnh giới tầm trung dùng để thuyết minh tại triển lãm. Ảnh: Nam Nguyễn\r\nMô hình xe với radar 3D cảnh giới tầm trung dùng để thuyết minh tại triển lãm. Ảnh: Nam Nguyễn\r\n\r\nTheo ông Trần Hoàng Việt, so sánh với đài radar 3D chiến thuật cùng phân khúc của một công ty nước ngoài, đài radar do Viettel nghiên cứu có chỉ tiêu tương đương, vượt trội ở một số tính năng như phát hiện mục tiêu trực thăng xa hơn gấp 1,6 lần, độ chính xác đo góc tốt hơn gấp hai lần. Năm 2024, nhóm tác giả của công trình nghiên cứu radar 3D VRS-SRS cũng giành giải Nhất Tuổi trẻ sáng tạo toàn quân do Bộ Quốc phòng trao tặng.', 'uploads/news/1735385032_banner8.png', 1, '2024-12-25 20:54:33'),
(7, 'Băng tần 700 MHz có giá khởi điểm gần 2.000 tỷ đồng', 'Bộ Thông tin và Truyền thông chuẩn bị tổ chức đấu giá hai khối băng tần 700 MHz, có thể dùng triển khai cho mạng 5G diện rộng.\r\n\r\nTrong thông báo ngày 25/12, Cục Tần số vô tuyến điện - Bộ Thông tin và Truyền thông cho biết đang tìm tổ chức để thực hiện đấu giá ba tài sản là khối băng tần B1-B1’ (703-713 MHz và 758-768 MHz), khối B2-B2’ (713-723 MHz và 768-778 MHz), khối B3-B3’ (723-733 MHz và 778-788 MHz).\r\n\r\nCác khối này \"được quy hoạch để triển khai các hệ thống thông tin di động theo tiêu chuẩn IMT-Advanced và các phiên bản tiếp theo, sử dụng phương thức song công phân chia theo tần số\". Chúng có giá khởi điểm cùng là 1.955.613.000.000 đồng, cho giấy phép sử dụng trong 15 năm.\r\n\r\nCục chưa thông tin về thời gian đấu giá, nhưng cho biết việc nhận hồ sơ của đơn vị tổ chức đấu giá sẽ diễn ra ngày 25-27/12.\r\n\r\nKỹ thuật viên một nhà mạng đang lắp đặt trạm phát sóng 5G. Ảnh: MH\r\nKỹ thuật viên một nhà mạng đang lắp đặt trạm phát sóng 5G. Ảnh: MH\r\n\r\nNhững khối trên thuộc nhóm băng tần thấp, từng được sử dụng trong truyền hình tương tự (analog), nhưng đã được giải phóng từ năm 2020 khi Việt Nam chuyển sang truyền hình số mặt đất.\r\n\r\nĐặc tính kỹ thuật của nhóm băng tần này là tốc độ truyền thấp hơn băng tần cao, bù lại độ phủ rộng hơn. Nếu triển khai trong lĩnh vực viễn thông, nhà mạng có thể phủ sóng rộng với mỗi trạm và tiết kiệm được số trạm cần triển khai. So sánh với khối băng tần 2500-2600 MHz hay 3700-3800 MHz mà các nhà mạng đang thương mại hóa, tần số 700 MHz có thể có độ phủ rộng gấp chục lần.\r\n\r\nCục Tần số vô tuyến điện đánh giá quy hoạch băng tần 700 MHz cho thông tin di động IMT \"đáp ứng kỳ vọng của doanh nghiệp viễn thông, tác động tích cực đến xã hội\", khi các mạng như 4G, 5G có thể được phát triển, phủ rộng, đặc biệt ở khu vực nông thôn, miền núi.\r\n\r\nTheo báo cáo của GSA tháng 6/2022, có 205 nhà mạng đã đầu tư xây dựng mạng LTE trong băng tần 700 MHz, trong đó 74 nhà mạng trong số đó đã triển khai 4G LTE hoặc 5G thương mại.\r\n\r\nTrước đó, trong các cuộc đấu giá vào tháng 3 và tháng 4 năm nay, ba nhà mạng Viettel, VinaPhone, MobiFone đã trúng đấu giá ba khối băng tần B1 (2500-2600 MHz), C2 (3700-3800 MHz), C3 (3800-3900 MHz) với tổng số tiền hơn 12.500 tỷ đồng.', 'uploads/news/1735385012_banner2.jpg', 1, '2024-12-25 20:55:20'),
(8, 'Điểm nhấn công nghệ Việt Nam 2024', 'Tắt sóng 2G, thương mại hóa 5G, thu hút đầu tư AI, bán dẫn hay siết chặt quản lý Internet là những điểm nổi bật của công nghệ Việt Nam 2024.\r\n2024 chứng kiến nhiều biến chuyển tích cực trong lĩnh vực công nghệ tại Việt Nam, đặc biệt là về công nghệ cao như AI, bán dẫn, 5G. Bên cạnh đó, vấn đề an toàn an ninh mạng, thông tin trên Internet cũng tạo ra những thách thức cần vượt qua.\r\n\r\nSiết quản lý Internet bằng yêu cầu xác thực người dùng\r\n\r\nNghị định 147 về Quản lý, cung cấp, sử dụng Internet và thông tin trên mạng được Chính phủ ban hành ngày 9/11 và có hiệu lực từ 25/12, tập trung vào mạng xã hội, trang thông tin điện tử, trò chơi điện tử và tài nguyên Internet.\r\n\r\nBên cạnh các thay đổi nhằm đơn giản hóa thủ tục cấp phép các dịch vụ, một trong những điểm nhấn của nghị định là xác thực người dùng. Các đơn vị cung cấp dịch vụ phải định danh tài khoản mạng xã hội bằng số điện thoại Việt Nam, bảo đảm \"chỉ tài khoản xác thực mới được đăng thông tin\" như viết bài, bình luận, livestream. Quy định tương tự cũng được đưa ra với trò chơi điện tử, trong đó yêu cầu công ty game cần định danh người dùng và có hệ thống thiết bị kỹ thuật quản lý thời gian trong ngày, đặc biệt người dưới 18 tuổi không chơi một game quá 60 phút trong ngày.\r\n\r\n\r\nNgười tiêu dùng xem livestream bán hàng qua mạng xã hội. Ảnh: Thành Nguyễn\r\nNghị định cũng yêu cầu mạng xã hội xuyên biên giới phối hợp với cơ quan chức năng để ngăn chặn, gỡ bỏ thông tin xấu độc, khóa trang vi phạm, không cho truy cập từ người dùng Việt Nam, công khai thuật toán phân phối nội dung.\r\n\r\nNghị định 147 được ban hành thay thế cho nghị định 72/2013 và 27/2018, vốn ra đời nhiều năm, không còn bao quát hết về các vấn đề thông tin điện tử. Trong khi đó, sự phát triển bùng nổ của mạng xã hội, trang tin, trò chơi điện tử thời gian qua tại Việt Nam gây nhiều vấn đề tiêu cực như tin giả, tin xấu độc, lừa đảo mạng xuất hiện tràn lan. \"Xác thực tài khoản sẽ hạn chế tình trạng \'vô danh nên vô trách nhiệm’ khi hoạt động trên mạng\", Cục trưởng Phát thanh truyền hình và Thông tin điện tử Lê Quang Tự Do cho biết.\r\n\r\nMạng 5G chính thức thương mại hóa\r\n\r\nNgày 15/10, mạng 5G đầu tiên tại Việt Nam được Viettel nhấn nút khai trương. Đến 20/12, VinaPhone trở thành nhà mạng thứ hai thương mại hoá 5G. Tốc độ Internet di động trong nước tăng hơn 30% trên bảng thống kê của Ookla Speedtest, đạt hơn 71 Mbps, xếp thứ 43 toàn cầu ngay trong tháng 10.\r\n\r\n5G đến với người dùng một cách chính thức sau tám năm kể từ khi Việt Nam triển khai 4G. Đây là kết quả của quá trình dài chuẩn bị từ phía cơ quan quản lý cũng nhà cung cấp dịch vụ trong nước. Cuộc gọi 5G đầu tiên được thực hiện năm 2019, trước được thử nghiệm thương mại năm 2021. Đầu 2024, khi phương án đấu giá băng tần được phê duyệt, ba nhà mạng đã chi hơn 12.000 tỷ đồng để sở hữu các tần số, hoàn thành một trong những điều kiện cuối cùng để triển khai 5G.\r\n\r\nSo sánh tốc độ kết nối thực tế của 5G và 4G khi mới triển khai giữa tháng 10 tại Hà Nội. Video: Tuấn Hưng - Lưu Quý\r\nỞ thời điểm đầu triển khai, mạng 5G được đánh giá chưa ổn định, độ phủ còn thấp. Tuy nhiên, thế hệ mạng mới hứa hẹn tạo ra những thay đổi quan trọng như có thể đạt 1 - 1,5 Gbps, độ trễ gần như bằng 0, theo công bố từ các nhà mạng. Các yếu tố này không chỉ giúp tăng tốc tải dữ liệu, mà còn mở ra khả năng hiện thực hóa thành phố thông minh mà những thế hệ mạng cũ không thể đáp ứng do hạn chế kỹ thuật. Các dịch vụ yêu cầu phản hồi tức thì, như xe tự lái, phẫu thuật từ xa, điều khiển trong nhà máy thông minh, dịch vụ yêu cầu nhiều dữ liệu như video 4K/8K, AR chỉ có thể triển khai với 5G.\r\n\r\nNhững đột phá đó khiến 5G không đơn thuần là kết nối mạng thế hệ mới mà còn trở thành hạ tầng quan trọng trong chiến lược chuyển đổi số. Theo quy hoạch hạ tầng thông tin và truyền thông, Việt Nam đặt mục tiêu đến 2025, 100% tỉnh, thành phố, khu công nghệ cao, khu công nghệ thông tin tập trung, trung tâm nghiên cứu phát triển, đổi mới sáng tạo, khu công nghiệp, nhà ga, cảng biển, sân bay quốc tế có dịch vụ 5G. Đến 2030, mạng 5G phủ sóng 99% dân số.\r\n\r\nViệc triển khai 5G cũng đánh dấu Việt Nam đang rút ngắn khoảng cách với thế giới về viễn thông cũng như tự chủ công nghệ. \"Đây là lần đầu Việt Nam có thể song hành với thế giới trong việc ứng dụng công nghệ mới nhất của cuộc cách mạng 4.0, trở thành một trong những quốc gia thử nghiệm thành công sớm nhất\", Chủ tịch Viettel Tào Đức Thắng cho biết. \"Việt Nam gia nhập top 5 nước đầu tiên sản xuất được thiết bị mạng 5G, sau Thụy Điển, Phần Lan, Trung Quốc và Hàn Quốc\".\r\n\r\n\r\nChỉ số phát triển Chính phủ điện tử tăng 15 bậc\r\n\r\nTheo Báo cáo khảo sát Chính phủ điện tử 2024 được Liên Hợp Quốc công bố tháng 9, Việt Nam đạt 0,7709 điểm về Chỉ số phát triển Chính phủ điện tử - EGDI. So với 0,6787 điểm ở lần công bố trước vào năm 2022, Việt Nam tăng 15 bậc từ thứ hạng 86 lên 71 trên thế giới, lần đầu chuyển từ mức \"cao\" lên \"rất cao\".\r\n\r\nChỉ số\r\nThứ hạng\r\nChỉ số phát triển Chính phủ điện tử Việt Nam\r\nGiai đoạn 2008 - 2024\r\nChỉ số\r\nThứ hạng\r\n2008\r\n2010\r\n2012\r\n2014\r\n2016\r\n2018\r\n2020\r\n2022\r\n2024\r\n0\r\n0.25\r\n0.5\r\n0.75\r\n1\r\n48\r\n64\r\n80\r\n96\r\n112\r\nVnExpress\r\nChỉ số EGDI được tổng hợp từ ba thông số chính: Hạ tầng viễn thông, Nguồn nhân lực và Dịch vụ trực tuyến. Các chỉ số thể hiện mức độ chính phủ ứng dụng công nghệ thông tin để nâng cao hiệu lực, hiệu quả hoạt động, phục vụ người dân và doanh nghiệp tốt hơn. Tại Việt Nam, nhiều hệ thống nền tảng của Chính phủ điện tử đã được vận hành, giúp đổi mới cơ quan nhà nước, cung cấp dịch vụ trực tuyến cho người dân, doanh nghiệp. Các hệ thống này có thể kể đến Trục liên thông văn bản quốc gia khai trương từ tháng 3/2019; Cổng dịch vụ công quốc gia vận hành cuối 2019; Hệ thống thông tin báo cáo quốc gia và Trung tâm Thông tin phục vụ chỉ đạo, điều hành của Chính phủ, Thủ tướng khai trương tháng 8/2020.\r\n\r\nDù đạt kết quả tích cực, việc triển khai vẫn còn một số vấn đề, như chưa hình thành thói quen dùng dịch vụ công trực tuyến. Theo báo cáo tháng 12 của Bộ Thông tin và Truyền thông, tỷ lệ hồ sơ được xử lý trực tuyến trên cả nước đạt 45%, trong đó khối địa phương là 18%. Bộ đặt mục tiêu đến 2030, 70% người dân trưởng thành sử dụng dịch vụ công trực tuyến.\r\n\r\n\"Thời gian tới, Việt Nam sẽ tiếp tục triển khai chuyển đổi số quốc gia nhằm phát triển kinh tế - xã hội, trong đó thực hiện các giải pháp để tiếp tục nâng thứ hạng chỉ số Chính phủ điện tử, Chính phủ số của Việt Nam theo đánh giá của Liên Hợp Quốc, từ đó cải thiện môi trường kinh doanh, nâng cao năng lực cạnh tranh quốc gia\", Bộ Thông tin và Truyền thông cho biết.\r\n\r\nNvidia mở hai trung tâm thúc đẩy AI, bán dẫn tại Việt Nam\r\n\r\nNgày 5/12, Chính phủ và Nvidia công bố hợp tác thành lập Trung tâm Nghiên cứu và Phát triển AI (VRDC) và Trung tâm Dữ liệu AI.\r\n\r\nThủ tướng Phạm Minh Chính đánh giá đây là dấu mốc quan trọng, thể hiện quyết tâm và cam kết mạnh mẽ của CEO Nvidia Jensen Huang trong việc biến Việt Nam thành \"ngôi nhà thứ hai\" với tinh thần \"đã nói là làm, đã cam kết phải thực hiện, đã làm, đã thực hiện phải mang lại hiệu quả cụ thể\".\r\n\r\nSự kiện cũng tạo tiền đề đưa Việt Nam trở thành trung tâm nghiên cứu và phát triển AI hàng đầu châu Á. Các trung tâm không chỉ đóng vai trò chủ chốt trong việc hỗ trợ các sáng kiến, ứng dụng AI, thúc đẩy hoạt động đổi mới sáng tạo, khởi nghiệp mà còn tạo cơ hội việc làm cho đội ngũ nhân tài trong nước.', 'uploads/news/1735385002_tech5.png', 1, '2024-12-25 20:55:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro`
--

CREATE TABLE `vaitro` (
  `Id` int(11) NOT NULL,
  `TenVaiTro` varchar(50) NOT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vaitro`
--

INSERT INTO `vaitro` (`Id`, `TenVaiTro`, `NgayTao`) VALUES
(1, 'admin', '2024-12-25 10:28:14'),
(2, 'member', '2024-12-25 10:28:14');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chucvu`
--
ALTER TABLE `chucvu`
  ADD PRIMARY KEY (`Id`);

--
-- Chỉ mục cho bảng `danhsachdangky`
--
ALTER TABLE `danhsachdangky`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `NguoiDungId` (`NguoiDungId`),
  ADD KEY `HoatDongId` (`HoatDongId`);

--
-- Chỉ mục cho bảng `danhsachthamgia`
--
ALTER TABLE `danhsachthamgia`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `NguoiDungId` (`NguoiDungId`),
  ADD KEY `HoatDongId` (`HoatDongId`);

--
-- Chỉ mục cho bảng `hoatdong`
--
ALTER TABLE `hoatdong`
  ADD PRIMARY KEY (`Id`);

--
-- Chỉ mục cho bảng `khoatruong`
--
ALTER TABLE `khoatruong`
  ADD PRIMARY KEY (`Id`);

--
-- Chỉ mục cho bảng `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`Id`);

--
-- Chỉ mục cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `KhoaTruongId` (`KhoaTruongId`);

--
-- Chỉ mục cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `TenDangNhap` (`TenDangNhap`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `MaSinhVien` (`MaSinhVien`),
  ADD KEY `ChucVuId` (`ChucVuId`),
  ADD KEY `LopHocId` (`LopHocId`),
  ADD KEY `VaiTroId` (`VaiTroId`);

--
-- Chỉ mục cho bảng `nhiemvu`
--
ALTER TABLE `nhiemvu`
  ADD PRIMARY KEY (`Id`);

--
-- Chỉ mục cho bảng `phancongnhiemvu`
--
ALTER TABLE `phancongnhiemvu`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `NguoiDungId` (`NguoiDungId`),
  ADD KEY `NhiemVuId` (`NhiemVuId`);

--
-- Chỉ mục cho bảng `phanquyentailieu`
--
ALTER TABLE `phanquyentailieu`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `TaiLieuId` (`TaiLieuId`),
  ADD KEY `VaiTroId` (`VaiTroId`);

--
-- Chỉ mục cho bảng `taichinh`
--
ALTER TABLE `taichinh`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `NguoiDungId` (`NguoiDungId`);

--
-- Chỉ mục cho bảng `tailieu`
--
ALTER TABLE `tailieu`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `NguoiTaoId` (`NguoiTaoId`);

--
-- Chỉ mục cho bảng `tintuc`
--
ALTER TABLE `tintuc`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `NguoiTaoId` (`NguoiTaoId`);

--
-- Chỉ mục cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chucvu`
--
ALTER TABLE `chucvu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `danhsachdangky`
--
ALTER TABLE `danhsachdangky`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `danhsachthamgia`
--
ALTER TABLE `danhsachthamgia`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `hoatdong`
--
ALTER TABLE `hoatdong`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `khoatruong`
--
ALTER TABLE `khoatruong`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `log`
--
ALTER TABLE `log`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `nhiemvu`
--
ALTER TABLE `nhiemvu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `phancongnhiemvu`
--
ALTER TABLE `phancongnhiemvu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `phanquyentailieu`
--
ALTER TABLE `phanquyentailieu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `taichinh`
--
ALTER TABLE `taichinh`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT cho bảng `tailieu`
--
ALTER TABLE `tailieu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `tintuc`
--
ALTER TABLE `tintuc`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `danhsachdangky`
--
ALTER TABLE `danhsachdangky`
  ADD CONSTRAINT `danhsachdangky_ibfk_1` FOREIGN KEY (`NguoiDungId`) REFERENCES `nguoidung` (`Id`),
  ADD CONSTRAINT `danhsachdangky_ibfk_2` FOREIGN KEY (`HoatDongId`) REFERENCES `hoatdong` (`Id`);

--
-- Các ràng buộc cho bảng `danhsachthamgia`
--
ALTER TABLE `danhsachthamgia`
  ADD CONSTRAINT `danhsachthamgia_ibfk_1` FOREIGN KEY (`NguoiDungId`) REFERENCES `nguoidung` (`Id`),
  ADD CONSTRAINT `danhsachthamgia_ibfk_2` FOREIGN KEY (`HoatDongId`) REFERENCES `hoatdong` (`Id`);

--
-- Các ràng buộc cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  ADD CONSTRAINT `lophoc_ibfk_1` FOREIGN KEY (`KhoaTruongId`) REFERENCES `khoatruong` (`Id`);

--
-- Các ràng buộc cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD CONSTRAINT `nguoidung_ibfk_1` FOREIGN KEY (`ChucVuId`) REFERENCES `chucvu` (`Id`),
  ADD CONSTRAINT `nguoidung_ibfk_2` FOREIGN KEY (`LopHocId`) REFERENCES `lophoc` (`Id`),
  ADD CONSTRAINT `nguoidung_ibfk_3` FOREIGN KEY (`VaiTroId`) REFERENCES `vaitro` (`Id`);

--
-- Các ràng buộc cho bảng `phancongnhiemvu`
--
ALTER TABLE `phancongnhiemvu`
  ADD CONSTRAINT `phancongnhiemvu_ibfk_1` FOREIGN KEY (`NguoiDungId`) REFERENCES `nguoidung` (`Id`),
  ADD CONSTRAINT `phancongnhiemvu_ibfk_2` FOREIGN KEY (`NhiemVuId`) REFERENCES `nhiemvu` (`Id`);

--
-- Các ràng buộc cho bảng `phanquyentailieu`
--
ALTER TABLE `phanquyentailieu`
  ADD CONSTRAINT `phanquyentailieu_ibfk_1` FOREIGN KEY (`TaiLieuId`) REFERENCES `tailieu` (`Id`),
  ADD CONSTRAINT `phanquyentailieu_ibfk_2` FOREIGN KEY (`VaiTroId`) REFERENCES `vaitro` (`Id`);

--
-- Các ràng buộc cho bảng `taichinh`
--
ALTER TABLE `taichinh`
  ADD CONSTRAINT `taichinh_ibfk_1` FOREIGN KEY (`NguoiDungId`) REFERENCES `nguoidung` (`Id`);

--
-- Các ràng buộc cho bảng `tailieu`
--
ALTER TABLE `tailieu`
  ADD CONSTRAINT `tailieu_ibfk_1` FOREIGN KEY (`NguoiTaoId`) REFERENCES `nguoidung` (`Id`);

--
-- Các ràng buộc cho bảng `tintuc`
--
ALTER TABLE `tintuc`
  ADD CONSTRAINT `tintuc_ibfk_1` FOREIGN KEY (`NguoiTaoId`) REFERENCES `nguoidung` (`Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
