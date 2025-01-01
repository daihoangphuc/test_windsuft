<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/path.php';
require_once __DIR__ . '/config/auth.php';
$auth = new Auth();

if ($auth->isLoggedIn()) {
    if ($_SESSION['role_id'] == 1) {
        redirect('/admin');
    } else {
        redirect('/');
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        $_SESSION['flash_message'] = 'Đăng nhập thành công!';
        if ($_SESSION['role_id'] == 1) {
            redirect('/admin');
        } else {
            redirect('/');
        }
        exit();
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
    }
}

$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                Chào mừng trở lại!
            </h2>
            <p class="text-gray-600 text-sm">
                Đăng nhập để tiếp tục
            </p>
        </div>

        <?php if ($error): ?>
            <div class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg text-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="<?php echo base_url('/login.php'); ?>" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên đăng nhập
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input id="username" name="username" type="text" required 
                               class="pl-10 appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg 
                                      placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 
                                      focus:border-transparent transition duration-150 ease-in-out sm:text-sm" 
                               placeholder="Nhập tên đăng nhập">
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Mật khẩu
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" required 
                               class="pl-10 appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg 
                                      placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 
                                      focus:border-transparent transition duration-150 ease-in-out sm:text-sm"
                               placeholder="Nhập mật khẩu">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded 
                                  transition duration-150 ease-in-out">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                        Ghi nhớ đăng nhập
                    </label>
                </div>

                <div class="text-sm">
                    <a href="<?php echo base_url('/forgot-password.php'); ?>" 
                       class="font-medium text-blue-600 hover:text-blue-500 transition duration-150 ease-in-out">
                        Quên mật khẩu?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent 
                               text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 
                               transition duration-150 ease-in-out">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400 transition ease-in-out duration-150" 
                             fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" 
                                  d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" 
                                  clip-rule="evenodd"/>
                        </svg>
                    </span>
                    Đăng nhập
                </button>
            </div>
            <a href="<?php echo base_url('/google-login.php'); ?>" 
                       class="group relative w-full flex justify-center py-3 px-4 border border-transparent 
                               text-sm font-medium rounded-lg text-black bg-white-200 hover:bg-blue-700 hover:text-white
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-100 
                               transition duration-150 ease-in-out">
                        <img src="https://www.google.com/images/branding/googleg/1x/googleg_standard_color_128dp.png" alt="Google Login" class="w-5 h-5 mr-2">
                        Đăng nhập với Google
                    </a>
        </form>

        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">
                        Chưa có tài khoản?
                    </span>
                </div>
            </div>

            <div class="mt-6">
                <a href="<?php echo base_url('/register.php'); ?>" 
                   class="w-full flex justify-center py-3 px-4 border border-blue-300 rounded-lg 
                          text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 
                          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 
                          transition duration-150 ease-in-out">
                    Đăng ký tài khoản mới
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
