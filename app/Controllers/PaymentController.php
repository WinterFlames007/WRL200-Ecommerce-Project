<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentController extends Controller
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
            return (int) $cart['id'];
        }

        $insert = mysqli_prepare(
            $db,
            "INSERT INTO carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())"
        );
        mysqli_stmt_bind_param($insert, "i", $userId);
        mysqli_stmt_execute($insert);

        return (int) mysqli_insert_id($db);
    }

    private function clearCustomerCart(int $userId): void
    {
        $db = Database::connect();
        $cartId = $this->getOrCreateCartId($userId);

        $stmt = mysqli_prepare($db, "DELETE FROM cart_items WHERE cart_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $cartId);
        mysqli_stmt_execute($stmt);

        unset($_SESSION['cart']);
    }

    public function pay(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $orderNumber = $_SESSION['last_order_number'] ?? null;

        if (!$orderNumber) {
            echo 'No order found for payment.';
            return;
        }

        $db = Database::connect();

        $stmt = mysqli_prepare(
            $db,
            "SELECT * FROM orders
             WHERE order_number = ? AND customer_id = ?
             LIMIT 1"
        );

        $customerId = (int) $_SESSION['user']['id'];
        mysqli_stmt_bind_param($stmt, "si", $orderNumber, $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);

        if (!$order) {
            echo 'Order not found.';
            return;
        }

        if ($order['status'] !== 'pending_payment') {
            header('Location: /order-success');
            exit;
        }

        $config = require __DIR__ . '/../../config/config.php';

        Stripe::setApiKey($config['stripe']['secret_key']);

        $baseUrl = rtrim($config['app']['url'], '/');

        $session = Session::create([
            'mode' => 'payment',
            'success_url' => $baseUrl . '/payment-success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $baseUrl . '/payment-cancel?order=' . urlencode($orderNumber),
            'line_items' => [[
                'price_data' => [
                    'currency' => $config['stripe']['currency'],
                    'product_data' => [
                        'name' => 'Order ' . $order['order_number'],
                    ],
                    'unit_amount' => (int) round(((float) $order['total_amount']) * 100),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'order_id' => $order['id'],
                'order_number' => $order['order_number'],
                'customer_id' => $customerId,
            ],
        ]);

        $checkPaymentStmt = mysqli_prepare(
            $db,
            "SELECT id FROM payments WHERE order_id = ? AND stripe_session_id = ? LIMIT 1"
        );

        $orderId = (int) $order['id'];
        $sessionId = $session->id;
        mysqli_stmt_bind_param($checkPaymentStmt, "is", $orderId, $sessionId);
        mysqli_stmt_execute($checkPaymentStmt);
        $checkPaymentResult = mysqli_stmt_get_result($checkPaymentStmt);
        $existingPayment = mysqli_fetch_assoc($checkPaymentResult);

        if (!$existingPayment) {
            $insertPayment = mysqli_prepare(
                $db,
                "INSERT INTO payments
                 (order_id, provider, stripe_session_id, status, amount, currency, created_at)
                 VALUES (?, 'stripe', ?, 'pending', ?, ?, NOW())"
            );

            $amount = (float) $order['total_amount'];
            $currency = strtoupper($config['stripe']['currency']);

            mysqli_stmt_bind_param(
                $insertPayment,
                "isds",
                $orderId,
                $sessionId,
                $amount,
                $currency
            );

            mysqli_stmt_execute($insertPayment);
        }

        header('Location: ' . $session->url);
        exit;
    }

    public function success(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $sessionId = $_GET['session_id'] ?? null;

        if (!$sessionId) {
            header('Location: /shop');
            exit;
        }

        $config = require __DIR__ . '/../../config/config.php';
        Stripe::setApiKey($config['stripe']['secret_key']);

        try {
            $stripeSession = Session::retrieve($sessionId);
        } catch (\Throwable $e) {
            echo 'Unable to verify payment session.';
            return;
        }

        if (($stripeSession->payment_status ?? '') !== 'paid') {
            header('Location: /payment-cancel');
            exit;
        }

        $orderId = (int) ($stripeSession->metadata->order_id ?? 0);
        $orderNumber = $stripeSession->metadata->order_number ?? null;
        $customerId = (int) $_SESSION['user']['id'];

        if ($orderId <= 0 || !$orderNumber) {
            echo 'Payment metadata is incomplete.';
            return;
        }

        $db = Database::connect();

        mysqli_begin_transaction($db);

        try {
            $paymentIntentId = $stripeSession->payment_intent ?? null;
            $amountTotal = isset($stripeSession->amount_total) ? ($stripeSession->amount_total / 100) : 0;
            $currency = isset($stripeSession->currency) ? strtoupper($stripeSession->currency) : 'GBP';

            $paymentStmt = mysqli_prepare(
                $db,
                "UPDATE payments
                 SET stripe_payment_intent_id = ?,
                     status = 'succeeded',
                     amount = ?,
                     currency = ?,
                     paid_at = NOW()
                 WHERE order_id = ? AND stripe_session_id = ?"
            );

            mysqli_stmt_bind_param(
                $paymentStmt,
                "sdsis",
                $paymentIntentId,
                $amountTotal,
                $currency,
                $orderId,
                $sessionId
            );
            mysqli_stmt_execute($paymentStmt);

            $orderStmt = mysqli_prepare(
                $db,
                "UPDATE orders
                 SET status = 'paid'
                 WHERE id = ? AND order_number = ? AND customer_id = ?"
            );

            mysqli_stmt_bind_param(
                $orderStmt,
                "isi",
                $orderId,
                $orderNumber,
                $customerId
            );
            mysqli_stmt_execute($orderStmt);

            mysqli_commit($db);
        } catch (\Throwable $e) {
            mysqli_rollback($db);
            echo 'Failed to finalise payment.';
            return;
        }

        $_SESSION['last_order_number'] = $orderNumber;
        $this->clearCustomerCart($customerId);

        header('Location: /order-success');
        exit;
    }

    public function cancel(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        $orderNumber = $_GET['order'] ?? null;

        $this->view('payments/cancel', [
            'orderNumber' => $orderNumber
        ]);
    }
}