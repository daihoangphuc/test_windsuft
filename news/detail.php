<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/classes/News.php';

// Lấy ID tin tức từ URL
$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: /manage-htsv/news/');
    exit();
}

$news = new News();
$newsData = $news->get($id);

if (!$newsData) {
    header('Location: /manage-htsv/news/');
    exit();
}

// Lấy tin tức liên quan
$relatedNews = $news->getRelated($id, 3);

// Load header
$pageTitle = $newsData['TieuDe'];
include '../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Nội dung chính -->
        <div class="lg:col-span-2">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="/manage-htsv/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                            <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                            </svg>
                            Trang chủ
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="/manage-htsv/news/" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">Tin tức</a>
                        </div>
                    </li>
                </ol>
            </nav>

            <article class="bg-white rounded-lg shadow-lg overflow-hidden">
                <?php if ($newsData['FileDinhKem']): ?>
                <img src="/manage-htsv/<?php echo htmlspecialchars($newsData['FileDinhKem']); ?>" 
                     alt="<?php echo htmlspecialchars($newsData['TieuDe']); ?>" 
                     class="w-full h-64 object-cover">
                <?php endif; ?>
                
                <div class="p-6">
                    <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($newsData['TieuDe']); ?></h1>
                    
                    <div class="flex items-center text-gray-600 text-sm mb-6">
                        <span class="mr-4">
                            <i class="fas fa-user mr-1"></i>
                            <?php echo htmlspecialchars($newsData['NguoiDang']); ?>
                        </span>
                        <span>
                            <i class="fas fa-clock mr-1"></i>
                            <?php echo date('d/m/Y H:i', strtotime($newsData['NgayTao'])); ?>
                        </span>
                    </div>

                    <div class="prose max-w-none">
                        <?php echo nl2br(htmlspecialchars($newsData['NoiDung'])); ?>
                    </div>

                    <?php if ($newsData['FileDinhKem']): ?>
                    <div class="mt-6">
                        <a href="/manage-htsv/<?php echo htmlspecialchars($newsData['FileDinhKem']); ?>" 
                           class="inline-flex items-center text-blue-600 hover:underline" 
                           target="_blank">
                            <i class="fas fa-paperclip mr-1"></i>
                            Xem tập tin đính kèm
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>

        <!-- Sidebar - Tin tức liên quan -->
        <div class="lg:col-span-1 mt-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">Tin tức liên quan</h2>
                <?php if ($relatedNews): ?>
                    <div class="space-y-4">
                        <?php foreach ($relatedNews as $item): ?>
                            <div class="group">
                                <?php if ($item['FileDinhKem']): ?>
                                    <img src="/manage-htsv/<?php echo htmlspecialchars($item['FileDinhKem']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['TieuDe']); ?>"
                                         class="w-full h-40 object-cover rounded-lg mb-2">
                                <?php endif; ?>
                                <h3 class="font-medium group-hover:text-blue-600">
                                    <a href="?id=<?php echo $item['Id']; ?>">
                                        <?php echo htmlspecialchars($item['TieuDe']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo date('d/m/Y', strtotime($item['NgayTao'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Không có tin tức liên quan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>