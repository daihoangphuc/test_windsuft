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
</head>
<body class="bg-[#f8f9fa]">
    <nav class="bg-white shadow-md">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>/" class="flex items-center space-x-3 rtl:space-x-reverse">
                <span class="self-center text-2xl font-semibold whitespace-nowrap text-[#4a90e2]">CLB HSTV</span>
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
            <div class="hidden w-full md:flex md:w-auto md:order-1" id="navbar-default">
                <ul class="flex flex-col md:flex-row md:space-x-8 mt-4 md:mt-0 md:text-sm md:font-medium">
                    <li>
                        <a href="<?php echo BASE_URL; ?>/" class="block py-2 px-3 text-gray-700 hover:text-[#4a90e2] rounded-md">Trang chủ</a>
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
                </ul>
            </div>

            <!-- Auth buttons -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="flex items-center md:order-2">
                    <div class="relative ml-3" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex text-sm border-2 border-[#4a90e2] rounded-full focus:outline-none focus:ring-2 focus:ring-[#4a90e2] transition duration-300">
                            <img class="h-8 w-8 rounded-full object-cover" 
                                 src="<?php echo str_replace('../', BASE_URL . '/', $_SESSION['avatar']); ?>" alt="user photo"" 
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
            <?php else: ?>
                <div class="flex items-center space-x-4 md:order-2">
                    <a href="<?php echo base_url('/login.php'); ?>" 
                       class="text-gray-800 hover:text-[#4a90e2] px-3 py-2 rounded-md text-sm font-medium">
                        Đăng nhập
                    </a>
                    <a href="<?php echo base_url('/register.php'); ?>" 
                       class="text-gray-800 hover:text-[#4a90e2] px-3 py-2 rounded-md text-sm font-medium">
                        Đăng ký
                    </a>
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
