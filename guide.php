<?php
$pageTitle = 'Hướng dẫn sử dụng';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="bg-white">
    <div class="container mx-auto px-4 py-8">
        <!-- Tiêu đề -->
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-[#4a90e2] mb-8">Hướng dẫn sử dụng website CLB Hành trình sinh viên</h1>

            <!-- Phần 1: Đăng ký và Đăng nhập -->
            <div class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">1. Đăng ký và Đăng nhập</h2>
                <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Đăng ký tài khoản</h3>
                        <ul class="list-disc list-inside space-y-2 text-gray-600">
                            <li>Click vào nút "Đăng ký" trên thanh điều hướng</li>
                            <li>Điền đầy đủ thông tin cá nhân theo yêu cầu</li>
                            <li>Xác nhận email để hoàn tất quá trình đăng ký</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Đăng nhập</h3>
                        <ul class="list-disc list-inside space-y-2 text-gray-600">
                            <li>Click vào nút "Đăng nhập" trên thanh điều hướng</li>
                            <li>Nhập email và mật khẩu đã đăng ký</li>
                            <li>Hoặc đăng nhập nhanh bằng tài khoản Google</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Phần 2: Quản lý hoạt động -->
            <div class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">2. Tham gia hoạt động</h2>
                <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Xem danh sách hoạt động</h3>
                        <ul class="list-disc list-inside space-y-2 text-gray-600">
                            <li>Truy cập mục "Hoạt động" trên thanh điều hướng</li>
                            <li>Xem thông tin chi tiết về các hoạt động sắp diễn ra</li>
                            <li>Lọc hoạt động theo thời gian và loại hình</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Đăng ký tham gia</h3>
                        <ul class="list-disc list-inside space-y-2 text-gray-600">
                            <li>Click vào hoạt động muốn tham gia</li>
                            <li>Đọc kỹ thông tin và điều kiện tham gia</li>
                            <li>Nhấn nút "Đăng ký tham gia" và điền form thông tin</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Phần 3: Quản lý nhiệm vụ -->
            <div class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">3. Quản lý nhiệm vụ</h2>
                <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Xem nhiệm vụ được giao</h3>
                        <ul class="list-disc list-inside space-y-2 text-gray-600">
                            <li>Truy cập mục "Nhiệm vụ" trên thanh điều hướng</li>
                            <li>Xem danh sách nhiệm vụ và trạng thái</li>
                            <li>Cập nhật tiến độ thực hiện nhiệm vụ</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Hoàn thành nhiệm vụ</h3>
                        <ul class="list-disc list-inside space-y-2 text-gray-600">
                            <li>Click vào nhiệm vụ cần cập nhật</li>
                            <li>Điền thông tin và tải lên tài liệu liên quan</li>
                            <li>Đánh dấu hoàn thành và chờ xác nhận</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Phần 4: Hỗ trợ -->
            <div class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">4. Hỗ trợ</h2>
                <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Khi cần hỗ trợ</h3>
                        <ul class="list-disc list-inside space-y-2 text-gray-600">
                            <li>Tham gia nhóm Zalo hỗ trợ qua nút <a href="https://zalo.me/g/dqqnrd829" class="text-[#4a90e2]">"Nhóm hỗ trợ" </a></li>
                            <li>Liên hệ trực tiếp với ban chủ nhiệm CLB</li>
                            <li>Gửi email về địa chỉ: support@clbhstv.com</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
