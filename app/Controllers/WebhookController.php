<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function handleStripe(): void
    {
        $config = require __DIR__ . '/../../config/config.php';

        $webhookSecret = $config['stripe']['webhook_secret'] ?? '';

        if ($webhookSecret === '') {
            http_response_code(500);
            echo 'Webhook secret is not configured.';
            return;
        }

        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            echo 'Invalid payload.';
            return;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            echo 'Invalid signature.';
            return;
        }

        $db = Database::connect();




        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $orderId = (int)($session->metadata->order_id ?? 0);
            $orderNumber = $session->metadata->order_number ?? null;
            $customerId = (int)($session->metadata->customer_id ?? 0);
            $sessionId = $session->id ?? null;
            $paymentIntentId = $session->payment_intent ?? null;
            $amountTotal = isset($session->amount_total) ? ($session->amount_total / 100) : 0;
            $currency = isset($session->currency) ? strtoupper($session->currency) : 'GBP';

            if ($orderId > 0 && $sessionId) {
                mysqli_begin_transaction($db);

                try {
                    // 1. Update payment record
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

                    // 2. Update order status
                    $orderStmt = mysqli_prepare(
                        $db,
                        "UPDATE orders
                        SET status = 'paid'
                        WHERE id = ? AND order_number = ?"
                    );

                    mysqli_stmt_bind_param(
                        $orderStmt,
                        "is",
                        $orderId,
                        $orderNumber
                    );
                    mysqli_stmt_execute($orderStmt);

                    // 3. Check whether stock has already been reduced for this order
                    $movementCheckStmt = mysqli_prepare(
                        $db,
                        "SELECT COUNT(*) AS total_movements
                        FROM stock_movements
                        WHERE reference_order_id = ? AND reason = 'sale'"
                    );
                    mysqli_stmt_bind_param($movementCheckStmt, "i", $orderId);
                    mysqli_stmt_execute($movementCheckStmt);
                    $movementCheckResult = mysqli_stmt_get_result($movementCheckStmt);
                    $movementCheckRow = mysqli_fetch_assoc($movementCheckResult);

                    $alreadyReduced = (int)($movementCheckRow['total_movements'] ?? 0) > 0;

                    // 4. Only reduce stock once
                    if (!$alreadyReduced) {
                        $itemsStmt = mysqli_prepare(
                            $db,
                            "SELECT variant_id, quantity
                            FROM order_items
                            WHERE order_id = ?"
                        );

                        mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
                        mysqli_stmt_execute($itemsStmt);
                        $itemsResult = mysqli_stmt_get_result($itemsStmt);
                        $items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

                        foreach ($items as $item) {
                            $variantId = (int)$item['variant_id'];
                            $quantity = (int)$item['quantity'];

                            $updateStockStmt = mysqli_prepare(
                                $db,
                                "UPDATE product_variants
                                SET stock_qty = stock_qty - ?
                                WHERE id = ? AND stock_qty >= ?"
                            );

                            mysqli_stmt_bind_param(
                                $updateStockStmt,
                                "iii",
                                $quantity,
                                $variantId,
                                $quantity
                            );
                            mysqli_stmt_execute($updateStockStmt);

                            if (mysqli_stmt_affected_rows($updateStockStmt) === 0) {
                                throw new \Exception('Stock update failed for variant ID ' . $variantId);
                            }

                            $changeQty = -$quantity;
                            $reason = 'sale';
                            $createdBy = null;

                            $movementStmt = mysqli_prepare(
                                $db,
                                "INSERT INTO stock_movements
                                (variant_id, change_qty, reason, reference_order_id, created_by, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())"
                            );

                            mysqli_stmt_bind_param(
                                $movementStmt,
                                "iisii",
                                $variantId,
                                $changeQty,
                                $reason,
                                $orderId,
                                $createdBy
                            );
                            mysqli_stmt_execute($movementStmt);
                        }
                    }

                    // 5. Clear customer cart from database as a backup
                    if ($customerId > 0) {
                        $cartStmt = mysqli_prepare($db, "SELECT id FROM carts WHERE user_id = ? LIMIT 1");
                        mysqli_stmt_bind_param($cartStmt, "i", $customerId);
                        mysqli_stmt_execute($cartStmt);
                        $cartResult = mysqli_stmt_get_result($cartStmt);
                        $cartRow = mysqli_fetch_assoc($cartResult);

                        if (!empty($cartRow['id'])) {
                            $cartId = (int)$cartRow['id'];

                            $deleteCartItemsStmt = mysqli_prepare(
                                $db,
                                "DELETE ci
                                FROM cart_items ci
                                INNER JOIN order_items oi ON ci.variant_id = oi.variant_id
                                WHERE ci.cart_id = ? AND oi.order_id = ?"
                            );
                            mysqli_stmt_bind_param($deleteCartItemsStmt, "ii", $cartId, $orderId);
                            mysqli_stmt_execute($deleteCartItemsStmt);
                        }
                    }

                    mysqli_commit($db);



                } catch (\Throwable $e) {
                    mysqli_rollback($db);
                    error_log('Stripe webhook failed: ' . $e->getMessage());
                    http_response_code(500);
                    echo 'Webhook database update failed.';
                    return;
                }



            }

            
        }

        http_response_code(200);
        echo 'Webhook received successfully.';
    }
}