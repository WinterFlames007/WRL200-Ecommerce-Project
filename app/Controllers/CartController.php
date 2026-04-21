<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class CartController extends Controller
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

        $insert = mysqli_prepare($db, "INSERT INTO carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())");
        mysqli_stmt_bind_param($insert, "i", $userId);
        mysqli_stmt_execute($insert);

        return (int)mysqli_insert_id($db);
    }

    private function getCartItemsFromDatabase(int $userId): array
    {
        $db = Database::connect();
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

    private function getCartItems(): array
    {
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer') {
            return $this->getCartItemsFromDatabase((int)$_SESSION['user']['id']);
        }

        return $_SESSION['cart'] ?? [];
    }

    public function index(): void
    {
        $cart = $this->getCartItems();

        $subtotal = 0.00;

        foreach ($cart as $item) {
            $subtotal += ((float)$item['unit_price'] * (int)$item['quantity']);
        }

        $deliveryFee = !empty($cart) ? 5.00 : 0.00;
        $vatAmount = !empty($cart) ? round($subtotal * 0.20, 2) : 0.00;
        $total = $subtotal + $deliveryFee + $vatAmount;

        $this->view('cart/index', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'vatAmount' => $vatAmount,
            'total' => $total
        ]);
    }

    public function add(): void
    {
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $redirectTo = $_SERVER['HTTP_REFERER'] ?? '/shop';

        if ($variantId <= 0 || $quantity <= 0) {
            header('Location: ' . $redirectTo);
            exit;
        }

        $db = Database::connect();

        $stmt = mysqli_prepare(
            $db,
            "SELECT 
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
            FROM product_variants v
            INNER JOIN products p ON v.product_id = p.id
             WHERE v.id = ? AND v.status = 'active' AND p.is_active = 1
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "i", $variantId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $variant = mysqli_fetch_assoc($result);

        if (!$variant) {
            $_SESSION['flash_error'] = 'Variant not found.';
            header('Location: ' . $redirectTo);
            exit;
        }

        if ((int)$variant['stock_qty'] < $quantity) {
            $_SESSION['flash_error'] = 'Not enough stock available.';
            header('Location: ' . $redirectTo);
            exit;
        }

        if ((int)$variant['is_food'] === 1 && !empty($variant['expiry_date'])) {
            if (strtotime($variant['expiry_date']) <= strtotime(date('Y-m-d'))) {
                $_SESSION['flash_error'] = 'This food item is expired and cannot be added to cart.';
                header('Location: ' . $redirectTo);
                exit;
            }
        }

        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer') {
            $userId = (int)$_SESSION['user']['id'];
            $cartId = $this->getOrCreateCartId($userId);

            $checkStmt = mysqli_prepare($db, "SELECT quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? LIMIT 1");
            mysqli_stmt_bind_param($checkStmt, "ii", $cartId, $variantId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $existing = mysqli_fetch_assoc($checkResult);

            if ($existing) {
                $newQty = min(((int)$existing['quantity'] + $quantity), (int)$variant['stock_qty']);
                $updateStmt = mysqli_prepare($db, "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND variant_id = ?");
                mysqli_stmt_bind_param($updateStmt, "iii", $newQty, $cartId, $variantId);
                mysqli_stmt_execute($updateStmt);
            } else {
                $insertStmt = mysqli_prepare($db, "INSERT INTO cart_items (cart_id, variant_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                mysqli_stmt_bind_param($insertStmt, "iii", $cartId, $variantId, $quantity);
                mysqli_stmt_execute($insertStmt);
            }
        } else {
            $unitPrice = $variant['price'] !== null ? (float)$variant['price'] : (float)$variant['base_price'];

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $existingQty = $_SESSION['cart'][$variantId]['quantity'] ?? 0;
            $newQty = min(($existingQty + $quantity), (int)$variant['stock_qty']);


            $_SESSION['cart'][$variantId] = [
                'variant_id' => $variant['variant_id'],
                'product_id' => $variant['product_id'],
                'product_name' => $variant['product_name'],
                'product_image_path' => $variant['product_image_path'],
                'variant_image_path' => $variant['variant_image_path'],
                'size' => $variant['size'],
                'colour' => $variant['colour'],
                'sku' => $variant['sku'],
                'unit_price' => $unitPrice,
                'quantity' => $newQty,
                'stock_qty' => $variant['stock_qty'],
                'expiry_date' => $variant['expiry_date']
            ];

        }

        $_SESSION['flash_success'] = 'Item added to cart.';
        header('Location: ' . $redirectTo);
        exit;
    }

    public function remove(): void
    {
        $variantId = (int) ($_GET['variant_id'] ?? 0);

        if ($variantId > 0) {
            if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer') {
                $db = Database::connect();
                $cartId = $this->getOrCreateCartId((int)$_SESSION['user']['id']);
                $stmt = mysqli_prepare($db, "DELETE FROM cart_items WHERE cart_id = ? AND variant_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $cartId, $variantId);
                mysqli_stmt_execute($stmt);
            } elseif (isset($_SESSION['cart'][$variantId])) {
                unset($_SESSION['cart'][$variantId]);
            }
        }

        header('Location: /cart');
        exit;
    }

    public function update(): void
    {
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($variantId <= 0) {
            header('Location: /cart');
            exit;
        }

        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer') {
            $db = Database::connect();
            $cartId = $this->getOrCreateCartId((int)$_SESSION['user']['id']);

            $stmt = mysqli_prepare(
                $db,
                "SELECT ci.quantity, v.stock_qty
                 FROM cart_items ci
                 INNER JOIN product_variants v ON ci.variant_id = v.id
                 WHERE ci.cart_id = ? AND ci.variant_id = ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "ii", $cartId, $variantId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);

            if ($item) {
                $currentQty = (int)$item['quantity'];
                $stockQty = (int)$item['stock_qty'];

                if ($action === 'increase' && $currentQty < $stockQty) {
                    $newQty = $currentQty + 1;
                    $updateStmt = mysqli_prepare($db, "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND variant_id = ?");
                    mysqli_stmt_bind_param($updateStmt, "iii", $newQty, $cartId, $variantId);
                    mysqli_stmt_execute($updateStmt);
                }

                if ($action === 'decrease') {
                    $newQty = $currentQty - 1;

                    if ($newQty <= 0) {
                        $deleteStmt = mysqli_prepare($db, "DELETE FROM cart_items WHERE cart_id = ? AND variant_id = ?");
                        mysqli_stmt_bind_param($deleteStmt, "ii", $cartId, $variantId);
                        mysqli_stmt_execute($deleteStmt);
                    } else {
                        $updateStmt = mysqli_prepare($db, "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND variant_id = ?");
                        mysqli_stmt_bind_param($updateStmt, "iii", $newQty, $cartId, $variantId);
                        mysqli_stmt_execute($updateStmt);
                    }
                }
            }
        } else {
            if (!isset($_SESSION['cart'][$variantId])) {
                header('Location: /cart');
                exit;
            }

            $currentQty = (int)$_SESSION['cart'][$variantId]['quantity'];
            $stockQty = (int)$_SESSION['cart'][$variantId]['stock_qty'];

            if ($action === 'increase' && $currentQty < $stockQty) {
                $_SESSION['cart'][$variantId]['quantity']++;
            }

            if ($action === 'decrease') {
                $_SESSION['cart'][$variantId]['quantity']--;

                if ($_SESSION['cart'][$variantId]['quantity'] <= 0) {
                    unset($_SESSION['cart'][$variantId]);
                }
            }
        }

        header('Location: /cart');
        exit;
    }

    public function mergeSessionCartToDatabase(int $userId): void
    {
        if (empty($_SESSION['cart'])) {
            return;
        }

        $db = Database::connect();
        $cartId = $this->getOrCreateCartId($userId);

        foreach ($_SESSION['cart'] as $variantId => $item) {
            $variantId = (int)$variantId;
            $quantity = (int)$item['quantity'];

            $stockStmt = mysqli_prepare($db, "SELECT stock_qty, status FROM product_variants WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($stockStmt, "i", $variantId);
            mysqli_stmt_execute($stockStmt);
            $stockResult = mysqli_stmt_get_result($stockStmt);
            $variant = mysqli_fetch_assoc($stockResult);

            if (!$variant || $variant['status'] !== 'active' || (int)$variant['stock_qty'] <= 0) {
                continue;
            }

            $quantity = min($quantity, (int)$variant['stock_qty']);

            $checkStmt = mysqli_prepare($db, "SELECT quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? LIMIT 1");
            mysqli_stmt_bind_param($checkStmt, "ii", $cartId, $variantId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $existing = mysqli_fetch_assoc($checkResult);

            if ($existing) {
                $newQty = min(((int)$existing['quantity'] + $quantity), (int)$variant['stock_qty']);
                $updateStmt = mysqli_prepare($db, "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND variant_id = ?");
                mysqli_stmt_bind_param($updateStmt, "iii", $newQty, $cartId, $variantId);
                mysqli_stmt_execute($updateStmt);
            } else {
                $insertStmt = mysqli_prepare($db, "INSERT INTO cart_items (cart_id, variant_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                mysqli_stmt_bind_param($insertStmt, "iii", $cartId, $variantId, $quantity);
                mysqli_stmt_execute($insertStmt);
            }
        }

        unset($_SESSION['cart']);
    }
}