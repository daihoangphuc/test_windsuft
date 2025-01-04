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
                      WHERE c.Id IN (1, 2, 3, 11, 12)
                      ORDER BY c.Id ASC");
$stmt->execute();
$leaders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Trang chủ';
require_once __DIR__ . '/layouts/header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
    $(document).ready(function() {
        $("#draggableBanner").draggable({
            containment: "parent",
            cursor: "move"
        });
    });
</script>
<!-- Marquee cho hoạt động và tin tức -->
<div class="bg-[#4a90e2] text-white py-2">
    <div class="flex items-center">
        <div class="flex-shrink-0 px-4 py-1 bg-[#2563eb] rounded-r-full">
            <span class="font-semibold">Thông báo</span>
        </div>
        <div class="flex-1 overflow-hidden">
            <div class="marquee-container">
                <div class="marquee-content">
                    <?php foreach ($activities as $activity): ?>
                        <span class="mx-4">
                            📅 <?php echo htmlspecialchars($activity['TenHoatDong']); ?> 
                            (<?php echo date('d/m/Y', strtotime($activity['NgayBatDau'])); ?>)
                        </span>
                    <?php endforeach; ?>
                    <?php foreach ($news as $item): ?>
                        <span class="mx-4">
                            📰 <?php echo htmlspecialchars($item['TieuDe']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.marquee-container {
    width: 100%;
    overflow: hidden;
    position: relative;
}

.marquee-content {
    display: inline-block;
    white-space: nowrap;
    animation: marquee 30s linear infinite;
}

.marquee-content:hover {
    animation-play-state: paused;
}

@keyframes marquee {
    0% {
        transform: translateX(100%);
    }
    100% {
        transform: translateX(-100%);
    }
}

.marquee-content span {
    display: inline-block;
    padding-right: 50px;
}
</style>
<div class="bg-white">
    <div class="relative bg-white" style="min-height: 250px;">
        <div class="container mx-auto h-full px-4 py-8">
            <div class="flex flex-col md:flex-row h-full items-center gap-8">
                <!-- Video Container - Left Side -->
                <div class="w-full md:w-1/2">
                    <div class="relative aspect-video bg-black rounded-lg overflow-hidden">
                        <iframe 
                            src="https://drive.google.com/file/d/1h79MdeRSwyTFtBs216L3pKRJCsSCU3kn/preview" 
                            class="absolute top-0 left-0 w-full h-full"
                            allow="autoplay"
                            frameborder="0"
                        ></iframe>
                    </div>
                </div>
                <!-- Text Container - Right Side -->
                <div class="w-full md:w-1/2">
                    <div class="text-center px-4 sm:px-8 py-6 rounded-lg">
                        <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-[#4a90e2] mb-4 drop-shadow-lg">Câu lạc bộ</h2>
                        <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-[#ff66c2] mb-4 drop-shadow-lg">Hành trình sinh viên</h2>
                        <p class="text-lg sm:text-xl text-[#4a90e2] drop-shadow-lg">Nơi kết nối và phát triển tài năng</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($activities)): ?>
    <div class="mx-auto mt-16 max-w-7xl px-6 lg:px-8">
      <div class="mx-auto max-w-2xl lg:mx-0">
        <h2 class="text-3xl font-bold tracking-tight text-[#4a90e2] sm:text-4xl text">Hoạt động sắp diễn ra</h2>
        <p class="mt-2 text-lg leading-8 text-gray-600">Tham gia các hoạt động thú vị cùng CLB HSTV</p>
      </div>
      <div class="mx-auto mt-10 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 border-t border-[#e3f2fd] pt-10 sm:mt-16 sm:pt-16 lg:mx-0 lg:max-w-none lg:grid-cols-3">
        <?php foreach ($activities as $activity): ?>
        <div class="flex max-w-xl flex-col items-start justify-between bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
          <div class="flex items-center gap-x-4 text-xs">
            <time datetime="<?php echo $activity['NgayBatDau']; ?>" class="text-[#4a90e2]"><?php echo date('d/m/Y', strtotime($activity['NgayBatDau'])); ?></time>
            <?php
            $stmt = $db->prepare("SELECT COUNT(NguoiDungId) as count FROM danhsachdangky WHERE HoatDongId = ?");
            $stmt->bind_param("i", $activity['Id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $count = $result['count'];
            ?>
            <span class="relative z-10 rounded-full bg-[#fce7f3] px-3 py-0.5 font-medium text-[#4a90e2]"><?php echo "Đã đăng ký: " . htmlspecialchars($count); ?></span>
          </div>
          <div class="group relative">
            <h3 class="mt-3 text-lg font-semibold leading-6 text-gray-900 group-hover:text-[#4a90e2] transition duration-300">
              <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
            </h3>
            <p class="mt-5 line-clamp-3 text-sm leading-6 text-gray-600"><?php echo htmlspecialchars($activity['MoTa']); ?></p>
          </div>
          <div class="mt-6">
            <a href="<?php echo BASE_URL; ?>/activities/view_activity.php?id=<?php echo $activity['Id']; ?>" class="text-sm font-semibold leading-6 text-[#4a90e2] hover:text-[#2d5a8e] transition duration-300">
              Xem chi tiết <span aria-hidden="true">→</span>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($news)): ?>
    <div class="mx-auto max-w-7xl px-6 lg:px-8 mt-16">
      <div class="mx-auto max-w-2xl lg:mx-0">
        <h2 class="text-3xl font-bold tracking-tight text-[#4a90e2] sm:text-4xl">Tin tức</h2>
        <p class="mt-2 text-lg leading-8 text-gray-600">Cập nhật những tin tức mới nhất từ CLB HSTV</p>
      </div>
      
      <div class="mx-auto grid max-w-7xl grid-cols-1 gap-x-8 gap-y-16 border-t border-[#e3f2fd] pt-10 mt-8 sm:mt-16 sm:pt-16 lg:grid-cols-3">
        <?php foreach ($news as $item): ?>
            <div class="max-w-sm bg-white border border-[#e3f2fd] rounded-lg shadow-md hover:shadow-lg transition duration-300">
                <a href="/test_windsuft/news/detail.php?id=<?php echo $item['Id']; ?>">
                    <img class="rounded-t-lg w-full h-48 object-cover" src="<?php echo str_replace('../', BASE_URL . '/', $item['FileDinhKem']); ?>" alt="<?php echo htmlspecialchars($item['TieuDe']); ?>" />
                </a>
                <div class="p-5">
                    <div class="mb-4">
                        <span class="bg-[#fce7f3] text-[#4a90e2] text-xs font-medium px-2.5 py-0.5 rounded-full">Tin tức</span>
                        <span class="text-gray-500 text-sm ml-2"><?php echo date('d/m/Y', strtotime($item['NgayTao'])); ?></span>
                    </div>
                    <a href="#">
                        <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 hover:text-[#4a90e2] transition duration-300"><?php echo htmlspecialchars($item['TieuDe']); ?></h5>
                    </a>
                    <p class="mb-3 text-sm text-gray-600"><?php echo htmlspecialchars(substr($item['NoiDung'], 0, 150)) . '...'; ?></p>
                    <a href="/test_windsuft/news/detail.php?id=<?php echo $item['Id']; ?>" 
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-[#4a90e2] rounded-lg hover:bg-[#2d5a8e] focus:ring-4 focus:ring-[#e3f2fd] transition duration-300">
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
<div class="mx-auto mt-16 max-w-5xl">
  <h3 class="text-2xl font-bold mb-6 text-center text-[#4a90e2]">Ban chủ nhiệm</h3>
  <!-- Wrapper to prevent overflow -->
  <div class="overflow-hidden">
    <div class="swiper-container">
      <div class="swiper-wrapper">
        <?php foreach ($leaders as $leader): ?>
        <div class="swiper-slide">
          <div class="bg-white rounded-lg overflow-hidden">
            <div class="flex justify-center mt-4">
              <div class="w-48 h-48 rounded-full border-4 border-blue-200 overflow-hidden">
                <img src="<?php echo str_replace('../', BASE_URL . '/', $leader['anhdaidien']); ?>" 
                     alt="<?php echo htmlspecialchars($leader['HoTen']); ?>" 
                     class="w-full h-full object-cover">
              </div>
            </div>
            <div class="p-4 text-center">
              <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($leader['HoTen']); ?></h3>
              <p class="text-sm text-indigo-600"><?php echo htmlspecialchars($leader['TenChucVu']); ?></p>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>
<?php endif; ?>



    
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.swiper-container', {
      slidesPerView: 1,
      spaceBetween: 20,
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      breakpoints: {
        640: { slidesPerView: 1, spaceBetween: 20 },
        768: { slidesPerView: 2, spaceBetween: 30 },
        1024: { slidesPerView: 3, spaceBetween: 40 },
      },
    });
    new Swiper('.bannerSwiper', {
      slidesPerView: 1,
      spaceBetween: 30,
      loop: true,
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      effect: 'none',
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      }
    });
  });
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
