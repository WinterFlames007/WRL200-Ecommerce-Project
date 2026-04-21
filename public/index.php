<?php

session_start();


$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../app/Core/Database.php';

$maintenanceMode = false;

try {
    $db = \App\Core\Database::connect();
    $maintenanceResult = mysqli_query(
        $db,
        "SELECT setting_value
         FROM platform_settings
         WHERE setting_key = 'maintenance_mode'
         LIMIT 1"
    );

    if ($maintenanceResult) {
        $maintenanceRow = mysqli_fetch_assoc($maintenanceResult);
        $maintenanceMode = (($maintenanceRow['setting_value'] ?? '0') === '1');
    }
} catch (\Throwable $e) {
    $maintenanceMode = false;
}

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$isAdminLoggedIn = isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
$isAdminArea = str_starts_with($currentPath, '/admin');
$isLoginPage = $currentPath === '/login';

if ($maintenanceMode && !$isAdminLoggedIn && !$isAdminArea && !$isLoginPage) {
    http_response_code(503);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance Mode</title>
        <link rel="stylesheet" href="/assets/css/style.css">
    </head>
    <body>
        <main class="site-main">
            <div class="container">
                <div class="card" style="max-width:700px;margin:40px auto;text-align:center;">
                    <h1>We are temporarily unavailable</h1>
                    <p>The store is currently in maintenance mode. Please check back later.</p>
                </div>
            </div>
        </main>
    </body>
    </html>';
    exit;
}



$sessionTimeoutMap = [
    '15 Minutes' => 15 * 60,
    '30 Minutes' => 30 * 60,
    '1 Hour' => 60 * 60
];

$sessionTimeoutLabel = '30 Minutes';

try {
    $db = \App\Core\Database::connect();
    $timeoutResult = mysqli_query(
        $db,
        "SELECT setting_value
         FROM platform_settings
         WHERE setting_key = 'session_timeout'
         LIMIT 1"
    );

    if ($timeoutResult) {
        $timeoutRow = mysqli_fetch_assoc($timeoutResult);
        if (!empty($timeoutRow['setting_value']) && isset($sessionTimeoutMap[$timeoutRow['setting_value']])) {
            $sessionTimeoutLabel = $timeoutRow['setting_value'];
        }
    }
} catch (\Throwable $e) {
    $sessionTimeoutLabel = '30 Minutes';
}

$sessionTimeoutSeconds = $sessionTimeoutMap[$sessionTimeoutLabel];

if (isset($_SESSION['user'])) {
    $lastActivity = (int) ($_SESSION['last_activity_at'] ?? time());

    if ((time() - $lastActivity) > $sessionTimeoutSeconds) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your session expired. Please log in again.';
        header('Location: /login');
        exit;
    }

    $_SESSION['last_activity_at'] = time();
}



// Core
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Database.php';



use App\Core\Database;

function getPlatformSetting(string $key, ?string $default = null): ?string
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];

        try {
            $db = Database::connect();
            $result = mysqli_query($db, "SELECT setting_key, setting_value FROM platform_settings");

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (\Throwable $e) {
            $settings = [];
        }
    }

    return $settings[$key] ?? $default;
}









// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Controllers
require_once __DIR__ . '/../app/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/ProductController.php';
require_once __DIR__ . '/../app/Controllers/CartController.php';
require_once __DIR__ . '/../app/Controllers/CheckoutController.php';
require_once __DIR__ . '/../app/Controllers/OrderController.php';
require_once __DIR__ . '/../app/Controllers/PaymentController.php';
require_once __DIR__ . '/../app/Controllers/WebhookController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';
require_once __DIR__ . '/../app/Controllers/AccountController.php';


use App\Core\Router;



$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

/*
|--------------------------------------------------------------------------
| Session timeout
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['user'])) {
    $sessionTimeoutSetting = getPlatformSetting('session_timeout', '30 Minutes');
    $timeoutSeconds = 1800;

    if ($sessionTimeoutSetting === '15 Minutes') {
        $timeoutSeconds = 900;
    } elseif ($sessionTimeoutSetting === '1 Hour') {
        $timeoutSeconds = 3600;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your session expired. Please log in again.';
        header('Location: /login');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

/*
|--------------------------------------------------------------------------
| Maintenance mode
|--------------------------------------------------------------------------
*/
$maintenanceMode = getPlatformSetting('maintenance_mode', '0') === '1';

$maintenanceAllowedPaths = [
    '/login',
    '/logout',
    '/admin/dashboard',
    '/admin/users',
    '/admin/settings',
    '/admin/reports',
    '/admin/logs',
    '/webhook/stripe'
];

if ($maintenanceMode) {
    $isAdmin = isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
    $isAllowedPath = in_array($requestPath, $maintenanceAllowedPaths, true);

    if (!$isAdmin && !$isAllowedPath) {
        http_response_code(503);
        require __DIR__ . '/../app/Views/errors/maintenance.php';
        exit;
    }
}





$router = new Router();

// Public routes
$router->get('/', 'HomeController@index');
$router->get('/shop', 'ProductController@index');
$router->get('/product', 'ProductController@show');

// Auth
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@registerForm');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/account', 'AccountController@index');
$router->get('/account/edit', 'AccountController@editForm');
$router->post('/account/edit', 'AccountController@updateProfile');
$router->get('/account/order', 'AccountController@orderDetails');





// Dashboard
$router->get('/customer/dashboard', 'HomeController@customerDashboard');
$router->get('/seller/dashboard', 'DashboardController@dashboard');
$router->get('/admin/dashboard', 'AdminController@dashboard');

// Seller products
$router->get('/seller/products', 'DashboardController@products');
$router->get('/seller/products/create', 'DashboardController@createProductForm');
$router->post('/seller/products/create', 'DashboardController@storeProduct');
$router->get('/seller/products/edit', 'DashboardController@editProductForm');
$router->post('/seller/products/edit', 'DashboardController@updateProduct');
$router->get('/seller/products/delete', 'DashboardController@deleteProduct');



// Seller variants
$router->get('/seller/variants', 'DashboardController@variants');
$router->get('/seller/variants/create', 'DashboardController@createVariantForm');
$router->post('/seller/variants/create', 'DashboardController@storeVariant');
$router->get('/seller/variants/edit', 'DashboardController@editVariantForm');
$router->post('/seller/variants/edit', 'DashboardController@updateVariant');
$router->get('/seller/variants/delete', 'DashboardController@deleteVariant');
$router->get('/seller/variant-images/delete', 'DashboardController@deleteVariantGalleryImage');



// Seller inventory
$router->get('/seller/inventory', 'DashboardController@inventory');

// Seller orders
$router->get('/seller/orders', 'OrderController@index');
$router->get('/seller/order', 'OrderController@show');
$router->post('/seller/order/update-status', 'OrderController@updateStatus');

// Seller export
$router->get('/seller/export/orders', 'DashboardController@exportOrdersCsv');

// Cart
$router->get('/cart', 'CartController@index');
$router->post('/cart/add', 'CartController@add');
$router->get('/cart/remove', 'CartController@remove');
$router->post('/cart/update', 'CartController@update');

// Checkout
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout', 'CheckoutController@store');
$router->get('/order-success', 'CheckoutController@success');

// Payments
$router->get('/pay', 'PaymentController@pay');
$router->get('/payment-success', 'PaymentController@success');
$router->get('/payment-cancel', 'PaymentController@cancel');

// Stripe webhook
$router->post('/webhook/stripe', 'WebhookController@handleStripe');



// Admin
$router->get('/admin/users', 'AdminController@users');
$router->get('/admin/user/toggle-status', 'AdminController@toggleUserStatus');
$router->get('/admin/user/delete', 'AdminController@deleteUser');

$router->get('/admin/settings', 'AdminController@settings');
$router->post('/admin/settings', 'AdminController@saveSettings');

$router->get('/admin/reports', 'AdminController@reports');
$router->get('/admin/reports/users', 'AdminController@exportUsersCsv');
$router->get('/admin/reports/user-status-summary', 'AdminController@exportUserStatusSummaryCsv');

$router->get('/admin/logs', 'AdminController@logs');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);