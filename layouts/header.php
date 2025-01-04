<?php
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../config/auth.php';
$auth = new Auth();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - CLB HSTV' : 'CLB HSTV'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <style>
        .flash-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            padding: 16px;
            display: none;
            animation: slideIn 0.3s ease-out;
        }
        .flash-notification.success {
            border-left: 4px solid #10B981;
        }
        .flash-notification.error {
            border-left: 4px solid #EF4444;
        }
        .flash-notification.info {
            border-left: 4px solid #3B82F6;
        }
        .flash-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #3B82F6;
            width: 100%;
            transform-origin: left;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body class="bg-[#f8f9fa]">
    <!-- Flash Notification Container -->
    <div id="flashNotification" class="flash-notification">
        <div class="flex justify-between items-start">
            <div id="flashMessage" class="flex-1 pr-4"></div>
            <button onclick="closeFlash()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flash-progress" id="flashProgress"></div>
    </div>

    <script>
        let flashTimeout;
        let progressAnimation;

        function showFlash(message, type = 'info') {
            const notification = document.getElementById('flashNotification');
            const messageEl = document.getElementById('flashMessage');
            const progress = document.getElementById('flashProgress');
            
            // Reset any existing timeouts and animations
            if (flashTimeout) clearTimeout(flashTimeout);
            if (progressAnimation) progressAnimation.cancel();

            // Set message and type
            messageEl.textContent = message;
            notification.className = 'flash-notification ' + type;
            notification.style.display = 'block';

            // Animate progress bar
            progressAnimation = progress.animate(
                [
                    { transform: 'scaleX(1)' },
                    { transform: 'scaleX(0)' }
                ],
                {
                    duration: 3000,
                    easing: 'linear',
                    fill: 'forwards'
                }
            );

            // Auto close after 3 seconds
            flashTimeout = setTimeout(() => closeFlash(), 3000);
        }

        function closeFlash() {
            const notification = document.getElementById('flashNotification');
            notification.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => {
                notification.style.display = 'none';
                notification.style.animation = '';
            }, 300);
        }

        // Show flash message if exists
        <?php if (isset($_SESSION['flash_message'])): ?>
            showFlash(<?php echo json_encode($_SESSION['flash_message']); ?>, <?php echo isset($_SESSION['flash_type']) ? json_encode($_SESSION['flash_type']) : "'info'"; ?>);
            <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        <?php endif; ?>
    </script>

    <nav class="bg-white shadow-sm">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>/" class="flex items-center space-x-3 rtl:space-x-reverse">
                <img src="<?php echo BASE_URL; ?>/assets/logo/logo-clb.png" class="h-8 mr-3" alt="CLB HSTV Logo">
            </a>

            <!-- Mobile menu button -->
            <button data-collapse-toggle="navbar-default" type="button" 
                    class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200" 
                    aria-controls="navbar-default" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                </svg>
            </button>

            <!-- Navigation items -->
            <div class="hidden w-full md:block md:w-auto" id="navbar-default">
                <div class="flex flex-col md:flex-row items-center">
                    <ul class="flex flex-col md:flex-row md:space-x-8 mt-4 md:mt-0 md:text-sm md:font-medium w-full">
                        <li>
                            <a href="<?php echo BASE_URL; ?>/" class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md">Trang chủ</a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/guide.php" class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md">Hướng dẫn sử dụng</a>
                        </li>
                        <li>
                            <a href="https://zalo.me/g/dqqnrd829" class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md">Nhóm hỗ trợ</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/activities" class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md">Hoạt động</a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/activities/my_activities.php" class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md">Hoạt động của tôi</a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/tasks/my_tasks.php" class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md">Nhiệm vụ</a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Auth buttons in mobile menu -->
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <li class="md:hidden">
                                <a href="<?php echo base_url('/login.php'); ?>" 
                                   class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md whitespace-nowrap">
                                    Đăng nhập
                                </a>
                            </li>
                            <li class="md:hidden">
                                <a href="<?php echo base_url('/register.php'); ?>" 
                                   class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md whitespace-nowrap">
                                    Đăng ký
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Auth buttons - Only visible on desktop -->
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="hidden md:flex items-center space-x-4 ml-8">
                        <a href="<?php echo base_url('/login.php'); ?>" 
   class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none whitespace-nowrap">
    Đăng nhập
</a>
<a href="<?php echo base_url('/register.php'); ?>" 
   class="text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center whitespace-nowrap">
    Đăng ký
</a>

                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Auth section for desktop (when logged in) -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="hidden md:flex items-center md:order-2">
                    <div class="relative ml-3" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex text-sm border-2 border-[#4a90e2] rounded-full focus:outline-none focus:ring-2 focus:ring-[#4a90e2] transition duration-300">
                            <img class="h-8 w-8 rounded-full object-cover" 
                                 src="<?php echo str_replace('../', BASE_URL . '/', $_SESSION['avatar']); ?>" 
                                 alt="Avatar">
                        </button>
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5"
                             style="display: none;">
                            <a href="<?php echo base_url('/profile.php'); ?>" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Hồ sơ
                            </a>
                            <a href="<?php echo base_url('/logout.php'); ?>" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['flash_message']; ?></span>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('[data-collapse-toggle="navbar-default"]');
            const menu = document.getElementById('navbar-default');
            
            button.addEventListener('click', function() {
                menu.classList.toggle('hidden');
            });
        });
    </script>
</body>
</html>
