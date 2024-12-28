-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th12 27, 2024 lúc 11:20 AM
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
(4, 'Thành viên', '2024-12-25 10:28:14');

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
  `TrangThai` tinyint(4) DEFAULT 1
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
  `TrangThai` tinyint(4) DEFAULT 1,
  `NguoiTaoId` int(11) DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp(),
  FOREIGN KEY (`NguoiTaoId`) REFERENCES `nguoidung`(`Id`)
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

--
-- Đang đổ dữ liệu cho bảng `khoatruong`
--

INSERT INTO `khoatruong` (`Id`, `TenKhoaTruong`, `NgayTao`) VALUES
(1, 'Khoa Công nghệ Thông tin Update', '2024-12-25 10:28:14'),
(2, 'Khoa Kinh tế', '2024-12-25 10:28:14'),
(3, 'Khoa Ngoại ngữ', '2024-12-25 10:28:14'),
(4, 'New Khoa', '2024-12-25 16:26:10');

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
(1, 'CNTT1', 1, '2024-12-25 10:28:14'),
(2, 'CNTT2', 1, '2024-12-25 10:28:14'),
(3, 'KT1', 2, '2024-12-25 10:28:14'),
(4, 'KT2', 2, '2024-12-25 10:28:14'),
(5, 'NN1', 3, '2024-12-25 10:28:14'),
(6, 'NN2', 3, '2024-12-25 10:28:14');

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
  `lantruycapcuoi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoidung`
--

tôi mới cập nhật lại csdl hãy đọc @csdl.sql và vập nhật lại những thay đổi có lien quan, cong những gì trong module activities tương ứng với bảng hoatdong trong csdl; hãy cập nhật lại các module hoặc action có liên quan
INSERT INTO `nguoidung` (`Id`, `MaSinhVien`, `TenDangNhap`, `MatKhauHash`, `HoTen`, `Email`, `anhdaidien`, `GioiTinh`, `NgaySinh`, `ChucVuId`, `LopHocId`, `NgayTao`, `TrangThai`, `VaiTroId`, `lantruycapcuoi`) VALUES
(1, 'ADMIN', 'admin', '$2y$10$o..QP8iAlmOaJZhEmP5oG.X673HOn0hvap9SK6RJ.1/5LcX/Ubd0a', 'Administrator', 'nguyendaihoangphuc24@gmail.com', '../images/Users/tvupng.png', 1, NULL, 1, 1, '2024-12-25 10:28:14', 1, 1, '2024-12-26 15:39:49'),
(6, '', 'nguyendaihoangphuc1911', '$2y$10$JYFmlfReokigchRgiLC1wOXDJG6rVOsCnsYKnRGrMysqjC2/FV3y6', '', 'nguyendaihoangphuc1911@gmail.com', '../images/Users/tvupng.png', 1, '2000-02-16', 4, 1, '2024-12-25 12:37:02', 1, 2, '2024-12-26 09:21:21'),
(7, '110323232', 'phucndh4', '$2y$10$JYFmlfReokigchRgiLC1wOXDJG6rVOsCnsYKnRGrMysqjC2/FV3y6', 'Nguyễn Văn Test', 'phucndh4@mail.com', 'phucndh4', 1, '2000-11-11', 4, 1, '2024-12-25 17:27:11', 1, 2, NULL);

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
-- Cấu trúc bảng cho bảng `nhiemvu`
--

CREATE TABLE `nhiemvu` (
  `Id` int(11) NOT NULL,
  `TenNhiemVu` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `TrangThai` tinyint(4) DEFAULT 0, --0: Chưa bắt đầu; 1: Đang thực hiện; 2: Hoàn thành; 3: Quá hạn
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

--
-- Đang đổ dữ liệu cho bảng `taichinh`
--

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

--
-- Đang đổ dữ liệu cho bảng `tintuc`
--

INSERT INTO `tintuc` (`Id`, `TieuDe`, `NoiDung`, `FileDinhKem`, `NguoiTaoId`, `NgayTao`) VALUES
(5, ' Số hóaBlockchainThứ sáu, 13/12/2024, 08:00 (GMT+7) TP HCM đặt mục tiêu thành điểm đến của doanh nghiệp AI, blockchain', 'Để hướng tới mục tiêu trở thành thành phố công nghệ hàng đầu, TP HCM cần được tạo thêm hành lang pháp lý cho công nghệ mới, như AI, blockchain.\r\n\r\nTại tọa đàm ngày 12/12 của Hội Truyền thông Điện tử TP HCM, các lãnh đạo thành phố chia sẻ tầm nhìn chiến lược và giải pháp nhằm đưa TP HCM trở thành điểm đến cho các doanh nghiệp trong lĩnh vực công nghệ, đặc biệt là blockchain và AI.\r\n\r\nÔng Nguyễn Ngọc Hồi, phó giám đốc Sở Thông tin và Truyền thông TP HCM. Ảnh: Bảo Lâm\r\nÔng Nguyễn Ngọc Hồi, Phó giám đốc Sở Thông tin và Truyền thông TP HCM. Ảnh: Bảo Lâm\r\n\r\nTheo ông Nguyễn Ngọc Hồi, Phó giám đốc Sở Thông tin và Truyền thông TP HCM, thành phố đang trên đường tạo ra hành lang pháp lý để khơi dậy sự phát triển của các lĩnh vực tiềm năng và công nghệ mới. \"Phải làm sao để mọi người khi nghĩ đến TP HCM là nơi tạo cơ hội đột phá trong các lĩnh vực công nghệ, điển hình là game và blockchain thời gian tới\", ông Hồi nói.\r\n\r\nĐồng quan điểm, ông Nguyễn Thanh Hòa, trưởng phòng Thông tin điện tử - Sở Thông tin và Truyền thông TP HCM, cho rằng cần cải thiện hành lang pháp lý để phát triển các xu hướng công nghệ mới. \"Chúng ta có ba trung tâm đổi mới sáng tạo trên cả nước và TP HCM là một trong số đó. Các cơ sở đang hình thành như Trung tâm Chuyển đổi số, Công nghệ Sinh học, Viện nghiên cứu VTIS, Trung tâm VIS\", ông Hòa nói.\r\n\r\nTheo ông, thành phố đang tiếp tục chuyển đổi số và thực hiện Nghị quyết 98 của chính phủ. \"Nghị quyết 98 mở ra rất nhiều tiềm năng để phát triển công nghệ blockchain thời gian tới\", ông nói thêm. Nghị quyết 98 có hiệu lực từ 1/8/2023, gồm 44 nhóm chính sách với 7 lĩnh vực, kỳ vọng mang lại nhiều lợi ích cho người dân, doanh nghiệp tại thành phố.\r\n\r\nVề phía Hội Truyền thông Điện tử TP HCM, ông Nguyễn Quý Hòa cho biết Hội đã hoạt động 34 năm và nhận thấy TP HCM luôn là đơn vị dẫn đầu mảng truyền thông số. \"Hiện nay các doanh nghiệp hoạt động trong mảng blockchain chưa hiểu hết các quy định và khung pháp lý. Vì vậy, Hội sẽ đảm nhận vai trò gắn kết và giúp doanh nghiệp hiểu rõ hơn để tận dụng quyền lợi từ các chính sách nhà nước\", ông Quý Hòa nói.\r\n\r\nSự kiện cũng công bố thành lập hai chi hội mới là Chi hội Blockchain TP HCM (HBA) và Chi hội Quảng cáo và Truyền thông số TP HCM (DACA). HBA sẽ có nhiệm vụ phát triển cộng đồng blockchain TP HCM, còn DACA được thành lập để đáp ứng nhu cầu về chiến lược truyền thông sáng tạo và quảng cáo số hóa trong nền kinh tế số.\r\n\r\nÔng Lê Thanh, đại diện HBA, đánh giá TP HCM có thể trở thành điểm đến lý tưởng cho các startup blockchain khi sở hữ
