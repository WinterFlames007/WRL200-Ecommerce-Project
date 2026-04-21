<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class OrderController extends Controller
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



    public function index(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $selectedCategoryId = (int) ($_GET['category_id'] ?? 0);
        $selectedStatus = trim($_GET['status'] ?? '');
        $selectedDateFrom = trim($_GET['date_from'] ?? '');
        $selectedDateTo = trim($_GET['date_to'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $allowedStatuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];

        $whereParts = [$scope['sql']];
        $params = $scope['params'];
        $types = $scope['types'];

        if ($selectedCategoryId > 0) {
            $whereParts[] = "p.category_id = ?";
            $params[] = $selectedCategoryId;
            $types .= "i";
        }

        if ($selectedStatus !== '' && in_array($selectedStatus, $allowedStatuses, true)) {
            $whereParts[] = "o.status = ?";
            $params[] = $selectedStatus;
            $types .= "s";
        }

        if ($selectedDateFrom !== '') {
            $whereParts[] = "DATE(o.created_at) >= ?";
            $params[] = $selectedDateFrom;
            $types .= "s";
        }

        if ($selectedDateTo !== '') {
            $whereParts[] = "DATE(o.created_at) <= ?";
            $params[] = $selectedDateTo;
            $types .= "s";
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

        $countSql = "
            SELECT COUNT(*) AS total_rows
            FROM (
                SELECT DISTINCT o.id
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                INNER JOIN product_variants pv ON oi.variant_id = pv.id
                INNER JOIN products p ON pv.product_id = p.id
                {$whereClause}
            ) AS filtered_orders
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
            SELECT DISTINCT
                o.id,
                o.order_number,
                o.status,
                o.total_amount,
                o.delivery_name,
                o.delivery_email,
                o.created_at
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            {$whereClause}
            ORDER BY o.id DESC
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

        $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $categoriesResult = mysqli_query($db, "SELECT id, name FROM categories ORDER BY name ASC");
        $categories = mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC);

        $this->view('seller/orders/index', [
            'orders' => $orders,
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedStatus' => $selectedStatus,
            'selectedDateFrom' => $selectedDateFrom,
            'selectedDateTo' => $selectedDateTo,
            'page' => $page,
            'totalPages' => $totalPages
        ]);
    }




    public function show(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $orderId = (int) ($_GET['id'] ?? 0);

        if ($orderId <= 0) {
            echo 'Invalid order ID.';
            return;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $orderSql = "
            SELECT DISTINCT o.*
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE o.id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $orderStmt = mysqli_prepare($db, $orderSql);
        $orderTypes = 'i' . $scope['types'];
        $orderParams = array_merge([$orderId], $scope['params']);
        mysqli_stmt_bind_param($orderStmt, $orderTypes, ...$orderParams);
        mysqli_stmt_execute($orderStmt);
        $orderResult = mysqli_stmt_get_result($orderStmt);
        $order = mysqli_fetch_assoc($orderResult);

        if (!$order) {
            echo 'Order not found or access denied.';
            return;
        }

        $itemsSql = "
            SELECT
                oi.quantity,
                oi.unit_price,
                oi.line_total,
                pv.size,
                pv.colour,
                pv.sku,
                p.name AS product_name
            FROM order_items oi
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE oi.order_id = ? AND {$scope['sql']}
        ";

        $itemsStmt = mysqli_prepare($db, $itemsSql);
        $itemsTypes = 'i' . $scope['types'];
        $itemsParams = array_merge([$orderId], $scope['params']);
        mysqli_stmt_bind_param($itemsStmt, $itemsTypes, ...$itemsParams);
        mysqli_stmt_execute($itemsStmt);
        $itemsResult = mysqli_stmt_get_result($itemsStmt);
        $items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

        $this->view('seller/orders/show', [
            'order' => $order,
            'items' => $items
        ]);
    }





    public function updateStatus(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $allowedStatuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];

        if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
            header('Location: /seller/orders');
            exit;
        }

        $db = Database::connect();
        $scope = $this->getScopeCondition('p');

        $checkSql = "
            SELECT DISTINCT o.id
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN product_variants pv ON oi.variant_id = pv.id
            INNER JOIN products p ON pv.product_id = p.id
            WHERE o.id = ? AND {$scope['sql']}
            LIMIT 1
        ";

        $checkStmt = mysqli_prepare($db, $checkSql);
        $checkTypes = 'i' . $scope['types'];
        $checkParams = array_merge([$orderId], $scope['params']);
        mysqli_stmt_bind_param($checkStmt, $checkTypes, ...$checkParams);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $order = mysqli_fetch_assoc($checkResult);

        if (!$order) {
            header('Location: /seller/orders');
            exit;
        }

        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE orders
             SET status = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($updateStmt, "si", $status, $orderId);
        mysqli_stmt_execute($updateStmt);

        header('Location: /seller/order?id=' . $orderId);
        exit;
    }


}