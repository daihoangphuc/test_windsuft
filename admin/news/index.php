<?php
$pageTitle = "Quản lý tin tức";
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/News.php';

$auth = new Auth();
$auth->requireAdmin();

// Khởi tạo đối tượng News
$news = new News();

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Lấy tổng số tin tức và danh sách tin tức
$total_news = $news->getTotalCount($search);
$total_pages = ceil($total_news / $limit);
$news_list = $news->getAll($search, $limit, $offset);

require_once __DIR__ . '/../../layouts/admin_header.php';

?>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<div class="p-4">
    <div class="bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Quản lý tin tức</h2>
            <button type="button" onclick="openAddModal()" 
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Thêm tin tức
            </button>
        </div>

            <!-- Thanh tìm kiếm -->
    <div class="mb-4">
        <form class="flex items-center" action="" method="GET" >   
            <label for="simple-search" class="sr-only">Search</label>
            <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="Tìm kiếm">
            </div>
            <button type="submit" class="p-2.5 ml-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
                <span class="sr-only">Search</span>
            </button>
        </form>
    </div>

        <!-- News Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($news_list as $item): ?>
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-lg font-bold mb-2">
                        <a href="/manage-htsv/news/detail.php?id=<?php echo $item['Id']; ?>" 
                           class="text-gray-600 hover:text-blue-800">
                            <?php echo htmlspecialchars($item['TieuDe']); ?>
                        </a>
                    </h3>
                    <p class="text-gray-500 mb-2"><?php echo htmlspecialchars($item['NguoiDang']); ?></p>
                    <p class="text-gray-500 mb-2"><?php echo date('d/m/Y H:i', strtotime($item['NgayTao'])); ?></p>
                    <div class="text-gray-500 mb-4">
                        <?php if ($item['FileDinhKem']): ?>
                            <a href="/manage-htsv/<?php echo htmlspecialchars($item['FileDinhKem']); ?>" 
                               class="font-medium text-blue-600 hover:underline" target="_blank">
                                Xem file đính kèm
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400">Không có file đính kèm</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button onclick="openEditModal(<?php echo $item['Id']; ?>)" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-edit"></i> Sửa
                        </button>
                        <button onclick="deleteNews(<?php echo $item['Id']; ?>)" 
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-4">
            <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-100">
                        Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 <?php echo $i == $page ? 'text-blue-600 bg-blue-50' : 'text-gray-500 bg-white'; ?> border border-gray-300 hover:bg-gray-100">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-100">
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal thêm tin tức -->
<div id="addModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Thêm tin tức mới
                </h3>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <form id="addForm" enctype="multipart/form-data">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="TieuDe" class="block mb-2 text-sm font-medium text-gray-900">Tiêu đề</label>
                            <input type="text" name="TieuDe" id="TieuDe" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="NoiDung" class="block mb-2 text-sm font-medium text-gray-900">Nội dung</label>
                            <input type="hidden" name="NoiDung" id="NoiDung">
                            <div id="editor-container" style="height: 300px;"></div>
                        </div>
                        <div>
                            <label for="FileDinhKem" class="block mb-2 text-sm font-medium text-gray-900">File đính kèm</label>
                            <input type="file" name="FileDinhKem" id="FileDinhKem" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" accept="image/*">
                        </div>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Thêm tin tức</button>
                    <button type="button" onclick="closeAddModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa tin tức -->
<div id="editNewsModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Chỉnh sửa tin tức
                </h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <!-- Modal body -->
            <form id="editNewsForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editId">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="editTieuDe" class="block mb-2 text-sm font-medium text-gray-900">Tiêu đề</label>
                            <input type="text" name="TieuDe" id="editTieuDe" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="editNoiDung" class="block mb-2 text-sm font-medium text-gray-900">Nội dung</label>
                            <input type="hidden" name="NoiDung" id="editNoiDung">
                            <div id="edit-editor-container" style="height: 300px;"></div>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">File đính kèm hiện tại</label>
                            <div id="currentFile" class="mb-2"></div>
                            <label for="editFileDinhKem" class="block mb-2 text-sm font-medium text-gray-900">Thay đổi file đính kèm (nếu cần)</label>
                            <input type="file" name="FileDinhKem" id="editFileDinhKem" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" accept="image/*">
                            <img id="previewImage" src="" alt="" class="mt-2 max-w-xs hidden">
                        </div>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Lưu thay đổi</button>
                    <button type="button" onclick="closeEditModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Khởi tạo Quill Editor cho form thêm mới
var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['clean'],
            ['link', 'image']
        ]
    }
});

// Khởi tạo Quill Editor cho form chỉnh sửa
var editQuill = new Quill('#edit-editor-container', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['clean'],
            ['link', 'image']
        ]
    }
});

// Cập nhật nội dung vào input hidden khi submit form thêm mới
document.getElementById('addForm').onsubmit = function() {
    var content = quill.root.innerHTML;
    document.getElementById('NoiDung').value = content;
};

// Cập nhật nội dung vào input hidden khi submit form chỉnh sửa
document.getElementById('editNewsForm').onsubmit = function() {
    var content = editQuill.root.innerHTML;
    document.getElementById('editNoiDung').value = content;
};

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addModal').classList.add('flex');
    quill.setText('');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addModal').classList.remove('flex');
    document.getElementById('addForm').reset();
    quill.setText('');
}

function openEditModal(id) {
    document.getElementById('editNewsModal').classList.remove('hidden');
    document.getElementById('editNewsModal').classList.add('flex');
    document.getElementById('editId').value = id;
    
    // Lấy thông tin tin tức cần sửa
    fetch('get_news.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.news) {
                document.getElementById('editTieuDe').value = data.news.TieuDe;
                editQuill.root.innerHTML = data.news.NoiDung;
                
                if (data.news.FileDinhKem) {
                    document.getElementById('currentFile').innerHTML = `
                        <a href="/manage-htsv/${data.news.FileDinhKem}" class="text-blue-600 hover:underline" target="_blank">
                            Xem file hiện tại
                        </a>`;
                } else {
                    document.getElementById('currentFile').innerHTML = 'Không có file đính kèm';
                }
            } else {
                alert(data.message || 'Không thể lấy thông tin tin tức');
                closeEditModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi lấy thông tin tin tức');
            closeEditModal();
        });
}

function closeEditModal() {
    document.getElementById('editNewsModal').classList.add('hidden');
    document.getElementById('editNewsModal').classList.remove('flex');
    document.getElementById('editNewsForm').reset();
    document.getElementById('currentFile').innerHTML = '';
    document.getElementById('previewImage').src = '';
    document.getElementById('previewImage').classList.add('hidden');
    editQuill.setText('');
}

// Xử lý form thêm tin tức
document.getElementById('addForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('add_news.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        alert('Có lỗi xảy ra');
    });
});

// Xử lý form chỉnh sửa tin tức
document.getElementById('editNewsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('edit_news.php?id=' + document.getElementById('editId').value, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeEditModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật tin tức');
    });
});

// Preview ảnh khi chọn file mới
document.getElementById('editFileDinhKem').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.match(/image.*/)) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            document.getElementById('previewImage').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('previewImage').src = '';
        document.getElementById('previewImage').classList.add('hidden');
    }
});

function deleteNews(id) {
    if (confirm('Bạn có chắc chắn muốn xóa tin tức này?')) {
        fetch('delete_news.php?id=' + id, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra');
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
