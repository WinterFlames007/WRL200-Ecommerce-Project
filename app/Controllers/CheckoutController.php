<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class CheckoutController extends Controller
{
    private function getOrCreateCartId(int $userId): int
    {
        $db = Database::connect();

        $stmt = mysqli_prepare($db, "SELECT id FROM carts WHERE user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cart = mysqli_fetch_assoc($result);

        if ($cart) {
            return (int)$cart['id'];
        }

        $insert = mysqli_prepare(
            $db,
            "INSERT INTO carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())"
        );
        mysqli_stmt_bind_param($insert, "i", $userId);
        mysqli_stmt_execute($insert);

        return (int)mysqli_insert_id($db);
    }

    private function getCartItems(): array
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            return $_SESSION['cart'] ?? [];
        }

        $db = Database::connect();
        $userId = (int) $_SESSION['user']['id'];
        $cartId = $this->getOrCreateCartId($userId);

        $sql = "
            SELECT
                ci.quantity,
                v.id AS variant_id,
                v.size,
                v.colour,
                v.image_path AS variant_image_path,
                v.sku,
                v.price,
                v.stock_qty,
                v.expiry_date,
                v.status,
                p.id AS product_id,
                p.name AS product_name,
                p.image_path AS product_image_path,
                p.base_price,
                p.is_food
            FROM cart_items ci
            INNER JOIN product_variants v ON ci.variant_id = v.id
            INNER JOIN products p ON v.product_id = p.id
            WHERE ci.cart_id = ?
              AND v.status = 'active'
              AND p.is_active = 1
        ";

        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cartId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $items = [];
        $today = date('Y-m-d');

        while ($row = mysqli_fetch_assoc($result)) {
            $removeItem = false;

            if ((int)$row['stock_qty'] <= 0) {
                $removeItem = true;
            }

            if ((int)$row['is_food'] === 1 && !empty($row['expiry_date']) && $row['expiry_date'] <= $today) {
                $removeItem = true;
            }

            if ($removeItem) {
                $deleteStmt = mysqli_prepare($db, "DELETE FROM cart_items WHERE cart_id = ? AND variant_id = ?");
                mysqli_stmt_bind_param($deleteStmt, "ii", $cartId, $row['variant_id']);
                mysqli_stmt_execute($deleteStmt);
                continue;
            }

            $unitPrice = $row['price'] !== null ? (float)$row['price'] : (float)$row['base_price'];

            $items[$row['variant_id']] = [
                'variant_id' => $row['variant_id'],
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'product_image_path' => $row['product_image_path'],
                'variant_image_path' => $row['variant_image_path'],
                'size' => $row['size'],
                'colour' => $row['colour'],
                'sku' => $row['sku'],
                'unit_price' => $unitPrice,
                'quantity' => min((int)$row['quantity'], (int)$row['stock_qty']),
                'stock_qty' => $row['stock_qty'],
                'expiry_date' => $row['expiry_date']
            ];
        }

        return $items;
    }

    private function clearCustomerCart(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            unset($_SESSION['cart']);
            return;
        }

        $db = Database::connect();
        $userId = (int) $_SESSION['user']['id'];
        $cartId = $this->getOrCreateCartId($userId);

        $stmt = mysqli_prepare($db, "DELETE FROM cart_items WHERE cart_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $cartId);
        mysqli_stmt_execute($stmt);
    }

    private function getCheckoutProfile(int $customerId): array
    {
        $db = Database::connect();

        $stmt = mysqli_prepare(
            $db,
            "SELECT
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

        return [
            'delivery_name' => $user['full_name'] ?? '',
            'delivery_email' => $user['email'] ?? '',
            'delivery_phone' => $user['phone'] ?? '',
            'delivery_address' => $user['address_line1'] ?? '',
            'city' => $user['city'] ?? '',
            'postcode' => $user['postcode'] ?? '',
            'country' => $user['country'] ?? ''
        ];
    }

    public function index(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $cart = $this->getCartItems();

        if (empty($cart)) {
            header('Location: /cart');
            exit;
        }

        $subtotal = 0.00;
        foreach ($cart as $item) {
            $subtotal += ((float)$item['unit_price'] * (int)$item['quantity']);
        }

        $deliveryFee = 5.00;
        $vatAmount = round($subtotal * 0.20, 2);
        $totalAmount = $subtotal + $deliveryFee + $vatAmount;

        $customerId = (int) $_SESSION['user']['id'];
        $formData = $this->getCheckoutProfile($customerId);

        $this->view('checkout/index', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'vatAmount' => $vatAmount,
            'totalAmount' => $totalAmount,
            'formData' => $formData
        ]);
    }

    public function store(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $cart = $this->getCartItems();

        if (empty($cart)) {
            header('Location: /cart');
            exit;
        }

        $deliveryName = trim($_POST['delivery_name'] ?? '');
        $deliveryEmail = trim($_POST['delivery_email'] ?? '');
        $deliveryPhone = trim($_POST['delivery_phone'] ?? '');
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postcode = trim($_POST['postcode'] ?? '');
        $country = trim($_POST['country'] ?? '');

        $formData = [
            'delivery_name' => $deliveryName,
            'delivery_email' => $deliveryEmail,
            'delivery_phone' => $deliveryPhone,
            'delivery_address' => $deliveryAddress,
            'city' => $city,
            'postcode' => $postcode,
            'country' => $country
        ];

        $errors = [];

        if ($deliveryName === '') {
            $errors[] = 'Delivery name is required.';
        }

        if (!filter_var($deliveryEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid delivery email is required.';
        }

        if ($deliveryAddress === '') {
            $errors[] = 'Delivery address is required.';
        }

        if ($city === '') {
            $errors[] = 'City is required.';
        }

        if ($postcode === '') {
            $errors[] = 'Postcode is required.';
        }

        if ($country === '') {
            $errors[] = 'Country is required.';
        }

        $subtotal = 0.00;
        foreach ($cart as $item) {
            $subtotal += ((float)$item['unit_price'] * (int)$item['quantity']);
        }

        $deliveryFee = 5.00;
        $vatAmount = round($subtotal * 0.20, 2);
        $totalAmount = $subtotal + $deliveryFee + $vatAmount;

        if (!empty($errors)) {
            $this->view('checkout/index', [
                'cart' => $cart,
                'errors' => $errors,
                'subtotal' => $subtotal,
                'deliveryFee' => $deliveryFee,
                'vatAmount' => $vatAmount,
                'totalAmount' => $totalAmount,
                'formData' => $formData
            ]);
            return;
        }

        $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);
        $customerId = (int) $_SESSION['user']['id'];

        $db = Database::connect();
        mysqli_begin_transaction($db);

        try {
            $updateUserStmt = mysqli_prepare(
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
                $updateUserStmt,
                "sssssssi",
                $deliveryName,
                $deliveryEmail,
                $deliveryPhone,
                $deliveryAddress,
                $city,
                $postcode,
                $country,
                $customerId
            );
            mysqli_stmt_execute($updateUserStmt);

            $stmt = mysqli_prepare(
                $db,
                "INSERT INTO orders
                (customer_id, order_number, status, subtotal, delivery_fee, vat_amount, total_amount,
                 delivery_name, delivery_email, delivery_phone, delivery_address, city, postcode, country, created_at)
                 VALUES (?, ?, 'pending_payment', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "isddddsssssss",
                $customerId,
                $orderNumber,
                $subtotal,
                $deliveryFee,
                $vatAmount,
                $totalAmount,
                $deliveryName,
                $deliveryEmail,
                $deliveryPhone,
                $deliveryAddress,
                $city,
                $postcode,
                $country
            );

            mysqli_stmt_execute($stmt);
            $orderId = mysqli_insert_id($db);

            foreach ($cart as $item) {
                $variantId = (int) $item['variant_id'];
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = $unitPrice * $quantity;

                $itemStmt = mysqli_prepare(
                    $db,
                    "INSERT INTO order_items
                     (order_id, variant_id, quantity, unit_price, line_total)
                     VALUES (?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $itemStmt,
                    "iiidd",
                    $orderId,
                    $variantId,
                    $quantity,
                    $unitPrice,
                    $lineTotal
                );

                mysqli_stmt_execute($itemStmt);
            }

            mysqli_commit($db);


            $_SESSION['user']['full_name'] = $deliveryName;
            $_SESSION['user']['email'] = $deliveryEmail;
            $_SESSION['last_order_number'] = $orderNumber;


            header('Location: /pay');
            exit;

        } catch (\Throwable $e) {
            mysqli_rollback($db);

            $this->view('checkout/index', [
                'cart' => $cart,
                'errors' => ['Failed to create order. Please try again.'],
                'subtotal' => $subtotal,
                'deliveryFee' => $deliveryFee,
                'vatAmount' => $vatAmount,
                'totalAmount' => $totalAmount,
                'formData' => $formData
            ]);
        }
    }

    public function success(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $orderNumber = $_SESSION['last_order_number'] ?? null;

        if (!$orderNumber) {
            $this->view('checkout/success', [
                'order' => null,
                'items' => []
            ]);
            return;
        }

        $db = Database::connect();
        $customerId = (int) $_SESSION['user']['id'];

        $stmt = mysqli_prepare(
            $db,
            "SELECT *
             FROM orders
             WHERE order_number = ? AND customer_id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "si", $orderNumber, $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);

        if (!$order) {
            $this->view('checkout/success', [
                'order' => null,
                'items' => []
            ]);
            return;
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

        mysqli_stmt_bind_param($itemsStmt, "i", $order['id']);
        mysqli_stmt_execute($itemsStmt);
        $itemsResult = mysqli_stmt_get_result($itemsStmt);
        $items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

        $this->view('checkout/success', [
            'order' => $order,
            'items' => $items
        ]);
    }
}