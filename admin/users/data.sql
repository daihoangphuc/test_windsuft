-- Thêm dữ liệu mẫu cho bảng vaitro
INSERT INTO `vaitro` (`Id`, `TenVaiTro`, `MoTa`) VALUES
(1, 'Admin', 'Quản trị viên hệ thống'),
(2, 'Member', 'Thành viên');

-- Thêm dữ liệu mẫu cho bảng chucvu
INSERT INTO `chucvu` (`Id`, `TenChucVu`) VALUES
(1, 'Chủ nhiệm'),
(2, 'Phó chủ nhiệm'),
(3, 'Thư ký'),
(4, 'Thủ quỹ'),
(5, 'Thành viên');

-- Thêm dữ liệu mẫu cho bảng khoatruong
INSERT INTO `khoatruong` (`Id`, `TenKhoaTruong`) VALUES
(1, 'Khoa Công nghệ thông tin'),
(2, 'Khoa Kinh tế'),
(3, 'Khoa Ngoại ngữ');

-- Thêm dữ liệu mẫu cho bảng lophoc
INSERT INTO `lophoc` (`Id`, `TenLop`, `KhoaTruongId`) VALUES
(1, 'CNTT1', 1),
(2, 'CNTT2', 1),
(3, 'KT1', 2),
(4, 'KT2', 2),
(5, 'NN1', 3),
(6, 'NN2', 3);
