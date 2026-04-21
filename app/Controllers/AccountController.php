<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class AccountController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $customerId = (int) $_SESSION['user']['id'];

        $userStmt = mysqli_prepare(
            $db,
            "SELECT
                id,
                full_name,
                email,
                phone,
                address_line1,
                city,
                postcode,
                country
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($userStmt, "i", $customerId);
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);
        $user = mysqli_fetch_assoc($userResult);




        $perPage = 6;
                $currentPage = max(1, (int) ($_GET['page'] ?? 1));
                $offset = ($currentPage - 1) * $perPage;

                $countStmt = mysqli_prepare(
                    $db,
                    "SELECT COUNT(*) AS total_orders
                    FROM orders
                    WHERE customer_id = ?"
                );
                mysqli_stmt_bind_param($countStmt, "i", $customerId);
                mysqli_stmt_execute($countStmt);
                $countResult = mysqli_stmt_get_result($countStmt);
                $countRow = mysqli_fetch_assoc($countResult);

                $totalOrders = (int) ($countRow['total_orders'] ?? 0);
                $totalPages = max(1, (int) ceil($totalOrders / $perPage));

                if ($currentPage > $totalPages) {
                    $currentPage = $totalPages;
                    $offset = ($currentPage - 1) * $perPage;
                }

                $ordersStmt = mysqli_prepare(
                    $db,
                    "SELECT
                        id,
                        order_number,
                        created_at,
                        status,
                        total_amount
                    FROM orders
                    WHERE customer_id = ?
                    ORDER BY id DESC
                    LIMIT ? OFFSET ?"
                );
                mysqli_stmt_bind_param($ordersStmt, "iii", $customerId, $perPage, $offset);
                mysqli_stmt_execute($ordersStmt);
                $ordersResult = mysqli_stmt_get_result($ordersStmt);
                $orders = mysqli_fetch_all($ordersResult, MYSQLI_ASSOC);


        $success = $_SESSION['account_success'] ?? null;
        unset($_SESSION['account_success']);


        $this->view('account/index', [
                    'user' => $user,
                    'orders' => $orders,
                    'success' => $success,
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages
                ]);





    }

    public function editForm(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $customerId = (int) $_SESSION['user']['id'];

        $stmt = mysqli_prepare(
            $db,
            "SELECT
                id,
                full_name,
                email,
                phone,
                address_line1,
                city,
                postcode,
                country
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            header('Location: /account');
            exit;
        }

        $this->view('account/edit', [
            'user' => $user
        ]);
    }

    public function updateProfile(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();
        $customerId = (int) $_SESSION['user']['id'];

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addressLine1 = trim($_POST['address_line1'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postcode = trim($_POST['postcode'] ?? '');
        $country = trim($_POST['country'] ?? '');

        $errors = [];

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        $emailStmt = mysqli_prepare(
            $db,
            "SELECT id
             FROM users
             WHERE email = ? AND id != ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($emailStmt, "si", $email, $customerId);
        mysqli_stmt_execute($emailStmt);
        $emailResult = mysqli_stmt_get_result($emailStmt);

        if (mysqli_num_rows($emailResult) > 0) {
            $errors[] = 'That email address is already in use.';
        }

        if ($phone !== '') {
            $phoneStmt = mysqli_prepare(
                $db,
                "SELECT id
                 FROM users
                 WHERE phone = ? AND id != ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($phoneStmt, "si", $phone, $customerId);
            mysqli_stmt_execute($phoneStmt);
            $phoneResult = mysqli_stmt_get_result($phoneStmt);

            if (mysqli_num_rows($phoneResult) > 0) {
                $errors[] = 'That phone number is already in use.';
            }
        }

        if (!empty($errors)) {
            $this->view('account/edit', [
                'user' => [
                    'id' => $customerId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'address_line1' => $addressLine1,
                    'city' => $city,
                    'postcode' => $postcode,
                    'country' => $country
                ],
                'errors' => $errors
            ]);
            return;
        }

        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE users
             SET full_name = ?,
                 email = ?,
                 phone = ?,
                 address_line1 = ?,
                 city = ?,
                 postcode = ?,
                 country = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param(
            $updateStmt,
            "sssssssi",
            $fullName,
            $email,
            $phone,
            $addressLine1,
            $city,
            $postcode,
            $country,
            $customerId
        );
        mysqli_stmt_execute($updateStmt);

        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['phone'] = $phone;

        $_SESSION['account_success'] = 'Profile updated successfully.';
        header('Location: /account');
        exit;
    }

    public function orderDetails(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $orderId = (int) ($_GET['id'] ?? 0);
        $customerId = (int) $_SESSION['user']['id'];

        if ($orderId <= 0) {
            header('Location: /account');
            exit;
        }

        $db = Database::connect();

        $orderStmt = mysqli_prepare(
            $db,
            "SELECT *
             FROM orders
             WHERE id = ? AND customer_id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($orderStmt, "ii", $orderId, $customerId);
        mysqli_stmt_execute($orderStmt);
        $orderResult = mysqli_stmt_get_result($orderStmt);
        $order = mysqli_fetch_assoc($orderResult);

        if (!$order) {
            header('Location: /account');
            exit;
        }

        $itemsStmt = mysqli_prepare(
            $db,
            "SELECT
                oi.quantity,
                oi.unit_price,
                oi.line_total,
                pv.size,
                pv.colour,
                pv.image_path AS variant_image_path,
                p.name AS product_name,
                p.image_path AS product_image_path
             FROM order_items oi
             INNER JOIN product_variants pv ON oi.variant_id = pv.id
             INNER JOIN products p ON pv.product_id = p.id
             WHERE oi.order_id = ?"
        );
        mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
        mysqli_stmt_execute($itemsStmt);
        $itemsResult = mysqli_stmt_get_result($itemsStmt);
        $items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

        $this->view('account/order', [
            'order' => $order,
            'items' => $items
        ]);
    }
}