-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th1 09, 2025 lúc 03:27 PM
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
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1
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
  `TrangThai` tinyint(4) DEFAULT 0 COMMENT '0: Sắp diễn ra, 1 Đang diễn ra; 2: Đã kết thúc',
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
  `Id` int(11) NOT NULL,
  `IP` varchar(45) DEFAULT NULL,
  `NguoiDung` varchar(50) DEFAULT NULL,
  `HanhDong` varchar(255) DEFAULT NULL,
  `KetQua` varchar(50) DEFAULT NULL,
  `ChiTiet` text DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
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
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
