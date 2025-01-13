<?php
$pageTitle = "Xem tài liệu";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Thêm autoload của Composer

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

// Bật hiển thị lỗi chi tiết
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập thư mục temp cho PHPWord
$tempDir = __DIR__ . '/../temp';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}
Settings::setTempDir($tempDir);

$auth = new Auth();
$auth->requireLogin();

// Khởi tạo kết nối
$db = Database::getInstance();
$conn = $db->getConnection();

// Lấy ID tài liệu từ URL
$documentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$documentId) {
    header('Location: index.php');
    exit;
}

// Kiểm tra quyền xem tài liệu
$query = "
    SELECT t.*, p.Quyen 
    FROM tailieu t
    LEFT JOIN phanquyentailieu p ON t.Id = p.TaiLieuId 
    WHERE t.Id = ? AND p.VaiTroId = ? AND p.Quyen >= 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $documentId, $_SESSION['role_id']);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();

if (!$document) {
    header('Location: index.php');
    exit;
}

// Chuyển đường dẫn tương đối thành tuyệt đối
$absolutePath = realpath(__DIR__ . '/../' . $document['DuongDan']);
if (!$absolutePath) {
    $absolutePath = __DIR__ . '/../' . ltrim($document['DuongDan'], '/.');
}

// Debug thông tin
echo '<!-- Original Path: ' . htmlspecialchars($document['DuongDan']) . ' -->';
echo '<!-- Absolute Path: ' . htmlspecialchars($absolutePath) . ' -->';

// Lấy extension của file
$fileExtension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
echo '<!-- File Extension: ' . htmlspecialchars($fileExtension) . ' -->';

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($document['TenTaiLieu']); ?></h1>
                <a href="index.php" class="text-blue-600 hover:text-blue-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
            </div>
            <?php if (!empty($document['MoTa'])): ?>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($document['MoTa']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Document Viewer -->
        <div class="p-6">
            <?php
            // Xử lý hiển thị theo loại file
            switch ($fileExtension) {
                case 'pdf':
                    echo '<iframe src="' . htmlspecialchars($absolutePath) . '" class="w-full h-screen" frameborder="0"></iframe>';
                    break;
                    
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    echo '<img src="' . htmlspecialchars($absolutePath) . '" alt="' . htmlspecialchars($document['TenTaiLieu']) . '" class="max-w-full h-auto mx-auto">';
                    break;
                    
                case 'txt':
                    // Đọc và hiển thị nội dung file text
                    $content = file_get_contents($absolutePath);
                    echo '<pre class="whitespace-pre-wrap p-4 bg-gray-50 rounded">' . htmlspecialchars($content) . '</pre>';
                    break;

                case 'doc':
                case 'docx':
                    try {
                        // Debug thông tin file
                        echo "<!-- File exists: " . (file_exists($absolutePath) ? 'Yes' : 'No') . " -->";
                        
                        if (!file_exists($absolutePath)) {
                            throw new Exception("File không tồn tại: " . $absolutePath);
                        }

                        // Đọc file Word
                        $phpWord = IOFactory::load($absolutePath);
                        
                        // Lưu sang HTML tạm thời
                        $htmlFile = $tempDir . '/' . uniqid() . '.html';
                        $htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
                        $htmlWriter->save($htmlFile);
                        
                        // Đọc nội dung HTML và xóa file tạm
                        $wordContent = file_get_contents($htmlFile);
                        unlink($htmlFile);
                        
                        echo '<div class="bg-white shadow-lg rounded-lg overflow-hidden">';
                        echo '<div class="p-4 border-b border-gray-200 flex justify-between items-center">';
                        echo '<h2 class="text-xl font-semibold">' . htmlspecialchars($document['TenTaiLieu']) . '</h2>';
                        if ($document['Quyen'] >= 2) {
                            echo '<a href="' . htmlspecialchars($absolutePath) . '" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" download>Tải xuống</a>';
                        }
                        echo '</div>';
                        
                        // Hiển thị nội dung với CSS
                        echo '<div class="document-content p-6 prose max-w-none">';
                        echo $wordContent;
                        echo '</div>';
                        echo '</div>';
                        
                        // Thêm CSS để định dạng nội dung
                        echo '<style>
                            .document-content {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                                padding: 20px;
                            }
                            .document-content table {
                                border-collapse: collapse;
                                width: 100%;
                                margin: 1em 0;
                            }
                            .document-content table td,
                            .document-content table th {
                                border: 1px solid #ddd;
                                padding: 8px;
                            }
                            .document-content img {
                                max-width: 100%;
                                height: auto;
                            }
                        </style>';
                        
                    } catch (Exception $e) {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">';
                        echo '<strong class="font-bold">Lỗi!</strong>';
                        echo '<span class="block sm:inline"> ' . htmlspecialchars($e->getMessage()) . '</span>';
                        echo '<p class="text-sm mt-2">Chi tiết lỗi đã được ghi vào log.</p>';
                        echo '</div>';
                        error_log("Lỗi đọc file Word: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    }
                    break;
                    
                default:
                    echo '<div class="text-center py-10">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="mt-2 text-gray-600">Định dạng file không được hỗ trợ xem trực tiếp</p>
                          </div>';
            }
            ?>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm text-gray-600">
                <div class="flex space-x-4">
                    <span>Loại: <?php echo htmlspecialchars($document['LoaiTaiLieu']); ?></span>
                    <span>Ngày tạo: <?php echo date('d/m/Y H:i', strtotime($document['NgayTao'])); ?></span>
                </div>
                <?php if ($document['Quyen'] >= 2): ?>
                    <a href="<?php echo htmlspecialchars($absolutePath); ?>" 
                       target="_blank"
                       class="inline-flex items-center justify-center px-4 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 hover:bg-blue-600 hover:text-white">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Tải về
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
