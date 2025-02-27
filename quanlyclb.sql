-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th12 29, 2024 lúc 05:54 AM
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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhsachthamgia`
--

CREATE TABLE `danhsachthamgia` (
  `Id` int(11) NOT NULL,
  `NguoiDungId` int(11) DEFAULT NULL,
  `HoatDongId` int(11) DEFAULT NULL,
  `DiemDanhLuc` datetime DEFAULT current_timestamp(),
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1 ; -- 0: Vắng, 1: Đã tham gia
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `TrangThai` tinyint(4) DEFAULT 0, 	0: Sắp diễn ra, 1 Đang diễn ra; 2: Đã kết thúc
  `NgayTao` datetime DEFAULT current_timestamp(),
  `NguoiTaoId` int(11) NOT NULL,
  `DuongDanMinhChung` text DEFAULT NULL COMMENT 'đường dẫn file minh chứng (danh sách tham gia hoạt động có mộc đỏ)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoatruong`
--

CREATE TABLE `khoatruong` (
  `Id` int(11) NOT NULL,
  `TenKhoaTruong` varchar(255) NOT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `log`
--

CREATE TABLE `log` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `IP` varchar(45) DEFAULT NULL,
  `NguoiDung` varchar(50) DEFAULT NULL,
  `HanhDong` varchar(255) DEFAULT NULL,
  `KetQua` varchar(50) DEFAULT NULL,
  `ChiTiet` text DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `reset_token` VARCHAR(64) NULL,
  `reset_expires` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `danhsachdangky`
--
ALTER TABLE `danhsachdangky`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `danhsachthamgia`
--
ALTER TABLE `danhsachthamgia`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `hoatdong`
--
ALTER TABLE `hoatdong`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `khoatruong`
--
ALTER TABLE `khoatruong`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `log`
--
ALTER TABLE `log`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nhiemvu`
--
ALTER TABLE `nhiemvu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phancongnhiemvu`
--
ALTER TABLE `phancongnhiemvu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phanquyentailieu`
--
ALTER TABLE `phanquyentailieu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `taichinh`
--
ALTER TABLE `taichinh`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `tailieu`
--
ALTER TABLE `tailieu`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `tintuc`
--
ALTER TABLE `tintuc`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

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

INSERT INTO `chucvu` (`Id`, `TenChucVu`, `NgayTao`) VALUES
(1, 'Chủ nhiệm', '2024-12-25 10:28:14'),
(2, 'Phó chủ nhiệm', '2024-12-25 10:28:14'),
(3, 'Thư ký', '2024-12-25 10:28:14'),
(4, 'Thành viên', '2024-12-25 10:28:14');

INSERT INTO `khoatruong` (`Id`, `TenKhoaTruong`, `NgayTao`) VALUES
(1, 'Khoa Công nghệ Thông tin Update', '2024-12-25 10:28:14'),
(2, 'Khoa Kinh tế', '2024-12-25 10:28:14'),
(3, 'Khoa Điện - Điện tử', '2024-12-25 10:28:14');

INSERT INTO `lophoc` (`Id`, `TenLop`, `KhoaTruongId`, `NgayTao`) VALUES
(1, 'CNTT1', 1, '2024-12-25 10:28:14'),
(2, 'CNTT2', 1, '2024-12-25 10:28:14'),
(3, 'CNTT3', 1, '2024-12-25 10:28:14'),
(4, 'KT1', 2, '2024-12-25 10:28:14'),
(5, 'KT2', 2, '2024-12-25 10:28:14'),
(6, 'DDT1', 3, '2024-12-25 10:28:14'),
(7, 'DDT2', 3, '2024-12-25 10:28:14');

INSERT INTO `taichinh` (`Id`, `LoaiGiaoDich`, `SoTien`, `MoTa`, `NgayGiaoDich`, `NguoiDungId`, `NgayTao`) VALUES
(1, 0, 2000000, 'Thu tiền quỹ tháng 10', '2024-12-25 21:35:46', 1, '2024-12-25 21:35:46'),
(2, 1, 10000, 'Mua giấy A4 tổ chức trò chơi cho trẻ em', '2024-12-25 21:46:25', 1, '2024-12-25 21:46:25'),
(13, 0, 2000000, 'Thu tiền quỹ tháng 1', '2024-01-15 09:00:00', 1, '2024-01-15 09:00:00'),
(14, 0, 4500000, 'Thu tiền quỹ tháng 2', '2024-02-10 10:00:00', 1, '2024-02-10 10:00:00'),
(15, 0, 1500000, 'Thu tiền quỹ tháng 3', '2024-03-05 14:30:00', 1, '2024-03-05 14:30:00'),
(16, 0, 1800000, 'Thu tiền quỹ tháng 4', '2024-04-20 11:15:00', 1, '2024-04-20 11:15:00'),
(17, 0, 500000, 'Thu tiền quỹ tháng 5', '2024-05-25 08:00:00', 1, '2024-05-25 08:00:00'),
(18, 0, 2500000, 'Thu tiền quỹ tháng 6', '2024-06-18 13:30:00', 1, '2024-06-18 13:30:00'),
(19, 0, 4200000, 'Thu tiền quỹ tháng 7', '2024-07-12 09:45:00', 1, '2024-07-12 09:45:00'),
(20, 0, 1300000, 'Thu tiền quỹ tháng 8', '2024-08-22 16:00:00', 1, '2024-08-22 16:00:00'),
(21, 0, 3000000, 'Thu tiền quỹ tháng 9', '2024-09-17 14:00:00', 1, '2024-09-17 14:00:00'),
(22, 0, 2200000, 'Thu tiền quỹ tháng 10', '2024-10-02 11:00:00', 1, '2024-10-02 11:00:00'),
(23, 1, 10000, 'Mua giấy A4 tổ chức trò chơi cho trẻ em', '2024-12-25 21:46:25', 1, '2024-12-25 21:46:25'),
(24, 1, 25000, 'Chi phí tổ chức sự kiện mừng sinh nhật', '2024-12-20 14:00:00', 1, '2024-12-20 14:00:00'),
(25, 1, 750000, 'Mua thiết bị cho phòng học', '2024-11-15 10:30:00', 1, '2024-11-15 10:30:00'),
(26, 1, 500000, 'Chi phí bảo trì hệ thống máy tính', '2024-10-05 15:45:00', 1, '2024-10-05 15:45:00'),
(27, 1, 520000, 'Chi phí tổ chức workshop', '2024-09-10 09:30:00', 1, '2024-09-10 09:30:00'),
(28, 1, 150000, 'Mua quà sinh nhật cho nhân viên', '2024-08-25 11:00:00', 1, '2024-08-25 11:00:00'),
(29, 1, 1000000, 'Mua văn phòng phẩm cho trường học', '2024-07-10 10:30:00', 1, '2024-07-10 10:30:00'),
(30, 1, 60000, 'Chi phí tiếp khách cho đối tác', '2024-06-15 13:15:00', 1, '2024-06-15 13:15:00'),
(31, 1, 2000000, 'Chi phí ăn uống cho nhân viên', '2024-05-01 08:45:00', 1, '2024-05-01 08:45:00'),
(32, 1, 240000, 'Chi phí vận chuyển hàng hóa', '2024-04-12 16:00:00', 1, '2024-04-12 16:00:00');

INSERT INTO `tintuc` (`Id`, `TieuDe`, `NoiDung`, `FileDinhKem`, `NguoiTaoId`, `NgayTao`) VALUES
(5, ' Số hóaBlockchainThứ sáu, 13/12/2024, 08:00 (GMT+7) TP HCM đặt mục tiêu thành điểm đến của doanh nghiệp AI, blockchain', 'Để hướng tới mục tiêu trở thành thành phố công nghệ hàng đầu, TP HCM cần được tạo thêm hành lang pháp lý cho công nghệ mới, như AI, blockchain.\r\n\r\nTại tọa đàm ngày 12/12 của Hội Truyền thông Điện tử TP HCM, các lãnh đạo thành phố chia sẻ tầm nhìn chiến lược và giải pháp nhằm đưa TP HCM trở thành điểm đến cho các doanh nghiệp trong lĩnh vực công nghệ, đặc biệt là blockchain và AI.\r\n\r\nÔng Nguyễn Ngọc Hồi, phó giám đốc Sở Thông tin và Truyền thông TP HCM. Ảnh: Bảo Lâm\r\nÔng Nguyễn Ngọc Hồi, Phó giám đốc Sở Thông tin và Truyền thông TP HCM. Ảnh: Bảo Lâm\r\n\r\nTheo ông Nguyễn Ngọc Hồi, Phó giám đốc Sở Thông tin và Truyền thông TP HCM, thành phố đang trên đường tạo ra hành lang pháp lý để khơi dậy sự phát triển của các lĩnh vực tiềm năng và công nghệ mới. \"Phải làm sao để mọi người khi nghĩ đến TP HCM là nơi tạo cơ hội đột phá trong các lĩnh vực công nghệ, điển hình là game và blockchain thời gian tới\", ông Hồi nói.\r\n\r\nĐồng quan điểm, ông Nguyễn Thanh Hòa, trưởng phòng Thông tin điện tử - Sở Thông tin và Truyền thông TP HCM, cho rằng cần cải thiện hành lang pháp lý để phát triển các xu hướng công nghệ mới. \"Chúng ta có ba trung tâm đổi mới sáng tạo trên cả nước và TP HCM là một trong số đó. Các cơ sở đang hình thành như Trung tâm Chuyển đổi số, Công nghệ Sinh học, Viện nghiên cứu VTIS, Trung tâm VIS\", ông Hòa nói.\r\n\r\nTheo ông, thành phố đang tiếp tục chuyển đổi số và thực hiện Nghị quyết 98 của chính phủ. \"Nghị quyết 98 mở ra rất nhiều tiềm năng để phát triển công nghệ blockchain thời gian tới\", ông nói thêm. Nghị quyết 98 có hiệu lực từ 1/8/2023, gồm 44 nhóm chính sách với 7 lĩnh vực, kỳ vọng mang lại nhiều lợi ích cho người dân, doanh nghiệp tại thành phố.\r\n\r\nVề phía Hội Truyền thông Điện tử TP HCM, ông Nguyễn Quý Hòa cho biết Hội đã hoạt động 34 năm và nhận thấy TP HCM luôn là đơn vị dẫn đầu mảng truyền thông số. \"Hiện nay các doanh nghiệp hoạt động trong mảng blockchain chưa hiểu hết các quy định và khung pháp lý. Vì vậy, Hội sẽ đảm nhận vai trò gắn kết và giúp doanh nghiệp hiểu rõ hơn để tận dụng quyền lợi từ các chính sách nhà nước\", ông Quý Hòa nói.\r\n\r\nSự kiện cũng công bố thành lập hai chi hội mới là Chi hội Blockchain TP HCM (HBA) và Chi hội Quảng cáo và Truyền thông số TP HCM (DACA). HBA sẽ có nhiệm vụ phát triển cộng đồng blockchain TP HCM, còn DACA được thành lập để đáp ứng nhu cầu về chiến lược truyền thông sáng tạo và quảng cáo số hóa trong nền kinh tế số.\r\n\r\nÔng Lê Thanh, đại diện HBA, đánh giá TP HCM có thể trở thành điểm đến lý tưởng cho các startup blockchain khi sở hữ


INSERT INTO `khoatruong` (`Id`, `TenKhoaTruong`, `NgayTao`) VALUES
(1, 'Khoa Công nghệ Thông tin Update', '2024-12-25 10:28:14'),
(2, 'Khoa Kinh tế', '2024-12-25 10:28:14'),
(3, 'Khoa Ngoại ngữ', '2024-12-25 10:28:14'),
(4, 'New Khoa', '2024-12-25 16:26:10');
