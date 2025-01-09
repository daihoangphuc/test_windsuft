<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance()->getConnection();

// Get member ID from URL
$memberId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($memberId === 0) {
    header('Location: index.php');
    exit;
}

// Fetch member details with additional information
$stmt = $db->prepare("SELECT n.*, l.TenLop, c.TenChucVu,
                      (SELECT COUNT(*) FROM danhsachdangky WHERE NguoiDungId = n.Id AND TrangThai = 1) as TongDangKy,
                      (SELECT COUNT(*) FROM danhsachthamgia WHERE NguoiDungId = n.Id AND TrangThai = 1) as TongThamGia
                      FROM nguoidung n 
                      LEFT JOIN lophoc l ON n.LopHocId = l.Id
                      LEFT JOIN chucvu c ON n.ChucVuId = c.Id 
                      WHERE n.Id = ?");
$stmt->bind_param("i", $memberId);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    header('Location: index.php');
    exit;
}

$pageTitle = "Thông tin " . htmlspecialchars($member['HoTen']);
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container mx-auto py-8 lg:px-8">
    <div class="bg-white rounded-lg overflow-hidden" style="margin-top: -50px">
        <div class="flex flex-col md:flex-row">
            <!-- Sidebar với ảnh đại diện -->
            <div class="w-full md:w-1/3 p-4 bg-gray-50 md:mt-[90px]">
                <div class="text-center">
                    <img src="<?php echo str_replace('../', BASE_URL . '/', $member['anhdaidien']); ?>" 
                         alt="<?php echo htmlspecialchars($member['HoTen']); ?>"
                         class="w-32 h-32 md:w-48 md:h-48 rounded-full mx-auto mb-4 object-cover border-4 border-blue-500">
                    <h2 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($member['HoTen']); ?></h2>
                    <p class="text-blue-600 font-medium"><?php echo htmlspecialchars($member['TenChucVu']); ?></p>
                </div>
            </div>

            <!-- Main content -->
            <div class="w-full md:w-2/3 p-4 md:p-6">
                <h2 class="text-2xl font-bold mb-6">Thông tin thành viên Ban chủ nhiệm</h2>
                
                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="profile-tab" data-tabs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                Thông tin chung
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="activities-tab" data-tabs-target="#activities" type="button" role="tab" aria-controls="activities" aria-selected="false">
                                Hoạt động
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Tab Thông tin chung -->
                <div id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Mã sinh viên</label>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                <?php echo htmlspecialchars($member['MaSinhVien']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Họ tên</label>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                <?php echo htmlspecialchars($member['HoTen']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                <?php echo htmlspecialchars($member['Email']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Ngày sinh</label>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                <?php echo date('d/m/Y', strtotime($member['NgaySinh'])); ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Giới tính</label>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                <?php echo $member['GioiTinh'] == 1 ? 'Nam' : 'Nữ'; ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Lớp</label>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                <?php echo htmlspecialchars($member['TenLop']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Chức vụ</label>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                <?php echo htmlspecialchars($member['TenChucVu']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Hoạt động -->
                <div id="activities" class="hidden" role="tabpanel" aria-labelledby="activities-tab">
                    <div class="bg-white p-4 md:p-6 rounded-lg">
                        <h3 class="text-xl md:text-2xl font-bold mb-6 text-gray-800">Thống kê hoạt động</h3>
                        <div class="w-full">
                            <!-- Thông tin phụ -->
                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <p class="text-sm text-gray-600">Tỷ lệ tham gia</p>
                                        <p class="text-xl md:text-2xl font-bold text-blue-600">
                                            <?php 
                                            $ratio = $member['TongDangKy'] > 0 
                                                ? round(($member['TongThamGia'] / $member['TongDangKy']) * 100, 1) 
                                                : 0; 
                                            echo $ratio . '%';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                        <p class="text-sm text-gray-600">Hoạt động tích cực</p>
                                        <p class="text-xl md:text-2xl font-bold text-green-600">
                                            <?php echo $member['TongThamGia']; ?> lần
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Progress bars -->
                            <div class="space-y-4">
                                <div class="flex flex-col md:flex-row md:items-center">
                                    <span class="text-sm font-medium text-gray-600 mb-2 md:mb-0 md:mr-2 md:w-32">Tổng đăng ký:</span>
                                    <div class="relative w-full md:w-[300px] h-9 bg-blue-100 rounded-lg">
                                        <?php 
                                        $dangKyPercent = ($member['TongDangKy'] / max($member['TongDangKy'], $member['TongThamGia'])) * 100;
                                        $isFullDangKy = $dangKyPercent >= 100;
                                        ?>
                                        <div class="absolute top-0 h-full bg-blue-600 rounded-lg transition-all duration-1000" 
                                             style="width: <?php echo $dangKyPercent; ?>%">
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-end pr-2">
                                            <span class="text-sm font-semibold <?php echo $isFullDangKy ? 'text-white' : 'text-blue-600'; ?>">
                                                <?php echo $member['TongDangKy']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center">
                                    <span class="text-sm font-medium text-gray-600 mb-2 md:mb-0 md:mr-2 md:w-32">Tổng tham gia:</span>
                                    <div class="relative w-full md:w-[300px] h-9 bg-green-100 rounded-lg">
                                        <?php 
                                        $thamGiaPercent = ($member['TongThamGia'] / max($member['TongDangKy'], $member['TongThamGia'])) * 100;
                                        $isFullThamGia = $thamGiaPercent >= 100;
                                        ?>
                                        <div class="absolute top-0 h-full bg-green-600 rounded-lg transition-all duration-1000" 
                                             style="width: <?php echo $thamGiaPercent; ?>%">
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-end pr-2">
                                            <span class="text-sm font-semibold <?php echo $isFullThamGia ? 'text-white' : 'text-green-600'; ?>">
                                                <?php echo $member['TongThamGia']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-8">
        <a href="index.php" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại trang chủ
        </a>
    </div>
</div>

<script>
// Tab switching
const tabElements = [
    {
        id: 'profile-tab',
        triggerEl: document.querySelector('#profile-tab'),
        targetEl: document.querySelector('#profile')
    },
    {
        id: 'activities-tab',
        triggerEl: document.querySelector('#activities-tab'),
        targetEl: document.querySelector('#activities')
    }
];

// Add click event to tabs
tabElements.forEach(tab => {
    tab.triggerEl.addEventListener('click', e => {
        e.preventDefault();
        
        // Hide all tabs
        tabElements.forEach(t => {
            t.targetEl.classList.add('hidden');
            t.triggerEl.classList.remove('border-blue-600', 'text-blue-600');
            t.triggerEl.classList.add('border-transparent');
        });
        
        // Show active tab
        tab.targetEl.classList.remove('hidden');
        tab.triggerEl.classList.add('border-blue-600', 'text-blue-600');
    });
});

// Animation cho các thanh tiến trình
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('[role="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            if (tab.id === 'activities-tab') {
                // Reset và chạy animation khi tab được chọn
                const bars = document.querySelectorAll('.bg-blue-600, .bg-green-600');
                bars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 50);
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
