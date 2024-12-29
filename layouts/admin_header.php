<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Admin Dashboard'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy đường dẫn hiện tại
            const currentPath = window.location.pathname;
            
            // Tìm tất cả các menu item
            const menuItems = document.querySelectorAll('#admin-sidebar ul li a');
            
            // Duyệt qua từng menu item
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                
                // Kiểm tra chính xác cho Dashboard
                if (href === '/test_windsuft/admin') {
                    if (currentPath === href || currentPath === href + '/') {
                        item.classList.add('bg-gray-100');
                        item.classList.add('text-blue-600');
                    }
                }
                // Kiểm tra cho các menu item khác
                else if (currentPath.startsWith(href + '/') || currentPath === href) {
                    item.classList.add('bg-gray-100');
                    item.classList.add('text-blue-600');
                }
            });
        });
    </script>
</head>
<body class="bg-gray-100">
    <nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200">
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start">
                    <button data-drawer-target="admin-sidebar" data-drawer-toggle="admin-sidebar" aria-controls="admin-sidebar" type="button" class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>
                    <a href="/test_windsuft/admin" class="flex ml-2 md:mr-24">
                        <span class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap">Admin Panel</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center ml-3">
                        <div>
                            <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                                <span class="sr-only">Open user menu</span>
                                <img class="w-8 h-8 rounded-full" src="<?php echo str_replace('../', BASE_URL . '/', $_SESSION['avatar']); ?>" alt="user photo">
                            </button>
                        </div>
                        <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow" id="dropdown-user">
                            <div class="px-4 py-3" role="none">
                                <p class="text-sm text-gray-900" role="none">
                                    <?php echo $_SESSION['name'] ?? 'Admin'; ?>
                                </p>
                                <p class="text-sm font-medium text-gray-900 truncate" role="none">
                                    <?php echo $_SESSION['email'] ?? ''; ?>
                                </p>
                            </div>
                            <ul class="py-1" role="none">
                                <li>
                                    <a href="/test_windsuft/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Hồ sơ</a>
                                </li>
                                <li>
                                    <a href="/test_windsuft/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Đăng xuất</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <aside id="admin-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0" aria-label="Sidebar">
        <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
            <ul class="space-y-2 font-medium">
                <li>
                    <a href="/test_windsuft/admin" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                            <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/>
                            <path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/>
                        </svg>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="/test_windsuft/admin/users" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                            <path d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a2.97 2.97 0 0 1-.184 1H19a1 1 0 0 0 1-1v-1a5.006 5.006 0 0 0-5-5ZM6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"/>
                        </svg>
                        <span class="flex-1 ml-3 whitespace-nowrap">Quản lý thành viên</span>
                    </a>
                </li>
                <li>
                    <a href="/test_windsuft/admin/activities" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 5V.13a2.96 2.96 0 0 0-1.293.749L.879 3.707A2.96 2.96 0 0 0 .13 5H5Z"/>
                            <path d="M6.737 11.061a2.961 2.961 0 0 1 .81-1.515l6.117-6.116A4.839 4.839 0 0 1 16 2.141V2a1.97 1.97 0 0 0-1.933-2H7v5a2 2 0 0 1-2 2H0v11a1.969 1.969 0 0 0 1.933 2h12.134A1.97 1.97 0 0 0 16 18v-3.093l-1.546 1.546c-.413.413-.94.695-1.513.81l-3.4.679a2.947 2.947 0 0 1-1.85-.227 2.96 2.96 0 0 1-1.635-3.257l.681-3.397Z"/>
                            <path d="M8.961 16a.93.93 0 0 0 .189-.019l3.4-.679a.961.961 0 0 0 .49-.263l6.118-6.117a2.884 2.884 0 0 0-4.079-4.078l-6.117 6.117a.96.96 0 0 0-.263.491l-.679 3.4A.961.961 0 0 0 8.961 16Zm7.477-9.8a.958.958 0 0 1 .68-.281.961.961 0 0 1 .682 1.644l-.315.315-1.36-1.36.313-.318Zm-5.911 5.911 4.236-4.236 1.359 1.359-4.236 4.237-1.7.339.341-1.699Z"/>
                        </svg>
                        <span class="flex-1 ml-3 whitespace-nowrap">Quản lý hoạt động</span>
                    </a>
                </li>
                <li>
                    <a href="/test_windsuft/admin/tasks" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2ZM2 18V7h16v11H2Z"/>
                            <path d="M8 9h8a1 1 0 0 0 0-2H8a1 1 0 0 0 0 2Zm0 4h8a1 1 0 0 0 0-2H8a1 1 0 0 0 0 2Z"/>
                        </svg>
                        <span class="flex-1 ml-3 whitespace-nowrap">Quản lý nhiệm vụ</span>
                    </a>
                </li>
                <li>
                    <a href="/test_windsuft/admin/documents" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H7Zm0 2h10v14H7V5Zm-4 2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1V7H3Z"/>
                        </svg>
                        <span class="flex-1 ml-3 whitespace-nowrap">Quản lý tài liệu</span>
                    </a>
                </li>
                <li>
                    <a href="/test_windsuft/admin/news" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M19 4h-1a1 1 0 1 0 0 2v11a1 1 0 0 1-2 0V2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v15a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V5a1 1 0 0 0-1-1ZM3 4a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4Zm9 13H4a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-3H4a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-3H4a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="flex-1 ml-3 whitespace-nowrap">Quản lý tin tức</span>
                    </a>
                </li>
                <li>
                    <a href="/test_windsuft/admin/finance" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm0 17a7.5 7.5 0 1 1 7.5-7.5 7.509 7.509 0 0 1-7.5 7.5Zm2.5-10.5h-5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1Zm-1 5h-3v-3h3v3Z"/>
                        </svg>
                        <span class="flex-1 ml-3 whitespace-nowrap">Quản lý tài chính</span>
                    </a>
                </li>
                <li>
                    <a href="/test_windsuft/admin/reports" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 16">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 8h11m0 0L8 4m4 4-4 4m4-11h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3"/>
                        </path>
                    </svg>
                    <span class="flex-1 ml-3 whitespace-nowrap">Báo cáo & Thống kê</span>
                </a>
            </li>
            <li>
                <a href="/test_windsuft/admin/positions" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                    <i class="fas fa-user-tag w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
                    <span class="ml-3">Chức vụ</span>
                </a>
            </li>
            <li>
    <a href="/test_windsuft/admin/faculty" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
        <i class="fas fa-university w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
        <span class="ml-3">Khoa/Trường</span>
    </a>
</li>
<li>
    <a href="/test_windsuft/admin/class" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
        <i class="fas fa-graduation-cap w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
        <span class="ml-3">Lớp học</span>
    </a>
</li>
            <li>
                <a href="/test_windsuft/logout.php" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                    <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 8h11m0 0L8 4m4 4-4 4m4-11h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3"/>
                    </path>
                </svg>
                <span class="flex-1 ml-3 whitespace-nowrap">Đăng xuất</span>
            </a>
        </li>
    </ul>
</div>
</aside>

<div class="p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="flash-message" class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">
                <?php 
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                ?>
            </div>
            <script>
                setTimeout(function() {
                    document.getElementById('flash-message').style.display = 'none';
                }, 3000);
            </script>
        <?php endif; ?>
