<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance()->getConnection();

// Fetch recent activities
$stmt = $db->prepare("SELECT * FROM hoatdong WHERE NgayKetThuc > NOW() ORDER BY NgayBatDau ASC LIMIT 3");
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch recent news
$stmt = $db->prepare("SELECT t.*, n.HoTen as TacGia FROM tintuc t JOIN nguoidung n ON t.NguoiTaoId = n.Id ORDER BY t.NgayTao DESC LIMIT 3");
$stmt->execute();
$news = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch club leaders
$stmt = $db->prepare("SELECT n.*, c.TenChucVu 
                      FROM nguoidung n 
                      JOIN chucvu c ON n.ChucVuId = c.Id 
                      WHERE c.TenChucVu IN ('Chủ nhiệm', 'Phó chủ nhiệm', 'Thư ký')
                      ORDER BY c.Id ASC");
$stmt->execute();
$leaders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Trang chủ';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="bg-white py-24 sm:py-32">
  <div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="mx-auto max-w-2xl text-center">
      <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Câu lạc bộ Học sinh - Sinh viên</h2>
      <p class="mt-2 text-lg leading-8 text-gray-600">Nơi kết nối và phát triển tài năng</p>
    </div>

    <?php if (!empty($activities)): ?>
    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
      <h3 class="text-2xl font-bold mb-6">Hoạt động sắp diễn ra</h3>
      <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
        <?php foreach ($activities as $activity): ?>
        <div class="flex flex-col">
          <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
            <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
          </dt>
          <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
            <p class="flex-auto"><?php echo htmlspecialchars($activity['MoTa']); ?></p>
            <p class="mt-6">
              <a href="<?php echo BASE_URL; ?>/activities/view_activity.php?id=<?php echo $activity['Id']; ?>" class="text-sm font-semibold leading-6 text-indigo-600">Xem chi tiết <span aria-hidden="true">→</span></a>
            </p>
          </dd>
        </div>
        <?php endforeach; ?>
      </dl>
    </div>
    <?php endif; ?>

    <?php if (!empty($news)): ?>
    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
        <h3 class="text-2xl font-bold mb-6">Tin tức mới nhất</h3>
        <div class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
            <?php foreach ($news as $item): ?>
            <div class="relative max-w-sm bg-white border border-gray-200 rounded-lg shadow">
                <a href="/test_windsuft/news/detail.php?id=<?php echo $item['Id']; ?>">
                    <img class="rounded-t-lg w-full h-48 object-cover" src="<?php echo htmlspecialchars($item['FileDinhKem']); ?>" alt="<?php echo htmlspecialchars($item['TieuDe']); ?>">
                </a>
                <div class="p-5">
                    <h5 class="text-lg font-bold tracking-tight text-gray-900 line-clamp-2">
                        <a href="/test_windsuft/news/detail.php?id=<?php echo $item['Id']; ?>">
                            <?php echo htmlspecialchars($item['TieuDe']); ?>
                        </a>
                    </h5>
                    <div class="mt-3 flex items-center text-sm text-gray-500 space-x-4">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($item['TacGia']); ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-calendar-alt"></i>
                            <time datetime="<?php echo date('Y-m-d', strtotime($item['NgayTao'])); ?>">
                                <?php echo date('d/m/Y', strtotime($item['NgayTao'])); ?>
                            </time>
                        </div>
                    </div>
                </div>
                <div class="absolute bottom-5 right-5">
                    <a href="/test_windsuft/news/detail.php?id=<?php echo $item['Id']; ?>" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300">
                        Đọc thêm
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>


    <?php if (!empty($leaders)): ?>
    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
      <h3 class="text-2xl font-bold mb-6">Ban chủ nhiệm</h3>
      <ul role="list" class="grid gap-x-8 gap-y-12 sm:grid-cols-2 sm:gap-y-16 xl:col-span-2">
        <?php foreach ($leaders as $leader): ?>
        <li>
          <div class="flex items-center gap-x-6">
            <div>
              <h3 class="text-base font-semibold leading-7 tracking-tight text-gray-900"><?php echo htmlspecialchars($leader['HoTen']); ?></h3>
              <p class="text-sm font-semibold leading-6 text-indigo-600"><?php echo htmlspecialchars($leader['TenChucVu']); ?></p>
            </div>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    
  </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
