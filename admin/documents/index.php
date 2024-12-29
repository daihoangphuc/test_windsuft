<?php
$pageTitle = "Quản lý tài liệu";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/functions.php';
require_once __DIR__ . '/../../utils/pagination.php';

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

$pagination = new Pagination($total_documents, $limit);

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
                    Tìm
                </button>
            </div>
        </form>

        <!-- Documents Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow">
                    <div class="flex justify-between items-start mb-4">
                        <h5 class="text-xl font-bold tracking-tight text-gray-900">
                            <?php echo htmlspecialchars($row['TenTaiLieu']); ?>
                        </h5>
                        <div class="flex gap-2">
                            <a href="<?php echo htmlspecialchars($row['DuongDan']); ?>" 
                               target="_blank"
                               class="text-blue-600 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                            <button data-document-id="<?php echo $row['Id']; ?>" 
                                    data-modal-target="editDocumentModal" 
                                    data-modal-toggle="editDocumentModal"
                                    class="text-blue-600 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button onclick="deleteDocument(<?php echo $row['Id']; ?>)" 
                                    class="text-red-600 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="mb-3 font-normal text-gray-700 ">
                        <?php echo nl2br(htmlspecialchars($row['MoTa'])); ?>
                    </p>
                    <div class="flex justify-between items-center text-sm text-gray-600">
                        <span>Loại: <?php echo htmlspecialchars($row['LoaiTaiLieu']); ?></span>
                        <span>Người tạo: <?php echo htmlspecialchars($row['NguoiTao']); ?></span>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($row['NgayTao'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php echo $pagination->renderPagination($_SERVER['PHP_SELF'], $page, $limit, $search); ?>
    </div>
</div>

<!-- Add Document Modal -->
<div id="documentModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
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
            <form id="addDocumentForm" class="p-4 md:p-5" enctype="multipart/form-data" method="POST">
                <div class="grid gap-4 mb-4">
                    <div class="col-span-2">
                        <label for="title" class="block mb-2 text-sm font-medium text-gray-900">Tên tài liệu</label>
                        <input type="text" name="title" id="title" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                    </div>
                    <div class="col-span-2">
                        <label for="description" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                        <textarea id="description" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                    </div>
                    <div class="col-span-2">
                        <label for="documentType" class="block mb-2 text-sm font-medium text-gray-900">Loại tài liệu</label>
                        <select id="documentType" name="documentType" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <option value="">Chọn loại tài liệu</option>
                            <option value="Văn bản">Văn bản</option>
                            <option value="Biểu mẫu">Biểu mẫu</option>
                            <option value="Báo cáo">Báo cáo</option>
                            <option value="Tài liệu khác">Tài liệu khác</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="file" class="block mb-2 text-sm font-medium text-gray-900">File đính kèm</label>
                        <input type="file" name="file" id="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" required>
                        <p class="mt-1 text-sm text-gray-500">Chấp nhận các file: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                        Thêm tài liệu
                    </button>
                    <button type="button" data-modal-hide="documentModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Document Modal -->
<div id="editDocumentModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Sửa tài liệu
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="editDocumentModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <form id="editDocumentForm" class="p-4 md:p-5" enctype="multipart/form-data">
                <input type="hidden" name="documentId" id="editDocumentId">
                <div class="grid gap-4 mb-4">
                    <div class="col-span-2">
                        <label for="editTitle" class="block mb-2 text-sm font-medium text-gray-900">Tên tài liệu</label>
                        <input type="text" name="title" id="editTitle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                    </div>
                    <div class="col-span-2">
                        <label for="editDescription" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                        <textarea id="editDescription" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                    </div>
                    <div class="col-span-2">
                        <label for="editDocumentType" class="block mb-2 text-sm font-medium text-gray-900">Loại tài liệu</label>
                        <select id="editDocumentType" name="documentType" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <option value="">Chọn loại tài liệu</option>
                            <option value="Văn bản">Văn bản</option>
                            <option value="Biểu mẫu">Biểu mẫu</option>
                            <option value="Báo cáo">Báo cáo</option>
                            <option value="Tài liệu khác">Tài liệu khác</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="editFile" class="block mb-2 text-sm font-medium text-gray-900">File đính kèm mới (không bắt buộc)</label>
                        <input type="file" name="file" id="editFile" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                        <p class="mt-1 text-sm text-gray-500">Chấp nhận các file: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Cập nhật
                    </button>
                    <button type="button" data-modal-hide="editDocumentModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Document Form Submit
    const addDocumentForm = document.getElementById('addDocumentForm');
    if (addDocumentForm) {
        addDocumentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate form
            const title = this.querySelector('#title').value.trim();
            const description = this.querySelector('#description').value.trim();
            const documentType = this.querySelector('#documentType').value;
            const file = this.querySelector('#file').files[0];
            
            if (!title || !description || !documentType || !file) {
                alert('Vui lòng điền đầy đủ thông tin và chọn file');
                return;
            }
            
            const formData = new FormData(this);
            
            // Disable submit button and show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                 </svg>
                                 Đang xử lý...`;
            
            try {
                const response = await fetch('add_document.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Server response:', data);
                
                if (data.success) {
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Có lỗi xảy ra khi gửi yêu cầu');
            } finally {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    // Edit Document Form Submit
    const editDocumentForm = document.getElementById('editDocumentForm');
    if (editDocumentForm) {
        editDocumentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate form
            const title = this.querySelector('#editTitle').value.trim();
            const description = this.querySelector('#editDescription').value.trim();
            const documentType = this.querySelector('#editDocumentType').value;
            
            if (!title || !description || !documentType) {
                alert('Vui lòng điền đầy đủ thông tin');
                return;
            }
            
            const formData = new FormData(this);
            
            // Disable submit button and show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                 </svg>
                                 Đang xử lý...`;
            
            try {
                const response = await fetch('edit_document.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Server response:', data);
                
                if (data.success) {
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Có lỗi xảy ra khi gửi yêu cầu');
            } finally {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    // Delete Document
    window.deleteDocument = async function(documentId) {
        if (confirm('Bạn có chắc chắn muốn xóa tài liệu này?')) {
            try {
                const response = await fetch('delete_document.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ documentId: documentId })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Có lỗi xảy ra khi xóa tài liệu');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Có lỗi xảy ra khi xóa tài liệu');
            }
        }
    };

    // Load document data for editing
    const editButtons = document.querySelectorAll('[data-modal-target="editDocumentModal"]');
    editButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const documentId = this.getAttribute('data-document-id');
            try {
                const response = await fetch(`get_document.php?id=${documentId}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await response.json();
                if (data.success) {
                    document.getElementById('editDocumentId').value = data.document.Id;
                    document.getElementById('editTitle').value = data.document.TenTaiLieu;
                    document.getElementById('editDescription').value = data.document.MoTa;
                    document.getElementById('editDocumentType').value = data.document.LoaiTaiLieu;
                } else {
                    throw new Error(data.message || 'Không thể tải thông tin tài liệu');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Có lỗi xảy ra khi tải thông tin tài liệu');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
