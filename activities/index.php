<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();

// Xử lý tìm kiếm và phân trang cho view danh sách
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query cho view danh sách
$query = "SELECT h.*, 
          COUNT(DISTINCT dk.Id) as TongDangKy,
          COUNT(DISTINCT dt.Id) as TongThamGia,
          CASE WHEN EXISTS (
              SELECT 1 FROM danhsachdangky dk2 
              WHERE dk2.HoatDongId = h.Id 
              AND dk2.NguoiDungId = ? 
              AND dk2.TrangThai = 1
          ) THEN 1 ELSE 0 END as DaDangKy
          FROM hoatdong h 
          LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
          LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id
          WHERE h.TrangThai = 0";

if ($search) {
    $search_term = "%$search%";
    $query .= " AND (h.TenHoatDong LIKE ? OR h.MoTa LIKE ? OR h.DiaDiem LIKE ?)";
}

$query .= " GROUP BY h.Id ORDER BY h.NgayBatDau ASC LIMIT ? OFFSET ?";

// Lấy danh sách hoạt động cho view danh sách
if ($search) {
    $stmt = $db->prepare($query);
    $stmt->bind_param("isssii", $_SESSION['user_id'], $search_term, $search_term, $search_term, $limit, $offset);
} else {
    $stmt = $db->prepare($query);
    $stmt->bind_param("iii", $_SESSION['user_id'], $limit, $offset);
}
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Lịch hoạt động';
require_once '../layouts/header.php';
?>

<!-- FullCalendar Bundle -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<div class="p-4">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Lịch hoạt động</h2>
        <p class="mt-1 text-sm text-gray-600">Xem tất cả các hoạt động sắp diễn ra</p>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button onclick="switchTab('calendar-view')" 
                        class="inline-block p-4 border-b-2 rounded-t-lg tab-button active" 
                        id="calendar-tab">
                    Lịch
                </button>
            </li>
            <li class="mr-2">
                <button onclick="switchTab('list-view')"
                        class="inline-block p-4 border-b-2 border-transparent rounded-t-lg tab-button" 
                        id="list-tab">
                    Danh sách
                </button>
            </li>
        </ul>
    </div>

    <!-- Calendar View -->
    <div id="calendar-view" class="tab-content block">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div id="calendar" style="min-height: 700px;"></div>
        </div>
    </div>

    <!-- List View -->
    <div id="list-view" class="tab-content hidden">
        <!-- Search Form -->
        <form method="GET" class="mb-6">
            <div class="flex gap-4">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm kiếm hoạt động..." 
                       class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                    Tìm kiếm
                </button>
            </div>
        </form>

        <!-- Activities Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($activities as $activity): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
                        </h3>
                        <span class="px-2 py-1 text-xs font-semibold rounded 
                        <?php 
                            if ($activity['TrangThai'] == 0) {
                                echo 'bg-blue-100 text-blue-800';
                            } elseif ($activity['TrangThai'] == 1) {
                                echo 'bg-green-100 text-green-800';
                            } else {
                                echo 'bg-gray-100 text-gray-800';
                            }
                        ?>">
                        <?php 
                            if ($activity['TrangThai'] == 0) {
                                echo 'Sắp diễn ra';
                            } elseif ($activity['TrangThai'] == 1) {
                                echo 'Đang diễn ra';
                            } else {
                                echo 'Đã kết thúc';
                            }
                        ?>
                        </span>
                    </div>
                    
                    <div class="space-y-3 text-gray-600 text-sm">
                        <p class="line-clamp-2"><?php echo htmlspecialchars($activity['MoTa']); ?></p>
                        
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt w-5"></i>
                            <span>Bắt đầu: <?php echo date('d/m/Y H:i', strtotime($activity['NgayBatDau'])); ?></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-calendar-check w-5"></i>
                            <span>Kết thúc: <?php echo date('d/m/Y H:i', strtotime($activity['NgayKetThuc'])); ?></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt w-5"></i>
                            <span><?php echo htmlspecialchars($activity['DiaDiem']); ?></span>
                        </div>

                        <?php if (isset($activity['ToaDo']) && $activity['ToaDo']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-location-arrow w-5"></i>
                            <a href="https://www.google.com/maps?q=<?php echo urlencode($activity['ToaDo']); ?>" 
                               target="_blank" 
                               class="text-blue-600 hover:text-blue-800">
                                Xem bản đồ
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center">
                            <i class="fas fa-users w-5"></i>
                            <span>
                                <?php echo $activity['TongDangKy']; ?>/<?php echo $activity['SoLuong'] ?? 'Không giới hạn'; ?> đã đăng ký
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between items-center">
                        <a href="view.php?id=<?php echo $activity['Id']; ?>" 
                           class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Xem chi tiết
                        </a>
                        
                        <!-- <?php if ($activity['TrangThai'] == 0): ?>
                            <?php if ($activity['DaDangKy']): ?>
                                <button onclick="cancelRegistration(<?php echo $activity['Id']; ?>)"
                                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                                    Hủy đăng ký
                                </button>
                            <?php else: ?>
                                <button onclick="register(<?php echo $activity['Id']; ?>)"
                                        class="hidden px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                                    Đăng ký
                                </button>
                            <?php endif; ?>
                        <?php endif; ?> -->
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Tab switching function
function switchTab(tabId) {
    console.log('Switching to tab:', tabId);
    
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Show selected tab content
    const selectedTab = document.getElementById(tabId);
    if (selectedTab) {
        selectedTab.style.display = 'block';
        console.log('Tab displayed:', tabId);
    } else {
        console.error('Tab not found:', tabId);
    }
    
    // Update tab button styles
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-600', 'text-blue-600');
        button.classList.add('border-transparent');
    });
    
    // Add active class to clicked tab
    const activeButton = tabId === 'calendar-view' ? 'calendar-tab' : 'list-tab';
    const button = document.getElementById(activeButton);
    if (button) {
        button.classList.add('active', 'border-blue-600', 'text-blue-600');
        button.classList.remove('border-transparent');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Calendar initialization
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'vi',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '' 
        },
        buttonText: {
            today: 'Hôm nay'
        },
        events: 'get_calendar_events.php',
        eventClick: function(info) {
            window.location.href = 'view.php?id=' + info.event.id;
        },
        eventDidMount: function(info) {
            info.el.title = info.event.title + '\nĐịa điểm: ' + info.event.extendedProps.location;
        },
        height: 'auto',
        firstDay: 1,
        displayEventTime: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false,
            hour12: false
        }
    });
    calendar.render();
    
    // Initialize first tab
    switchTab('calendar-view');
});

// Activity registration functions
function showFlashMessage(message, type = 'success') {
    // Implementation of showFlashMessage function
}

function register(activityId) {
    if (confirm('Bạn có chắc chắn muốn đăng ký tham gia hoạt động này?')) {
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ activityId: activityId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFlashMessage('Đăng ký thành công');
                setTimeout(() => location.reload(), 1000);
            } else {
                showFlashMessage(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            showFlashMessage('Có lỗi xảy ra', 'error');
        });
    }
}

function cancelRegistration(activityId) {
    if (confirm('Bạn có chắc chắn muốn hủy đăng ký tham gia hoạt động này?')) {
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                activityId: activityId,
                action: 'cancel'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFlashMessage('Hủy đăng ký thành công');
                setTimeout(() => location.reload(), 1000);
            } else {
                showFlashMessage(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            showFlashMessage('Có lỗi xảy ra', 'error');
        });
    }
}
</script>

<style>
/* Calendar styles */
.fc {
    --fc-border-color: #e5e7eb;
    --fc-button-bg-color: #3B82F6;
    --fc-button-border-color: #3B82F6;
    --fc-button-hover-bg-color: #2563EB;
    --fc-button-hover-border-color: #2563EB;
    --fc-button-active-bg-color: #1D4ED8;
    --fc-button-active-border-color: #1D4ED8;
    --fc-today-bg-color: #EFF6FF;
    --fc-event-bg-color: #3B82F6;
    --fc-event-border-color: #3B82F6;
}

/* Responsive Calendar Styles */
@media (max-width: 768px) {
    .fc .fc-toolbar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .fc .fc-toolbar-title {
        font-size: 1.1rem;
        margin: 0;
    }

    .fc .fc-toolbar-chunk {
        display: flex;
        justify-content: center;
        width: 100%;
    }

    .fc .fc-button {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }

    .fc .fc-view-harness {
        height: auto !important;
    }

    .fc .fc-daygrid-day-number {
        font-size: 0.875rem;
        padding: 0.25rem;
    }

    .fc .fc-daygrid-day-events {
        min-height: auto;
    }

    .fc .fc-event {
        font-size: 0.75rem;
        line-height: 1.2;
    }

    /* Ẩn một số phần tử trên mobile để tối ưu không gian */
    .fc .fc-daygrid-week-number,
    .fc .fc-timegrid-axis-cushion {
        display: none;
    }

    /* Điều chỉnh padding cho cells */
    .fc td, .fc th {
        padding: 1px;
    }
}

/* Điều chỉnh cho màn hình rất nhỏ */
@media (max-width: 480px) {
    .fc .fc-toolbar {
        gap: 0.5rem;
    }

    .fc .fc-toolbar-title {
        font-size: 1rem;
    }

    .fc .fc-button-group {
        gap: 0.25rem;
    }

    .fc .fc-button {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
    }

    /* Ẩn text của các nút không quan trọng */
    .fc .fc-button:not(.fc-prev-button):not(.fc-next-button):not(.fc-today-button) {
        padding: 0.3rem;
    }

    /* Giảm padding của container */
    #calendar-view .bg-white {
        padding: 0.5rem !important;
    }
}

.fc .fc-button {
    padding: 0.5rem 1rem;
    font-weight: 500;
    border-radius: 0.375rem;
}

.fc .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1F2937;
}

.fc-theme-standard td, .fc-theme-standard th {
    border-color: #e5e7eb;
}

.fc-event {
    border-radius: 0.25rem;
    padding: 2px 4px;
    font-size: 0.875rem;
}

/* Tab styles */
.tab-button {
    position: relative;
    transition: all 0.3s;
}

.tab-button.active {
    border-bottom-width: 2px;
    border-color: #3B82F6;
    color: #3B82F6;
}

.tab-button:hover {
    color: #4B5563;
    border-color: #E5E7EB;
}

.tab-content {
    transition: all 0.3s ease;
}

/* Responsive container padding */
@media (max-width: 768px) {
    .p-4 {
        padding: 0.5rem;
    }
    
    .p-6 {
        padding: 1rem;
    }
    
    .mb-6 {
        margin-bottom: 1rem;
    }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
