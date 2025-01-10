<?php
$pageTitle = "Tra cứu tài liệu";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../utils/functions.php';

$auth = new Auth();
$auth->requireLogin();

// Khởi tạo kết nối
$db = Database::getInstance();
$conn = $db->getConnection();

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Lấy danh sách loại tài liệu
$typeQuery = "SELECT DISTINCT LoaiTaiLieu FROM tailieu WHERE LoaiTaiLieu IS NOT NULL ORDER BY LoaiTaiLieu";
$types = $conn->query($typeQuery)->fetch_all(MYSQLI_ASSOC);

// Xây dựng query
$baseQuery = "
    FROM tailieu t
    LEFT JOIN nguoidung n ON t.NguoiTaoId = n.Id
    LEFT JOIN phanquyentailieu p ON t.Id = p.TaiLieuId
    WHERE (p.VaiTroId = ? OR p.VaiTroId IS NULL)
";

$params = [$_SESSION['role_id']];
$types = "i";

if (!empty($search)) {
    $baseQuery .= " AND (t.TenTaiLieu LIKE ? OR t.MoTa LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if (!empty($type)) {
    $baseQuery .= " AND t.LoaiTaiLieu = ?";
    $params[] = $type;
    $types .= "s";
}

// Đếm tổng số tài liệu
$countQuery = "SELECT COUNT(DISTINCT t.Id) as total " . $baseQuery;
$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

// Phân trang
$limit = 5; // Changed from 10 to 5 items per page
$totalPages = ceil($total / $limit);
$page = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$offset = ($page - 1) * $limit;

// Lấy danh sách tài liệu
$query = "
    SELECT DISTINCT 
        t.Id,
        t.TenTaiLieu,
        t.MoTa,
        t.DuongDan,
        t.LoaiTaiLieu,
        t.NgayTao,
        n.HoTen as NguoiTao
    " . $baseQuery . "
    ORDER BY t.NgayTao DESC 
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#4a90e2] mb-2">Tra cứu tài liệu</h2>
        <p class="text-gray-600">Tìm kiếm và tải xuống các tài liệu của CLB</p>
    </div>

    <!-- Search Form -->
    <form method="GET" class="mb-6 bg-white p-4 rounded-lg shadow-md">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Input Từ khóa -->
            <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    Từ khóa
                </label>
                <input 
                    type="text" 
                    name="search" 
                    id="search" 
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Nhập tên tài liệu hoặc mô tả..."
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <!-- Dropdown Loại tài liệu -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                    Loại tài liệu
                </label>
                <select 
                    name="type" 
                    id="type" 
                    class="w-full h-10 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Tất cả</option>
                    <?php 
                    $types1 = ["Văn bản", "Biểu mẫu", "Báo cáo", "Tài liệu khác"];
                    foreach ($types1 as $t): 
                    ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Nút Tìm kiếm -->
            <div class="flex items-end">
                <button 
                    type="submit"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-150 ease-in-out flex items-center justify-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Tìm kiếm
                </button>
            </div>
        </div>
    </form>

    <!-- Documents List -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <?php if (empty($documents)): ?>
            <div class="p-6 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-2 text-sm">Không tìm thấy tài liệu nào</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($documents as $doc): ?>
                    <div class="p-4 sm:p-6 hover:bg-gray-50 transition duration-150 ease-in-out">
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                    <?php echo htmlspecialchars($doc['TenTaiLieu']); ?>
                                </h3>
                                <?php if (!empty($doc['MoTa'])): ?>
                                    <p class="text-sm text-gray-600 mb-2 line-clamp-2">
                                        <?php echo htmlspecialchars($doc['MoTa']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-3 text-sm text-gray-500">
                                    <?php if (!empty($doc['LoaiTaiLieu'])): ?>
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                            <?php echo htmlspecialchars($doc['LoaiTaiLieu']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <?php echo date('d/m/Y H:i', strtotime($doc['NgayTao'])); ?>
                                    </span>
                                    <?php if (!empty($doc['NguoiTao'])): ?>
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <?php echo htmlspecialchars($doc['NguoiTao']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-shrink-0 w-full sm:w-auto">
                                <a href="<?php echo htmlspecialchars($doc['DuongDan']); ?>" 
                                   target="_blank"
                                   class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 hover:bg-blue-600 hover:text-white transition duration-150 ease-in-out">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Tải xuống
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Trước
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Sau
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Hiển thị
                                <span class="font-medium"><?php echo ($offset + 1); ?></span>
                                đến
                                <span class="font-medium"><?php echo min($offset + $limit, $total); ?></span>
                                trong số
                                <span class="font-medium"><?php echo $total; ?></span>
                                kết quả
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Trang trước</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $range = 2;
                                $rangeStart = max(1, $page - $range);
                                $rangeEnd = min($totalPages, $page + $range);

                                if ($rangeStart > 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }

                                for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
                                    $isCurrentPage = $i === $page;
                                    ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $isCurrentPage ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-700'; ?> text-sm font-medium hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php
                                }

                                if ($rangeEnd < $totalPages) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Trang sau</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
