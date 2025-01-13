<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/classes/News.php';

// Lấy tham số tìm kiếm và phân trang
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$news = new News();
$items = $news->getAll($search, $limit, $offset);

// Lấy tổng số tin tức để phân trang
$total = $news->getTotalCount($search);
$totalPages = ceil($total / $limit);

// Load header
include '../layouts/header.php';
?>
<!-- Main content -->
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Tin tức</h1>

    <!-- Search form -->
    <form method="GET" class="mb-6">
        <div class="flex gap-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm kiếm tin tức..." 
                   class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            <button type="submit" 
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Tìm kiếm
            </button>
        </div>
    </form>

    <!-- News list -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($items as $item): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if ($item['FileDinhKem']): ?>
                    <img src="/manage-htsv/<?php echo htmlspecialchars($item['FileDinhKem']); ?>" 
                         alt="<?php echo htmlspecialchars($item['TieuDe']); ?>"
                         class="w-full h-48 object-cover">
                <?php endif; ?>
                <div class="p-4">
                    <h2 class="text-xl font-bold mb-2">
                        <a href="detail.php?id=<?php echo $item['Id']; ?>" 
                           class="text-blue-600 hover:text-blue-800">
                            <?php echo htmlspecialchars($item['TieuDe']); ?>
                        </a>
                    </h2>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['NguoiDang']); ?>
                    </p>
                    <p class="text-gray-600 mb-4">
                        <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($item['NgayTao'])); ?>
                    </p>
                    <div class="text-gray-700 mb-4 line-clamp-3">
                        <?php 
                            $plainText = strip_tags($item['NoiDung']);
                            echo htmlspecialchars(substr($plainText, 0, 200)) . '...'; 
                        ?>
                    </div>
                    <a href="detail.php?id=<?php echo $item['Id']; ?>" 
                       class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Xem chi tiết
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-8">
            <nav class="inline-flex rounded-md shadow">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                        Trước
                    </a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 bg-white hover:bg-gray-50'; ?> border border-gray-300">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                        Sau
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>
