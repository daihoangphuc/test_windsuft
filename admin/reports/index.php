<?php
$pageTitle = "Báo cáo thống kê";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/functions.php';

$auth = new Auth();
$auth->requireAdmin();

// Khởi tạo kết nối
$db = Database::getInstance();
$conn = $db->getConnection();

// Xử lý filter
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01'); // Đầu tháng hiện tại
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t'); // Cuối tháng hiện tại

// Thống kê tổng quan
function get_statistics($conn, $startDate, $endDate) {
    // Thống kê thành viên
    $memberStats = $conn->query("
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN DATEDIFF(NOW(), NgayTao) <= 30 THEN 1 END) as new_members
        FROM nguoidung
        WHERE NgayTao <= '$endDate'
    ")->fetch_assoc();

    // Thống kê nhiệm vụ
    $taskStats = $conn->query("
        SELECT 
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN TrangThai = 0 THEN 1 END) as pending_tasks,
            COUNT(CASE WHEN TrangThai = 1 THEN 1 END) as in_progress_tasks,
            COUNT(CASE WHEN TrangThai = 2 THEN 1 END) as completed_tasks
        FROM nhiemvu
        WHERE NgayTao BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    // Thống kê tài chính
    $financeStats = $conn->query("
        SELECT 
            SUM(CASE WHEN LoaiGiaoDich = 0 THEN SoTien ELSE 0 END) as total_income,
            SUM(CASE WHEN LoaiGiaoDich = 1 THEN SoTien ELSE 0 END) as total_expense
        FROM taichinh
        WHERE NgayGiaoDich BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    // Thống kê tài liệu
    $documentStats = $conn->query("
        SELECT COUNT(*) as total_documents
        FROM tailieu
        WHERE NgayTao BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    // Thống kê tin tức
    $newsStats = $conn->query("
        SELECT COUNT(*) as total_news
        FROM tintuc
        WHERE NgayTao BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    return [
        'members' => $memberStats,
        'tasks' => $taskStats,
        'finance' => $financeStats,
        'documents' => $documentStats,
        'news' => $newsStats
    ];
}

// Lấy dữ liệu biểu đồ tài chính theo ngày
function get_finance_chart_data($conn, $startDate, $endDate) {
    $query = "
        SELECT 
            DATE(NgayGiaoDich) as date,
            SUM(CASE WHEN LoaiGiaoDich = 0 THEN SoTien ELSE 0 END) as income,
            SUM(CASE WHEN LoaiGiaoDich = 1 THEN SoTien ELSE 0 END) as expense
        FROM taichinh
        WHERE NgayGiaoDich BETWEEN ? AND ?
        GROUP BY DATE(NgayGiaoDich)
        ORDER BY date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $chartData = [];
    while ($row = $result->fetch_assoc()) {
        $chartData[] = $row;
    }
    
    return $chartData;
}

// Lấy dữ liệu biểu đồ nhiệm vụ theo trạng thái
function get_task_chart_data($conn) {
    $query = "
        SELECT 
            TrangThai,
            COUNT(*) as count
        FROM nhiemvu
        GROUP BY TrangThai
        ORDER BY TrangThai
    ";
    
    $result = $conn->query($query);
    $chartData = [0, 0, 0, 0]; // Khởi tạo mảng với 4 trạng thái
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        $chartData[$row['TrangThai']] = (int)$row['count'];
        $total += (int)$row['count'];
    }
    
    return [
        'data' => $chartData,
        'total' => $total
    ];
}

// Lấy dữ liệu biểu đồ phân công nhiệm vụ
function get_task_assignment_data($conn) {
    $query = "
        SELECT 
            u.HoTen,
            COUNT(DISTINCT pc.NhiemVuId) as TongNhiemVu,
            SUM(CASE WHEN nv.TrangThai = 0 THEN 1 ELSE 0 END) as ChuaBatDau,
            SUM(CASE WHEN nv.TrangThai = 1 THEN 1 ELSE 0 END) as DangThucHien,
            SUM(CASE WHEN nv.TrangThai = 2 THEN 1 ELSE 0 END) as HoanThanh,
            SUM(CASE WHEN nv.TrangThai = 3 THEN 1 ELSE 0 END) as QuaHan
        FROM nguoidung u
        LEFT JOIN phancongnhiemvu pc ON u.Id = pc.NguoiDungId
        LEFT JOIN nhiemvu nv ON pc.NhiemVuId = nv.Id
        WHERE u.VaiTroId = 2 AND pc.Id IS NOT NULL
        GROUP BY u.Id, u.HoTen
        ORDER BY TongNhiemVu DESC
        LIMIT 10
    ";
    
    $result = $conn->query($query);
    $data = [
        'labels' => [],
        'chuaBatDau' => [],
        'dangThucHien' => [],
        'hoanThanh' => [],
        'quaHan' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['HoTen'];
        $data['chuaBatDau'][] = (int)$row['ChuaBatDau'];
        $data['dangThucHien'][] = (int)$row['DangThucHien'];
        $data['hoanThanh'][] = (int)$row['HoanThanh'];
        $data['quaHan'][] = (int)$row['QuaHan'];
    }
    
    return $data;
}

// Lấy thống kê
$statistics = get_statistics($conn, $startDate, $endDate);
$financeChartData = get_finance_chart_data($conn, $startDate, $endDate);
$taskChartData = get_task_chart_data($conn);
$taskAssignmentData = get_task_assignment_data($conn);

require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4 bg-white block sm:flex items-center justify-between border-b border-gray-200">
    <div class="mb-1 w-full">
        <div class="mb-4">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Báo cáo thống kê</h1>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <form id="filterForm" class="flex items-center gap-4">
                    <div>
                        <label for="startDate" class="block mb-2 text-sm font-medium text-gray-900">Từ ngày</label>
                        <input type="date" id="startDate" name="startDate" value="<?php echo $startDate; ?>"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label for="endDate" class="block mb-2 text-sm font-medium text-gray-900">Đến ngày</label>
                        <input type="date" id="endDate" name="endDate" value="<?php echo $endDate; ?>"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Lọc
                        </button>
                    </div>
                </form>
            </div>
            <div class="flex items-center gap-4">
                <a href="export.php?startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>" 
                   class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg focus:ring-4 focus:ring-green-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Xuất báo cáo Excel
                </a>
            </div>
        </div>
    </div>
</div>

<div class="p-4">
    <div class="mb-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Member Statistics -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Thành viên mới (30 Ngày)</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($statistics['members']['new_members']); ?></p>
            </div>

            <!-- Task Statistics -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hoạt động mới (30 Ngày)</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo number_format($statistics['tasks']['total_tasks']); ?></p>
            </div>

            <!-- Financial Statistics -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Thu</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo format_money($statistics['finance']['total_income']); ?></p>
            </div>

            <!-- Expense Statistics -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Chi</h3>
                <p class="text-3xl font-bold text-red-600"><?php echo format_money($statistics['finance']['total_expense']); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
    <!-- Financial Chart -->
    <div class="col-span-3 bg-white rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Biểu đồ thu chi</h3>
        <canvas id="financeChart"></canvas>
    </div>

<!-- Task Status Chart -->
<div class="col-span-1 bg-white rounded-lg shadow p-4 flex flex-col items-center">
    <h3 class="text-lg font-semibold text-gray-900 mb-10 text-center">Biểu đồ trạng thái nhiệm vụ</h3>
    <div class="relative w-full" style="height: 400px;">
        <canvas id="taskChart"></canvas>
        <!-- Phần nội dung overlay -->
        <div class="absolute mt-[-100px] inset-0 flex flex-col items-center justify-center text-center">
            <div class="text-3xl font-bold text-gray-700"><?php echo $taskChartData['total']; ?></div>
            <div class="text-sm text-gray-500">Tổng nhiệm vụ</div>
        </div>
    </div>
</div>

</div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Finance Chart
const financeCtx = document.getElementById('financeChart').getContext('2d');
new Chart(financeCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($financeChartData, 'date')); ?>,
        datasets: [{
            label: 'Thu',
            data: <?php echo json_encode(array_column($financeChartData, 'income')); ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.5)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1
        }, {
            label: 'Chi',
            data: <?php echo json_encode(array_column($financeChartData, 'expense')); ?>,
            backgroundColor: 'rgba(239, 68, 68, 0.5)',
            borderColor: 'rgb(239, 68, 68)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});


// Task Chart
const taskCtx = document.getElementById('taskChart').getContext('2d');
new Chart(taskCtx, {
    type: 'doughnut',
    data: {
        labels: ['Chưa bắt đầu', 'Đang thực hiện', 'Hoàn thành', 'Quá hạn'],
        datasets: [{
            data: <?php echo json_encode($taskChartData['data']); ?>,
            backgroundColor: ['#94a3b8', '#3b82f6', '#22c55e', '#ef4444'],
            borderWidth: 0,
            cutout: '70%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return `${context.label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Task Assignment Chart
const taskAssignmentCtx = document.getElementById('taskAssignmentChart').getContext('2d');
new Chart(taskAssignmentCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($taskAssignmentData['labels']); ?>,
        datasets: [
            {
                label: 'Chưa bắt đầu',
                data: <?php echo json_encode($taskAssignmentData['chuaBatDau']); ?>,
                backgroundColor: '#94a3b8',
                borderWidth: 0
            },
            {
                label: 'Đang thực hiện',
                data: <?php echo json_encode($taskAssignmentData['dangThucHien']); ?>,
                backgroundColor: '#3b82f6',
                borderWidth: 0
            },
            {
                label: 'Hoàn thành',
                data: <?php echo json_encode($taskAssignmentData['hoanThanh']); ?>,
                backgroundColor: '#22c55e',
                borderWidth: 0
            },
            {
                label: 'Quá hạn',
                data: <?php echo json_encode($taskAssignmentData['quaHan']); ?>,
                backgroundColor: '#ef4444',
                borderWidth: 0
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                stacked: true,
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            },
            y: {
                stacked: true,
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Số lượng nhiệm vụ'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        }
    }
});
</script>

<script>
function exportReport() {
    const startDate = document.querySelector('input[name="startDate"]').value;
    const endDate = document.querySelector('input[name="endDate"]').value;
    window.location.href = '/manage-htsv/admin/reports/export.php?startDate=' + startDate + '&endDate=' + endDate;
}

document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const startDate = this.elements.startDate.value;
    const endDate = this.elements.endDate.value;
    window.location.href = 'index.php?startDate=' + startDate + '&endDate=' + endDate;
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
