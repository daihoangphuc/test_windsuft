<?php
$pageTitle = "Quản lý tài liệu";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/functions.php';

$auth = new Auth();
$auth->requireAdmin();

// Khởi tạo kết nối
$db = Database::getInstance();
$conn = $db->getConnection();

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$whereClause = '';
if (!empty($search)) {
    $whereClause = " WHERE TenTaiLieu LIKE ? OR MoTa LIKE ?";
}

// Lấy tổng số tài liệu
$total_query = "SELECT COUNT(*) as total FROM tailieu" . $whereClause;
if (!empty($search)) {
    $stmt = $conn->prepare($total_query);
    $searchParam = "%$search%";
    $stmt->bind_param("ss", $searchParam, $searchParam);
    $stmt->execute();
    $total_result = $stmt->get_result();
} else {
    $total_result = $conn->query($total_query);
}
$total_row = $total_result->fetch_assoc();
$total_documents = $total_row['total'];
$total_pages = ceil($total_documents / $limit);

// Lấy danh sách tài liệu
$query = "SELECT tl.*, nd.HoTen as NguoiTao 
          FROM tailieu tl 
          LEFT JOIN nguoidung nd ON tl.NguoiTaoId = nd.Id" . 
          $whereClause . 
          " ORDER BY tl.NgayTao DESC LIMIT ? OFFSET ?";

if (!empty($search)) {
    $stmt = $conn->prepare($query);
    $searchParam = "%$search%";
    $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<?php require_once __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="p-4">
    <div class="bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Quản lý tài liệu</h2>
            <button type="button" data-modal-target="documentModal" data-modal-toggle="documentModal" 
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Thêm tài liệu
            </button>
        </div>
        
        <!-- Search Form -->
        <form action="" method="GET" class="mb-4">
            <div class="flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                       placeholder="Tìm kiếm tài liệu...">
                <button type="submit" class="ml-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                    Tìm kiếm
                </button>
            </div>
        </form>

        <!-- Documents Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-4">
                        <h5 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($row['TenTaiLieu']); ?>
                        </h5>
                        <div class="flex gap-2">
                            <button data-document-id="<?php echo $row['Id']; ?>" 
                                    data-modal-target="editDocumentModal" 
                                    data-modal-toggle="editDocumentModal"
                                    class="text-blue-600 dark:text-blue-500 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button data-document-id="<?php echo $row['Id']; ?>" 
                                    class="delete-document text-red-600 dark:text-red-500 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
                        <?php echo nl2br(htmlspecialchars($row['MoTa'])); ?>
                    </p>
                    <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <span>Người tạo: <?php echo htmlspecialchars($row['NguoiTao']); ?></span>
                        <span><?php echo format_datetime($row['NgayTao']); ?></span>
                    </div>
                    <?php if ($row['FileDinhKem']): ?>
                        <a href="/test_windsuft/uploads/documents/<?php echo $row['FileDinhKem']; ?>" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300"
                           target="_blank">
                            Tải xuống
                            <svg class="w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-4">
            <nav aria-label="Page navigation">
                <ul class="inline-flex items-center -space-x-px">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="<?php echo $page === $i ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Document Modal -->
<div id="documentModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Thêm tài liệu mới
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="documentModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <form id="addDocumentForm" class="p-4 md:p-5" enctype="multipart/form-data">
                <div class="grid gap-4 mb-4">
                    <div class="col-span-2">
                        <label for="title" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tên tài liệu</label>
                        <input type="text" name="title" id="title" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                    </div>
                    <div class="col-span-2">
                        <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mô tả</label>
                        <textarea id="description" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                    </div>
                    <div class="col-span-2">
                        <label for="file" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">File đính kèm</label>
                        <input type="file" name="file" id="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" required>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">Chấp nhận các file: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)</p>
                    </div>
                </div>
                <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Thêm tài liệu
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Document Modal -->
<div id="editDocumentModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <!-- Similar structure to Add Document Modal, with pre-filled values -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Document Form Submit
    const addDocumentForm = document.getElementById('addDocumentForm');
    addDocumentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('add_document.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        });
    });

    // Delete Document
    const deleteButtons = document.querySelectorAll('.delete-document');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn xóa tài liệu này?')) {
                const documentId = this.dataset.documentId;
                fetch('delete_document.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ documentId: documentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Có lỗi xảy ra: ' + data.message);
                    }
                });
            }
        });
    });

    // Edit Document
    const editButtons = document.querySelectorAll('[data-modal-target="editDocumentModal"]');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const documentId = this.dataset.documentId;
            fetch('get_document.php?documentId=' + documentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fill form with document data
                        document.getElementById('edit-title').value = data.data.TenTaiLieu;
                        document.getElementById('edit-description').value = data.data.MoTa;
                        document.getElementById('edit-document-id').value = data.data.Id;
                    }
                });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
