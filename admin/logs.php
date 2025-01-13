<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$auth->requireAdmin();
logActivity('Truy cập quản lý log', 'Thành công', 'Xem trang quản lý log hệ thống');

$db = Database::getInstance()->getConnection();

// Thống kê real-time (5 phút gần nhất)
$realtimeStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN KetQua = 'Thành công' THEN 1 ELSE 0 END) as success,
        SUM(CASE WHEN KetQua = 'Thất bại' THEN 1 ELSE 0 END) as failed
    FROM log 
    WHERE NgayTao >= NOW() - INTERVAL 5 MINUTE
")->fetch_assoc();

// Thống kê theo giờ trong ngày
$hourlyStats = $db->query("
    SELECT 
        HOUR(NgayTao) as hour,
        COUNT(*) as count,
        SUM(CASE WHEN KetQua = 'Thành công' THEN 1 ELSE 0 END) as success,
        SUM(CASE WHEN KetQua = 'Thất bại' THEN 1 ELSE 0 END) as failed
    FROM log 
    WHERE DATE(NgayTao) = CURDATE()
    GROUP BY HOUR(NgayTao)
    ORDER BY hour
")->fetch_all(MYSQLI_ASSOC);

// Xử lý filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = "1=1";
$params = [];
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $whereClause .= " AND (NguoiDung LIKE ? OR HanhDong LIKE ? OR ChiTiet LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
    $types .= "sss";
}

if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
    $whereClause .= " AND NgayTao >= ?";
    $params[] = $_GET['from_date'] . " 00:00:00";
    $types .= "s";
}

if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $whereClause .= " AND NgayTao <= ?";
    $params[] = $_GET['to_date'] . " 23:59:59";
    $types .= "s";
}

if (isset($_GET['result']) && !empty($_GET['result'])) {
    $whereClause .= " AND KetQua = ?";
    $params[] = $_GET['result'];
    $types .= "s";
}

// Thống kê tổng quan
$stats = [
    'total_logs' => $db->query("SELECT COUNT(*) as count FROM log")->fetch_assoc()['count'],
    'success_logs' => $db->query("SELECT COUNT(*) as count FROM log WHERE KetQua = 'Thành công'")->fetch_assoc()['count'],
    'failed_logs' => $db->query("SELECT COUNT(*) as count FROM log WHERE KetQua = 'Thất bại'")->fetch_assoc()['count'],
    'today_logs' => $db->query("SELECT COUNT(*) as count FROM log WHERE DATE(NgayTao) = CURDATE()")->fetch_assoc()['count']
];

// Thống kê theo hành động
$action_stats = $db->query("
    SELECT HanhDong, COUNT(*) as count 
    FROM log 
    GROUP BY HanhDong 
    ORDER BY count DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Thống kê theo người dùng
$user_stats = $db->query("
    SELECT NguoiDung, COUNT(*) as count 
    FROM log 
    GROUP BY NguoiDung 
    ORDER BY count DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách log
$query = "SELECT * FROM log WHERE $whereClause ORDER BY NgayTao DESC LIMIT ? OFFSET ?";
$countQuery = "SELECT COUNT(*) as total FROM log WHERE $whereClause";

$stmt = $db->prepare($countQuery);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRecords = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

$stmt = $db->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types . "ii", ...[...$params, $limit, $offset]);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giám sát Hệ thống - Quản lý Log</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .monitor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        .monitor-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 1.5rem;
            color: #1f2937;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .success-text { color: #059669; }
        .warning-text { color: #d97706; }
        .danger-text { color: #dc2626; }
        .monitor-table {
            background: #ffffff;
            border-radius: 10px;
        }
        .monitor-table th {
            background: #f3f4f6;
            color: #374151;
        }
        .monitor-table td {
            border-bottom: 1px solid #e5e7eb;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-success { background: rgba(5, 150, 105, 0.1); color: #059669; }
        .status-error { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../layouts/admin_header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Nhật ký Hệ thống</h1>
            <div class="text-gray-600">
                <span id="currentTime"></span>
            </div>
        </div>

        <!-- Real-time Monitor -->
        <div class="monitor-grid mb-8">
            <div class="monitor-card">
                <div class="stat-label">HOẠT ĐỘNG THỜI GIAN THỰC (5 phút qua)</div>
                <div class="stat-value success-text"><?php echo $realtimeStats['total']; ?></div>
                <div class="flex justify-between text-sm">
                    <span class="success-text">✓ <?php echo $realtimeStats['success']; ?> thành công</span>
                    <span class="danger-text">✕ <?php echo $realtimeStats['failed']; ?> thất bại</span>
                </div>
            </div>
            <div class="monitor-card">
                <div class="stat-label">TỔNG SỐ HÔM NAY</div>
                <div class="stat-value warning-text"><?php echo $stats['today_logs']; ?></div>
                <div class="text-sm text-gray-600">Log được ghi nhận hôm nay</div>
            </div>
            <div class="monitor-card">
                <div class="stat-label">TÌNH TRẠNG HỆ THỐNG</div>
                <div class="stat-value success-text">
                    <?php 
                    $successRate = $stats['total_logs'] > 0 
                        ? round(($stats['success_logs'] / $stats['total_logs']) * 100) 
                        : 0;
                    echo $successRate . '%';
                    ?>
                </div>
                <div class="text-sm text-gray-600">Tỷ lệ thành công</div>
            </div>
        </div>

        <!-- Activity Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="monitor-card">
                <h3 class="text-lg font-semibold mb-4">Hoạt động theo giờ</h3>
                <canvas id="hourlyChart" height="200"></canvas>
            </div>
            <div class="monitor-card">
                <h3 class="text-lg font-semibold mb-4">Phân bố hành động</h3>
                <canvas id="actionChart" height="200"></canvas>
            </div>
        </div>

        <!-- Live Log Stream -->
        <div class="monitor-card mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Nhật ký hoạt động</h3>
                <div class="flex gap-2">
                    <button onclick="clearFilters()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Xóa bộ lọc
                    </button>
                    <button onclick="refreshLogs()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                        Làm mới
                    </button>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 bg-gray-100 p-4 rounded-lg">
                <div>
                    <label class="block text-sm text-gray-600 mb-2 whitespace-nowrap">Tìm kiếm</label>
                    <input type="text" name="search" id="search" value="<?php echo $_GET['search'] ?? ''; ?>" 
                           class="w-full bg-white border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2 whitespace-nowrap">Từ ngày</label>
                    <input type="datetime-local" name="from_date" id="from_date" value="<?php echo $_GET['from_date'] ?? ''; ?>"
                           class="w-full bg-white border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2 whitespace-nowrap">Đến ngày</label>
                    <input type="datetime-local" name="to_date" id="to_date" value="<?php echo $_GET['to_date'] ?? ''; ?>"
                           class="w-full bg-white border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2 whitespace-nowrap">Trạng thái</label>
                    <select name="result" id="result" class="w-full bg-white border-gray-300 rounded-lg">
                        <option value="">Tất cả</option>
                        <option value="Thành công" <?php echo (isset($_GET['result']) && $_GET['result'] == 'Thành công') ? 'selected' : ''; ?>>Thành công</option>
                        <option value="Thất bại" <?php echo (isset($_GET['result']) && $_GET['result'] == 'Thất bại') ? 'selected' : ''; ?>>Thất bại</option>
                    </select>
                </div>
            </div>

            <!-- Log Table -->
            <div class="overflow-x-auto monitor-table">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs uppercase whitespace-nowrap">Thời gian</th>
                            <th class="px-6 py-3 text-left text-xs uppercase whitespace-nowrap">IP</th>
                            <th class="px-6 py-3 text-left text-xs uppercase whitespace-nowrap">Người dùng</th>
                            <th class="px-6 py-3 text-left text-xs uppercase whitespace-nowrap">Hành động</th>
                            <th class="px-6 py-3 text-left text-xs uppercase whitespace-nowrap">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs uppercase whitespace-nowrap">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm">
                                <?php echo date('d/m/Y H:i:s', strtotime($log['NgayTao'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm"><?php echo $log['IP']; ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $log['NguoiDung']; ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $log['HanhDong']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge <?php echo $log['KetQua'] == 'Thành công' ? 'status-success' : 'status-error'; ?>">
                                    <?php echo $log['KetQua']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm"><?php echo $log['ChiTiet']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-600">
                    Hiển thị <?php echo $offset + 1; ?> đến <?php echo min($offset + $limit, $totalRecords); ?> 
                    trong tổng số <?php echo $totalRecords; ?> kết quả
                </div>
                
                <div class="flex justify-center">
                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        <?php
                        // Nút Previous
                        if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        <?php endif; ?>

                        <?php
                        // Hiển thị các số trang
                        $maxPagesToShow = 3;
                        $startPage = max(1, min($page - floor($maxPagesToShow/2), $totalPages - $maxPagesToShow + 1));
                        $endPage = min($startPage + $maxPagesToShow - 1, $totalPages);

                        // Trang đầu
                        if ($startPage > 1): ?>
                            <a href="?page=1" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>
                            <?php endif;
                        endif;

                        // Các trang giữa
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="relative inline-flex items-center px-4 py-2 text-sm font-semibold <?php echo ($i == $page) ? 'bg-indigo-600 text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;

                        // Trang cuối
                        if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $totalPages; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"><?php echo $totalPages; ?></a>
                        <?php endif; ?>

                        <?php 
                        // Nút Next
                        if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Cập nhật thời gian
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentTime').textContent = now.toLocaleDateString('vi-VN', options);
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Hàm tự động lọc khi thay đổi
        function autoFilter() {
            let searchParams = new URLSearchParams(window.location.search);
            
            // Lấy giá trị từ các trường input
            const search = document.getElementById('search').value;
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;
            const result = document.getElementById('result').value;
            
            // Cập nhật URL parameters
            if (search) searchParams.set('search', search);
            else searchParams.delete('search');
            
            if (fromDate) searchParams.set('from_date', fromDate);
            else searchParams.delete('from_date');
            
            if (toDate) searchParams.set('to_date', toDate);
            else searchParams.delete('to_date');
            
            if (result) searchParams.set('result', result);
            else searchParams.delete('result');
            
            // Reset về trang 1 khi lọc
            searchParams.delete('page');
            
            // Chuyển hướng với parameters mới
            window.location.href = window.location.pathname + '?' + searchParams.toString();
        }

        // Thêm event listeners cho các trường input
        document.getElementById('search').addEventListener('input', debounce(autoFilter, 500));
        document.getElementById('from_date').addEventListener('change', autoFilter);
        document.getElementById('to_date').addEventListener('change', autoFilter);
        document.getElementById('result').addEventListener('change', autoFilter);

        // Debounce function để tránh gọi quá nhiều request khi gõ
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Biểu đồ hoạt động theo giờ
        const hourlyData = <?php echo json_encode($hourlyStats); ?>;
        new Chart(document.getElementById('hourlyChart'), {
            type: 'line',
            data: {
                labels: hourlyData.map(item => item.hour + ':00'),
                datasets: [{
                    label: 'Thành công',
                    data: hourlyData.map(item => item.success),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Thất bại',
                    data: hourlyData.map(item => item.failed),
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Biểu đồ phân bố hành động
        const actionData = <?php echo json_encode($action_stats); ?>;
        new Chart(document.getElementById('actionChart'), {
            type: 'doughnut',
            data: {
                labels: actionData.map(item => item.HanhDong),
                datasets: [{
                    data: actionData.map(item => item.count),
                    backgroundColor: [
                        '#059669', '#d97706', '#3b82f6', '#dc2626', '#8b5cf6',
                        '#10b981', '#f59e0b', '#60a5fa', '#ef4444', '#a78bfa'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Hàm xử lý bộ lọc
        function clearFilters() {
            window.location.href = 'logs.php';
        }

        function refreshLogs() {
            location.reload();
        }

        // Tự động làm mới mỗi 30 giây
        setInterval(refreshLogs, 30000);
    </script>
</body>
</html>
