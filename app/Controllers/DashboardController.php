<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{



    private function isAdmin(): bool
    {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
    }

    private function getScopeCondition(string $productAlias = 'p'): array
    {
        if ($this->isAdmin()) {
            return [
                'sql' => '1=1',
                'types' => '',
                'params' => []
            ];
        }

        return [
            'sql' => "{$productAlias}.created_by = ?",
            'types' => 'i',
            'params' => [(int) $_SESSION['user']['id']]
        ];
    }

    private function getOwnerUserIdForCreate(): int
    {
        if (!$this->isAdmin()) {
            return (int) $_SESSION['user']['id'];
        }

        $db = Database::connect();

        $stmt = mysqli_prepare(
            $db,
            "SELECT id
             FROM users
             WHERE role = 'seller' AND status = 'active'
             ORDER BY id ASC
             LIMIT 1"
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $seller = mysqli_fetch_assoc($result);

        if ($seller) {
            return (int) $seller['id'];
        }

        return (int) $_SESSION['user']['id'];
    }







    public function dashboard(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        // Total products
        $productsSql = "
            SELECT COUNT(*) AS total_products
            FROM products p
            WHERE {$scope['sql']}
        ";
        $productsStmt = mysqli_prepare($db, $productsSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($productsStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($productsStmt);
        $productsResult = mysqli_stmt_get_result($productsStmt);
        $productsData = mysqli_fetch_assoc($productsResult);

        // Total variants
        $variantsSql = "
            SELECT COUNT(*) AS total_variants
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE {$scope['sql']}
        ";
        $variantsStmt = mysqli_prepare($db, $variantsSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($variantsStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($variantsStmt);
        $variantsResult = mysqli_stmt_get_result($variantsStmt);
        $variantsData = mysqli_fetch_assoc($variantsResult);

        // Low stock count
        $lowStockSql = "
            SELECT COUNT(*) AS low_stock_count
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE {$scope['sql']} AND v.stock_qty <= 3
        ";
        $lowStockStmt = mysqli_prepare($db, $lowStockSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($lowStockStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($lowStockStmt);
        $lowStockResult = mysqli_stmt_get_result($lowStockStmt);
        $lowStockData = mysqli_fetch_assoc($lowStockResult);

        // Total paid orders and sales total
        $salesSql = "
            SELECT
                COUNT(DISTINCT o.id) AS total_paid_orders,
                COALESCE(SUM(DISTINCT o.total_amount), 0) AS total_sales
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE {$scope['sql']} AND o.status = 'paid'
        ";
        $salesStmt = mysqli_prepare($db, $salesSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($salesStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($salesStmt);
        $salesResult = mysqli_stmt_get_result($salesStmt);
        $salesData = mysqli_fetch_assoc($salesResult);

        // Small recent orders preview
        $recentOrdersSql = "
            SELECT DISTINCT
                o.id,
                o.order_number,
                o.delivery_name,
                o.total_amount,
                o.status,
                o.created_at
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE {$scope['sql']}
            ORDER BY o.id DESC
            LIMIT 5
        ";
        $recentOrdersStmt = mysqli_prepare($db, $recentOrdersSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($recentOrdersStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($recentOrdersStmt);
        $recentOrdersResult = mysqli_stmt_get_result($recentOrdersStmt);
        $recentOrders = mysqli_fetch_all($recentOrdersResult, MYSQLI_ASSOC);

        // Paginated order overview
        $ordersPerPage = 5;
        $ordersPage = max(1, (int) ($_GET['orders_page'] ?? 1));
        $ordersOffset = ($ordersPage - 1) * $ordersPerPage;

        $ordersCountSql = "
            SELECT COUNT(DISTINCT o.id) AS total_orders
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE {$scope['sql']}
        ";
        $ordersCountStmt = mysqli_prepare($db, $ordersCountSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($ordersCountStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($ordersCountStmt);
        $ordersCountResult = mysqli_stmt_get_result($ordersCountStmt);
        $ordersCountRow = mysqli_fetch_assoc($ordersCountResult);

        $totalOrders = (int) ($ordersCountRow['total_orders'] ?? 0);
        $totalOrderPages = max(1, (int) ceil($totalOrders / $ordersPerPage));

        if ($ordersPage > $totalOrderPages) {
            $ordersPage = $totalOrderPages;
            $ordersOffset = ($ordersPage - 1) * $ordersPerPage;
        }

        $ordersOverviewSql = "
            SELECT DISTINCT
                o.id,
                o.order_number,
                o.delivery_name,
                o.total_amount,
                o.status,
                o.created_at
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE {$scope['sql']}
            ORDER BY o.id DESC
            LIMIT ? OFFSET ?
        ";
        $ordersOverviewStmt = mysqli_prepare($db, $ordersOverviewSql);
        $ordersTypes = $scope['types'] . 'ii';
        $ordersParams = array_merge($scope['params'], [$ordersPerPage, $ordersOffset]);
        mysqli_stmt_bind_param($ordersOverviewStmt, $ordersTypes, ...$ordersParams);
        mysqli_stmt_execute($ordersOverviewStmt);
        $ordersOverviewResult = mysqli_stmt_get_result($ordersOverviewStmt);
        $ordersOverview = mysqli_fetch_all($ordersOverviewResult, MYSQLI_ASSOC);

        // Low stock alerts
        $alertsSql = "
            SELECT
                p.name AS product_name,
                p.is_food,
                v.stock_qty,
                v.expiry_date
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE {$scope['sql']}
              AND (
                    v.stock_qty <= 3
                    OR (p.is_food = 1 AND v.expiry_date IS NOT NULL AND v.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
              )
            ORDER BY v.stock_qty ASC, v.expiry_date ASC
            LIMIT 4
        ";
        $alertsStmt = mysqli_prepare($db, $alertsSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($alertsStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($alertsStmt);
        $alertsResult = mysqli_stmt_get_result($alertsStmt);
        $lowStockAlerts = mysqli_fetch_all($alertsResult, MYSQLI_ASSOC);

        // Low stock items preview
        $lowStockItemsSql = "
            SELECT
                p.name AS product_name,
                v.stock_qty,
                v.expiry_date
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE {$scope['sql']} AND v.stock_qty <= 3
            ORDER BY v.stock_qty ASC
            LIMIT 3
        ";
        $lowStockItemsStmt = mysqli_prepare($db, $lowStockItemsSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($lowStockItemsStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($lowStockItemsStmt);
        $lowStockItemsResult = mysqli_stmt_get_result($lowStockItemsStmt);
        $lowStockItems = mysqli_fetch_all($lowStockItemsResult, MYSQLI_ASSOC);

        $this->view('seller/dashboard/index', [
            'totalProducts' => $productsData['total_products'] ?? 0,
            'totalVariants' => $variantsData['total_variants'] ?? 0,
            'lowStockCount' => $lowStockData['low_stock_count'] ?? 0,
            'totalPaidOrders' => $salesData['total_paid_orders'] ?? 0,
            'totalSales' => $salesData['total_sales'] ?? 0,
            'recentOrders' => $recentOrders,
            'ordersOverview' => $ordersOverview,
            'ordersPage' => $ordersPage,
            'totalOrderPages' => $totalOrderPages,
            'lowStockAlerts' => $lowStockAlerts,
            'lowStockItems' => $lowStockItems
        ]);
    }






    public function exportOrdersCsv(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $sellerId = (int) $_SESSION['user']['id'];

        $stmt = mysqli_prepare(
            $db,
            "SELECT
                o.id,
                o.order_number,
                o.delivery_name,
                o.delivery_email,
                o.status,
                o.total_amount,
                o.created_at
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE p.created_by = ?
            GROUP BY
                o.id,
                o.order_number,
                o.delivery_name,
                o.delivery_email,
                o.status,
                o.total_amount,
                o.created_at
            ORDER BY o.id DESC"
        );

        mysqli_stmt_bind_param($stmt, "i", $sellerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="seller_orders.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Order ID',
            'Order Number',
            'Customer Name',
            'Customer Email',
            'Status',
            'Total Amount',
            'Created At'
        ]);

        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['order_number'],
                $row['delivery_name'],
                $row['delivery_email'],
                $row['status'],
                $row['total_amount'],
                $row['created_at']
            ]);
        }

        fclose($output);
        exit;
    }


    public function products(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $search = trim($_GET['search'] ?? '');
        $categoryId = (int) ($_GET['category_id'] ?? 0);

        $perPage = 10;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $whereParts = [$scope['sql']];
        $types = $scope['types'];
        $params = $scope['params'];

        if ($search !== '') {
            $whereParts[] = "p.name LIKE ?";
            $types .= "s";
            $params[] = '%' . $search . '%';
        }

        if ($categoryId > 0) {
            $whereParts[] = "p.category_id = ?";
            $types .= "i";
            $params[] = $categoryId;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

        $countSql = "
            SELECT COUNT(DISTINCT p.id) AS total_products
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            {$whereClause}
        ";

        $countStmt = mysqli_prepare($db, $countSql);
        if ($types !== '') {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);

        $totalProductsCount = (int) ($countRow['total_products'] ?? 0);
        $totalPages = max(1, (int) ceil($totalProductsCount / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = "
            SELECT
                p.*,
                c.name AS category_name,
                COALESCE(SUM(v.stock_qty), 0) AS total_stock,
                MIN(v.expiry_date) AS nearest_expiry
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_variants v ON v.product_id = p.id
            {$whereClause}
            GROUP BY p.id, c.name
            ORDER BY p.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = mysqli_prepare($db, $sql);

        $productsParams = $params;
        $productsParams[] = $perPage;
        $productsParams[] = $offset;
        $productsTypes = $types . "ii";

        mysqli_stmt_bind_param($stmt, $productsTypes, ...$productsParams);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $products = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $categoryResult = mysqli_query($db, "SELECT id, name FROM categories ORDER BY name ASC");
        $categories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);

        $this->view('seller/products/index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'selectedCategoryId' => $categoryId,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalProductsCount' => $totalProductsCount
        ]);
    }


















    public function createProductForm(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $result = mysqli_query($db, "SELECT * FROM categories ORDER BY name ASC");
        $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $this->view('seller/products/create', [
            'categories' => $categories,
            'isEdit' => false
        ]);
    }




    public function storeProduct(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deliveryInfo = trim($_POST['delivery_info'] ?? '');
        $returnPolicy = trim($_POST['return_policy'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $basePrice = ($_POST['base_price'] ?? '') !== '' ? (float) $_POST['base_price'] : null;
        $isFood = isset($_POST['is_food']) ? 1 : 0;
        $createdBy = $this->getOwnerUserIdForCreate();

        $errors = [];
        $imagePath = null;
        $galleryImagePaths = [];

        if ($name === '') {
            $errors[] = 'Product name is required.';
        }

        if ($categoryId <= 0) {
            $errors[] = 'Please select a category.';
        }

        if ($basePrice !== null && $basePrice < 0) {
            $errors[] = 'Base price cannot be negative.';
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $uploadDir = __DIR__ . '/../../public/assets/images/products/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Main image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Main image upload failed.';
            } else {
                $originalName = $_FILES['product_image']['name'];
                $fileTmpPath = $_FILES['product_image']['tmp_name'];
                $fileSize = (int) $_FILES['product_image']['size'];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Main image must be JPG, JPEG, PNG, or WEBP.';
                }

                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Main image size must not exceed 5MB.';
                }

                if (empty($errors)) {
                    $newFileName = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $destination = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destination)) {
                        $imagePath = '/assets/images/products/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to save main product image.';
                    }
                }
            }
        }

        // Gallery images upload
        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            $galleryCount = count($_FILES['gallery_images']['name']);

            for ($i = 0; $i < $galleryCount; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = 'One of the gallery images failed to upload.';
                    continue;
                }

                $originalName = $_FILES['gallery_images']['name'][$i];
                $fileTmpPath = $_FILES['gallery_images']['tmp_name'][$i];
                $fileSize = (int) $_FILES['gallery_images']['size'][$i];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Gallery images must be JPG, JPEG, PNG, or WEBP.';
                    continue;
                }

                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Each gallery image must not exceed 5MB.';
                    continue;
                }

                $newFileName = 'gallery_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destination)) {
                    $galleryImagePaths[] = '/assets/images/products/' . $newFileName;
                } else {
                    $errors[] = 'Failed to save one of the gallery images.';
                }
            }
        }

        if (!empty($errors)) {
            $db = Database::connect();
            $result = mysqli_query($db, "SELECT * FROM categories ORDER BY name ASC");
            $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $this->view('seller/products/create', [
                'errors' => $errors,
                'categories' => $categories
            ]);
            return;
        }

        $db = Database::connect();
        mysqli_begin_transaction($db);

        try {
            $stmt = mysqli_prepare(
                $db,
                "INSERT INTO products
                (category_id, created_by, name, description, delivery_info, return_policy, image_path, base_price, is_food, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iisssssdi",
                $categoryId,
                $createdBy,
                $name,
                $description,
                $deliveryInfo,
                $returnPolicy,
                $imagePath,
                $basePrice,
                $isFood
            );

            mysqli_stmt_execute($stmt);
            $productId = (int) mysqli_insert_id($db);

            if (!empty($galleryImagePaths)) {
                $imageStmt = mysqli_prepare(
                    $db,
                    "INSERT INTO product_images (product_id, image_path, sort_order, created_at)
                    VALUES (?, ?, ?, NOW())"
                );

                foreach ($galleryImagePaths as $index => $galleryPath) {
                    $sortOrder = $index + 1;
                    mysqli_stmt_bind_param($imageStmt, "isi", $productId, $galleryPath, $sortOrder);
                    mysqli_stmt_execute($imageStmt);
                }
            }

            mysqli_commit($db);
            header('Location: /seller/products');
            exit;
        } catch (\Throwable $e) {
            mysqli_rollback($db);

            $result = mysqli_query($db, "SELECT * FROM categories ORDER BY name ASC");
            $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $this->view('seller/products/create', [
                'errors' => ['Failed to save product. Please try again.'],
                'categories' => $categories
            ]);
        }
    }



    public function editProductForm(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $productId = (int) ($_GET['id'] ?? 0);
        $db = Database::connect();
        $scope = $this->getScopeCondition('products');

        $sql = "
            SELECT *
            FROM products
            WHERE id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $stmt = mysqli_prepare($db, $sql);
        $types = 'i' . $scope['types'];
        $params = array_merge([$productId], $scope['params']);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if (!$product) {
            echo 'Product not found or access denied.';
            return;
        }

        $categoryResult = mysqli_query($db, "SELECT * FROM categories ORDER BY name ASC");
        $categories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);

        $this->view('seller/products/create', [
            'product' => $product,
            'categories' => $categories,
            'isEdit' => true
        ]);
    }




    public function updateProduct(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $productId = (int) ($_POST['id'] ?? 0);
        $db = Database::connect();
        $scope = $this->getScopeCondition('products');

        $checkSql = "
            SELECT *
            FROM products
            WHERE id = ? AND {$scope['sql']}
            LIMIT 1
        ";
        $checkStmt = mysqli_prepare($db, $checkSql);
        $checkTypes = 'i' . $scope['types'];
        $checkParams = array_merge([$productId], $scope['params']);
        mysqli_stmt_bind_param($checkStmt, $checkTypes, ...$checkParams);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $existingProduct = mysqli_fetch_assoc($checkResult);

        if (!$existingProduct) {
            echo 'Product not found or access denied.';
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deliveryInfo = trim($_POST['delivery_info'] ?? '');
        $returnPolicy = trim($_POST['return_policy'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $basePrice = ($_POST['base_price'] ?? '') !== '' ? (float) $_POST['base_price'] : null;
        $isFood = isset($_POST['is_food']) ? 1 : 0;

        $errors = [];
        $imagePath = $existingProduct['image_path'];

        if ($name === '') {
            $errors[] = 'Product name is required.';
        }

        if ($categoryId <= 0) {
            $errors[] = 'Please select a category.';
        }

        if ($basePrice !== null && $basePrice < 0) {
            $errors[] = 'Base price cannot be negative.';
        }

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Main image upload failed.';
            } else {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $originalName = $_FILES['product_image']['name'];
                $fileTmpPath = $_FILES['product_image']['tmp_name'];
                $fileSize = (int) $_FILES['product_image']['size'];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Main image must be JPG, JPEG, PNG, or WEBP.';
                }

                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Main image size must not exceed 5MB.';
                }

                if (empty($errors)) {
                    $newFileName = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $uploadDir = __DIR__ . '/../../public/assets/images/products/';
                    $destination = $uploadDir . $newFileName;

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    if (move_uploaded_file($fileTmpPath, $destination)) {
                        $imagePath = '/assets/images/products/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to save main product image.';
                    }
                }
            }
        }

        if (!empty($errors)) {
            $categoryResult = mysqli_query($db, "SELECT * FROM categories ORDER BY name ASC");
            $categories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);

            $product = array_merge($existingProduct, [
                'name' => $name,
                'description' => $description,
                'delivery_info' => $deliveryInfo,
                'return_policy' => $returnPolicy,
                'category_id' => $categoryId,
                'base_price' => $basePrice,
                'is_food' => $isFood,
                'image_path' => $imagePath
            ]);

            $this->view('seller/products/create', [
                'errors' => $errors,
                'categories' => $categories,
                'product' => $product,
                'isEdit' => true
            ]);
            return;
        }

        $stmt = mysqli_prepare(
            $db,
            "UPDATE products
             SET category_id = ?, name = ?, description = ?, delivery_info = ?, return_policy = ?, image_path = ?, base_price = ?, is_food = ?, updated_at = NOW()
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "isssssdii",
            $categoryId,
            $name,
            $description,
            $deliveryInfo,
            $returnPolicy,
            $imagePath,
            $basePrice,
            $isFood,
            $productId
        );

        mysqli_stmt_execute($stmt);

        header('Location: /seller/products');
        exit;
    }




    public function deleteProduct(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $productId = (int) ($_GET['id'] ?? 0);
        $db = Database::connect();
        $scope = $this->getScopeCondition('products');

        $checkSql = "
            SELECT id
            FROM products
            WHERE id = ? AND {$scope['sql']}
            LIMIT 1
        ";
        $checkStmt = mysqli_prepare($db, $checkSql);
        $checkTypes = 'i' . $scope['types'];
        $checkParams = array_merge([$productId], $scope['params']);
        mysqli_stmt_bind_param($checkStmt, $checkTypes, ...$checkParams);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $product = mysqli_fetch_assoc($checkResult);

        if (!$product) {
            echo 'Product not found or access denied.';
            return;
        }

        $stmt = mysqli_prepare($db, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);

        header('Location: /seller/products');
        exit;
    }




    public function variants(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $productId = (int) ($_GET['product_id'] ?? 0);

        if ($productId <= 0) {
            echo 'Invalid product ID.';
            return;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $productSql = "
            SELECT p.*, c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $productStmt = mysqli_prepare($db, $productSql);
        $productTypes = 'i' . $scope['types'];
        $productParams = array_merge([$productId], $scope['params']);
        mysqli_stmt_bind_param($productStmt, $productTypes, ...$productParams);
        mysqli_stmt_execute($productStmt);
        $productResult = mysqli_stmt_get_result($productStmt);
        $product = mysqli_fetch_assoc($productResult);

        if (!$product) {
            echo 'Product not found or access denied.';
            return;
        }

        $variantStmt = mysqli_prepare(
            $db,
            "SELECT *
             FROM product_variants
             WHERE product_id = ?
             ORDER BY id DESC"
        );
        mysqli_stmt_bind_param($variantStmt, "i", $productId);
        mysqli_stmt_execute($variantStmt);
        $variantResult = mysqli_stmt_get_result($variantStmt);
        $variants = mysqli_fetch_all($variantResult, MYSQLI_ASSOC);

        $this->view('seller/variants/index', [
            'product' => $product,
            'variants' => $variants
        ]);
    }





    public function createVariantForm(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $productId = (int) ($_GET['product_id'] ?? 0);

        if ($productId <= 0) {
            echo 'Invalid product ID.';
            return;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('products');

        $sql = "
            SELECT *
            FROM products
            WHERE id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $stmt = mysqli_prepare($db, $sql);
        $types = 'i' . $scope['types'];
        $params = array_merge([$productId], $scope['params']);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if (!$product) {
            echo 'Product not found or access denied.';
            return;
        }

        $this->view('seller/variants/create', ['product' => $product]);
    }




    public function storeVariant(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $size = trim($_POST['size'] ?? '');
        $colour = trim($_POST['colour'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $price = ($_POST['price'] ?? '') !== '' ? (float) $_POST['price'] : null;
        $stockQty = (int) ($_POST['stock_qty'] ?? 0);
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $status = $_POST['status'] ?? 'active';
 

        $errors = [];
        $imagePath = null;
        $galleryImagePaths = [];

        

        if ($productId <= 0) {
            $errors[] = 'Invalid product.';
        }

        if ($size === '') {
            $size = 'N/A';
        }

        if ($colour === '') {
            $colour = 'N/A';
        }

        if ($sku === '') {
            $errors[] = 'SKU is required.';
        }

        if ($price !== null && $price < 0) {
            $errors[] = 'Price cannot be negative.';
        }

        if ($stockQty < 0) {
            $errors[] = 'Stock quantity cannot be negative.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }





        $db = Database::connect();
        $scope = $this->getScopeCondition('products');

        $productSql = "
            SELECT *
            FROM products
            WHERE id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $productStmt = mysqli_prepare($db, $productSql);
        $productTypes = 'i' . $scope['types'];
        $productParams = array_merge([$productId], $scope['params']);
        mysqli_stmt_bind_param($productStmt, $productTypes, ...$productParams);



        
        mysqli_stmt_execute($productStmt);
        $productResult = mysqli_stmt_get_result($productStmt);
        $product = mysqli_fetch_assoc($productResult);

        if (!$product) {
            echo 'Product not found or access denied.';
            return;
        }

        if ($product['is_food'] == 1 && $expiryDate === '') {
            $errors[] = 'Expiry date is required for food products.';
        }

        if ($product['is_food'] == 0) {
            $expiryDate = null;
        }

        $skuCheckStmt = mysqli_prepare($db, "SELECT id FROM product_variants WHERE sku = ? LIMIT 1");
        mysqli_stmt_bind_param($skuCheckStmt, "s", $sku);
        mysqli_stmt_execute($skuCheckStmt);
        $skuCheckResult = mysqli_stmt_get_result($skuCheckStmt);

        if (mysqli_num_rows($skuCheckResult) > 0) {
            $errors[] = 'SKU already exists.';
        }

        if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['variant_image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Variant image upload failed.';
            } else {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $originalName = $_FILES['variant_image']['name'];
                $fileTmpPath = $_FILES['variant_image']['tmp_name'];
                $fileSize = (int) $_FILES['variant_image']['size'];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Variant image must be JPG, JPEG, PNG, or WEBP.';
                }

                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Variant image size must not exceed 5MB.';
                }

                if (empty($errors)) {
                    $newFileName = 'variant_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $uploadDir = __DIR__ . '/../../public/assets/images/products/';
                    $destination = $uploadDir . $newFileName;

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    if (move_uploaded_file($fileTmpPath, $destination)) {
                        $imagePath = '/assets/images/products/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to save variant image.';
                    }
                }
            }
        }



        if (isset($_FILES['variant_gallery_images']) && !empty($_FILES['variant_gallery_images']['name'][0])) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $uploadDir = __DIR__ . '/../../public/assets/images/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $galleryCount = count($_FILES['variant_gallery_images']['name']);

            for ($i = 0; $i < $galleryCount; $i++) {
                if ($_FILES['variant_gallery_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($_FILES['variant_gallery_images']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = 'One of the variant gallery images failed to upload.';
                    continue;
                }

                $originalName = $_FILES['variant_gallery_images']['name'][$i];
                $fileTmpPath = $_FILES['variant_gallery_images']['tmp_name'][$i];
                $fileSize = (int) $_FILES['variant_gallery_images']['size'][$i];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Variant gallery images must be JPG, JPEG, PNG, or WEBP.';
                    continue;
                }

                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Each variant gallery image must not exceed 5MB.';
                    continue;
                }

                $newFileName = 'variant_gallery_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destination)) {
                    $galleryImagePaths[] = '/assets/images/products/' . $newFileName;
                } else {
                    $errors[] = 'Failed to save one of the variant gallery images.';
                }
            }
        }



        if (!empty($errors)) {
            $this->view('seller/variants/create', [
                'errors' => $errors,
                'product' => $product
            ]);
            return;
        }

        $stmt = mysqli_prepare(
            $db,
            "INSERT INTO product_variants
            (product_id, size, colour, image_path, sku, price, stock_qty, expiry_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "issssdiss",
            $productId,
            $size,
            $colour,
            $imagePath,
            $sku,
            $price,
            $stockQty,
            $expiryDate,
            $status
        );



        if (mysqli_stmt_execute($stmt)) {
            $variantId = (int) mysqli_insert_id($db);

            if (!empty($galleryImagePaths)) {
                $galleryStmt = mysqli_prepare(
                    $db,
                    "INSERT INTO variant_images (variant_id, image_path, sort_order, created_at)
                    VALUES (?, ?, ?, NOW())"
                );

                foreach ($galleryImagePaths as $index => $galleryPath) {
                    $sortOrder = $index + 1;
                    mysqli_stmt_bind_param($galleryStmt, "isi", $variantId, $galleryPath, $sortOrder);
                    mysqli_stmt_execute($galleryStmt);
                }
            }

            header('Location: /seller/variants?product_id=' . $productId);
            exit;
        }



        $this->view('seller/variants/create', [
            'errors' => ['Failed to save variant. Please try again.'],
            'product' => $product
        ]);
    }

    


    public function editVariantForm(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $variantId = (int) ($_GET['id'] ?? 0);
        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $sql = "
            SELECT v.*, p.name AS product_name, p.id AS product_id, p.is_food
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE v.id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $stmt = mysqli_prepare($db, $sql);
        $types = 'i' . $scope['types'];
        $params = array_merge([$variantId], $scope['params']);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $variant = mysqli_fetch_assoc($result);

        if (!$variant) {
            echo 'Variant not found or access denied.';
            return;
        }

        $product = [
            'id' => $variant['product_id'],
            'name' => $variant['product_name'],
            'is_food' => $variant['is_food']
        ];

        $variantImagesStmt = mysqli_prepare(
            $db,
            "SELECT id, image_path, sort_order
             FROM variant_images
             WHERE variant_id = ?
             ORDER BY sort_order ASC, id ASC"
        );
        mysqli_stmt_bind_param($variantImagesStmt, "i", $variantId);
        mysqli_stmt_execute($variantImagesStmt);
        $variantImagesResult = mysqli_stmt_get_result($variantImagesStmt);
        $variantImages = mysqli_fetch_all($variantImagesResult, MYSQLI_ASSOC);

        $this->view('seller/variants/create', [
            'product' => $product,
            'variant' => $variant,
            'variantImages' => $variantImages,
            'isEdit' => true
        ]);
    }



    public function deleteVariantGalleryImage(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $imageId = (int) ($_GET['id'] ?? 0);
        $variantId = (int) ($_GET['variant_id'] ?? 0);

        if ($imageId <= 0 || $variantId <= 0) {
            echo 'Invalid image or variant ID.';
            return;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $sql = "
            SELECT vi.image_path
            FROM variant_images vi
            INNER JOIN product_variants v ON vi.variant_id = v.id
            INNER JOIN products p ON v.product_id = p.id
            WHERE vi.id = ? AND vi.variant_id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $stmt = mysqli_prepare($db, $sql);
        $types = 'ii' . $scope['types'];
        $params = array_merge([$imageId, $variantId], $scope['params']);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $image = mysqli_fetch_assoc($result);

        if (!$image) {
            echo 'Image not found or access denied.';
            return;
        }

        $deleteStmt = mysqli_prepare(
            $db,
            "DELETE FROM variant_images
             WHERE id = ? AND variant_id = ?"
        );
        mysqli_stmt_bind_param($deleteStmt, "ii", $imageId, $variantId);
        mysqli_stmt_execute($deleteStmt);

        if (!empty($image['image_path'])) {
            $filePath = __DIR__ . '/../../public' . $image['image_path'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        header('Location: /seller/variants/edit?id=' . $variantId);
        exit;
    }



    public function updateVariant(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $variantId = (int) ($_POST['id'] ?? 0);
        $productId = (int) ($_POST['product_id'] ?? 0);
        $size = trim($_POST['size'] ?? '');
        $colour = trim($_POST['colour'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $price = ($_POST['price'] ?? '') !== '' ? (float) $_POST['price'] : null;
        $stockQty = (int) ($_POST['stock_qty'] ?? 0);
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $status = $_POST['status'] ?? 'active';


        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $checkSql = "
            SELECT v.*, p.name AS product_name, p.is_food, p.created_by
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE v.id = ? AND {$scope['sql']}
            LIMIT 1
        ";
        $checkStmt = mysqli_prepare($db, $checkSql);
        $checkTypes = 'i' . $scope['types'];
        $checkParams = array_merge([$variantId], $scope['params']);
        mysqli_stmt_bind_param($checkStmt, $checkTypes, ...$checkParams);

        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $existingVariant = mysqli_fetch_assoc($checkResult);

        if (!$existingVariant) {
            echo 'Variant not found or access denied.';
            return;
        }

        if ($size === '') {
            $size = 'N/A';
        }

        if ($colour === '') {
            $colour = 'N/A';
        }

        $errors = [];
        $imagePath = $existingVariant['image_path'] ?? null;

        if ($sku === '') {
            $errors[] = 'SKU is required.';
        }

        if ($price !== null && $price < 0) {
            $errors[] = 'Price cannot be negative.';
        }

        if ($stockQty < 0) {
            $errors[] = 'Stock quantity cannot be negative.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if ((int)$existingVariant['is_food'] === 1 && $expiryDate === '') {
            $errors[] = 'Expiry date is required for food products.';
        }

        if ((int)$existingVariant['is_food'] === 0) {
            $expiryDate = null;
        }

        $skuCheckStmt = mysqli_prepare(
            $db,
            "SELECT id FROM product_variants WHERE sku = ? AND id != ? LIMIT 1"
        );
        mysqli_stmt_bind_param($skuCheckStmt, "si", $sku, $variantId);
        mysqli_stmt_execute($skuCheckStmt);
        $skuCheckResult = mysqli_stmt_get_result($skuCheckStmt);

        if (mysqli_num_rows($skuCheckResult) > 0) {
            $errors[] = 'SKU already exists.';
        }

        if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['variant_image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Variant image upload failed.';
            } else {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $originalName = $_FILES['variant_image']['name'];
                $fileTmpPath = $_FILES['variant_image']['tmp_name'];
                $fileSize = (int) $_FILES['variant_image']['size'];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Variant image must be JPG, JPEG, PNG, or WEBP.';
                }

                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Variant image size must not exceed 5MB.';
                }

                if (empty($errors)) {
                    $newFileName = 'variant_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $uploadDir = __DIR__ . '/../../public/assets/images/products/';
                    $destination = $uploadDir . $newFileName;

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    if (move_uploaded_file($fileTmpPath, $destination)) {
                        $imagePath = '/assets/images/products/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to save variant image.';
                    }
                }
            }
        }

        $product = [
            'id' => $existingVariant['product_id'],
            'name' => $existingVariant['product_name'],
            'is_food' => $existingVariant['is_food']
        ];



        if (!empty($errors)) {
            $variant = array_merge($existingVariant, [
                'id' => $variantId,
                'product_id' => $productId,
                'size' => $size,
                'colour' => $colour,
                'sku' => $sku,
                'price' => $price,
                'stock_qty' => $stockQty,
                'expiry_date' => $expiryDate,
                'status' => $status,
                'image_path' => $imagePath
            ]);


            $variantImagesStmt = mysqli_prepare(
                $db,
                "SELECT id, image_path, sort_order
                FROM variant_images
                WHERE variant_id = ?
                ORDER BY sort_order ASC, id ASC"
            );

            mysqli_stmt_bind_param($variantImagesStmt, "i", $variantId);
            mysqli_stmt_execute($variantImagesStmt);
            $variantImagesResult = mysqli_stmt_get_result($variantImagesStmt);
            $variantImages = mysqli_fetch_all($variantImagesResult, MYSQLI_ASSOC);


            $this->view('seller/variants/create', [
                'errors' => $errors,
                'product' => $product,
                'variant' => $variant,
                'variantImages' => $variantImages,
                'isEdit' => true
            ]);
            return;

        }



        $stmt = mysqli_prepare(
            $db,
            "UPDATE product_variants
            SET size = ?, colour = ?, image_path = ?, sku = ?, price = ?, stock_qty = ?, expiry_date = ?, status = ?
            WHERE id = ? AND product_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssdissii",
            $size,
            $colour,
            $imagePath,
            $sku,
            $price,
            $stockQty,
            $expiryDate,
            $status,
            $variantId,
            $productId
        );

        mysqli_stmt_execute($stmt);




        if (isset($_FILES['variant_gallery_images']) && !empty($_FILES['variant_gallery_images']['name'][0])) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $uploadDir = __DIR__ . '/../../public/assets/images/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $countStmt = mysqli_prepare(
                $db,
                "SELECT COUNT(*) AS total_images
                FROM variant_images
                WHERE variant_id = ?"
            );
            mysqli_stmt_bind_param($countStmt, "i", $variantId);
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
            $countRow = mysqli_fetch_assoc($countResult);
            $sortOrder = (int) ($countRow['total_images'] ?? 0);

            $galleryCount = count($_FILES['variant_gallery_images']['name']);

            for ($i = 0; $i < $galleryCount; $i++) {
                if ($_FILES['variant_gallery_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($_FILES['variant_gallery_images']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $originalName = $_FILES['variant_gallery_images']['name'][$i];
                $fileTmpPath = $_FILES['variant_gallery_images']['tmp_name'][$i];
                $fileSize = (int) $_FILES['variant_gallery_images']['size'][$i];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    continue;
                }

                if ($fileSize > 5 * 1024 * 1024) {
                    continue;
                }

                $newFileName = 'variant_gallery_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destination)) {
                    $galleryPath = '/assets/images/products/' . $newFileName;
                    $sortOrder++;

                    $galleryStmt = mysqli_prepare(
                        $db,
                        "INSERT INTO variant_images (variant_id, image_path, sort_order, created_at)
                        VALUES (?, ?, ?, NOW())"
                    );
                    mysqli_stmt_bind_param($galleryStmt, "isi", $variantId, $galleryPath, $sortOrder);
                    mysqli_stmt_execute($galleryStmt);
                }
            }
        }


        header('Location: /seller/variants?product_id=' . $productId);
        exit;
    }



    public function deleteVariant(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $variantId = (int) ($_GET['id'] ?? 0);
        $productId = (int) ($_GET['product_id'] ?? 0);
        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $checkSql = "
            SELECT v.id
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE v.id = ? AND v.product_id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $checkStmt = mysqli_prepare($db, $checkSql);
        $checkTypes = 'ii' . $scope['types'];
        $checkParams = array_merge([$variantId, $productId], $scope['params']);
        mysqli_stmt_bind_param($checkStmt, $checkTypes, ...$checkParams);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $variant = mysqli_fetch_assoc($checkResult);

        if (!$variant) {
            echo 'Variant not found or access denied.';
            return;
        }

        $stmt = mysqli_prepare($db, "DELETE FROM product_variants WHERE id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $variantId, $productId);
        mysqli_stmt_execute($stmt);

        header('Location: /seller/variants?product_id=' . $productId);
        exit;
    }


    public function inventory(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $inventoryFilter = trim($_GET['inventory_filter'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $whereParts = [$scope['sql']];
        $params = $scope['params'];
        $types = $scope['types'];

        if ($inventoryFilter === 'expired') {
            $whereParts[] = "p.is_food = 1";
            $whereParts[] = "v.expiry_date IS NOT NULL";
            $whereParts[] = "v.expiry_date < CURDATE()";
        } elseif ($inventoryFilter === 'out_of_stock') {
            $whereParts[] = "v.stock_qty <= 0";
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

        $statsSql = "
            SELECT
                COUNT(v.id) AS total_variants,
                COALESCE(SUM(v.stock_qty), 0) AS total_units,
                SUM(
                    CASE
                        WHEN p.is_food = 1
                        AND v.expiry_date IS NOT NULL
                        AND v.expiry_date < CURDATE()
                        THEN 1
                        ELSE 0
                    END
                ) AS total_expired,
                SUM(
                    CASE
                        WHEN v.stock_qty <= 0
                        THEN 1
                        ELSE 0
                    END
                ) AS total_out_of_stock
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            WHERE {$scope['sql']}
        ";

        $statsStmt = mysqli_prepare($db, $statsSql);
        if ($scope['types'] !== '') {
            mysqli_stmt_bind_param($statsStmt, $scope['types'], ...$scope['params']);
        }
        mysqli_stmt_execute($statsStmt);
        $statsResult = mysqli_stmt_get_result($statsStmt);
        $stats = mysqli_fetch_assoc($statsResult) ?: [];

        $countSql = "
            SELECT COUNT(*) AS total_rows
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            INNER JOIN categories c ON p.category_id = c.id
            {$whereClause}
        ";

        $countStmt = mysqli_prepare($db, $countSql);
        if ($types !== '') {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);

        $totalRows = (int) ($countRow['total_rows'] ?? 0);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = "
            SELECT
                p.id AS product_id,
                p.name AS product_name,
                p.is_food,
                c.name AS category_name,
                v.id AS variant_id,
                v.size,
                v.colour,
                v.sku,
                v.price,
                v.stock_qty,
                v.expiry_date,
                v.status
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
            INNER JOIN categories c ON p.category_id = c.id
            {$whereClause}
            ORDER BY p.name ASC, v.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = mysqli_prepare($db, $sql);
        $finalParams = $params;
        $finalParams[] = $perPage;
        $finalParams[] = $offset;
        $finalTypes = $types . "ii";

        mysqli_stmt_bind_param($stmt, $finalTypes, ...$finalParams);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $inventoryItems = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $this->view('seller/inventory/index', [
            'inventoryItems' => $inventoryItems,
            'inventoryFilter' => $inventoryFilter,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'totalProducts' => (int) ($stats['total_variants'] ?? 0),
            'totalUnits' => (int) ($stats['total_units'] ?? 0),
            'totalExpired' => (int) ($stats['total_expired'] ?? 0),
            'totalOutOfStock' => (int) ($stats['total_out_of_stock'] ?? 0)
        ]);
    }










}