<div class="order-success-page">
    <?php if (!$order): ?>
        <div class="empty-state">
            <p>Order information could not be loaded.</p>
            <p><a href="/shop" class="button">Continue Shopping</a></p>
        </div>
    <?php else: ?>
        <div class="order-success-header">
            <div class="success-icon">✓</div>
            <h1>Thank you for your order!</h1>
            <p>Your payment was successful and your order is now being processed.</p>
        </div>

        <div class="order-success-top">
            <section class="order-info-panel">
                <div class="checkout-panel-box">
                    <div class="checkout-panel-title">
                        Order ID: #<?= htmlspecialchars($order['order_number']) ?>
                    </div>


                    <div class="order-meta-grid">
                        <div><strong>Date</strong></div>
                        <div><?= htmlspecialchars(date('F j, Y', strtotime($order['created_at']))) ?></div>

                        <div><strong>Payment Method</strong></div>
                        <div>Stripe</div>

                        <div><strong>Order Status</strong></div>
                        <div>
                            <span class="badge badge-success">Paid</span>
                        </div>

                        <div><strong>Total Paid</strong></div>
                        <div>£<?= number_format((float)$order['total_amount'], 2) ?></div>
                    </div>


                    <div class="order-address-box">
                        <h3>Delivery Address</h3>
                        <p><?= htmlspecialchars($order['delivery_address']) ?></p>
                        <p><?= htmlspecialchars($order['city']) ?></p>
                        <p><?= htmlspecialchars($order['postcode']) ?></p>
                        <p><?= htmlspecialchars($order['country']) ?></p>
                    </div>
                </div>
            </section>

            <aside class="order-summary-side">
                <div class="checkout-panel-box">
                    <div class="checkout-panel-title">Order Summary</div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong>£<?= number_format((float)$order['subtotal'], 2) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <strong>£<?= number_format((float)$order['delivery_fee'], 2) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>VAT (20%)</span>
                        <strong>£<?= number_format((float)$order['vat_amount'], 2) ?></strong>
                    </div>

                    <div class="summary-total">
                        <span>Total</span>
                        <strong>£<?= number_format((float)$order['total_amount'], 2) ?></strong>
                    </div>
                </div>
            </aside>
        </div>

        <div class="order-items-section">
            <div class="checkout-panel-box">

                <div class="order-items-header-row">
                    <div class="order-col-product">Product  </div>
                    <div class="order-col-price">Price</div>
                    <div class="order-col-qty">Quantity</div>
                    <div class="order-col-total">Sub Total</div>
                </div>

              
                <?php foreach ($items as $item): ?>
                    <?php
                    $productClass = 'product-image-default';
                    $name = strtolower($item['product_name']);

                    if (str_contains($name, 'green')) {
                        $productClass = 'product-image-green';
                    } elseif (str_contains($name, 'hair')) {
                        $productClass = 'product-image-hair';
                    } elseif (str_contains($name, 'chip')) {
                        $productClass = 'product-image-chips';
                    } elseif (str_contains($name, 'watch')) {
                        $productClass = 'product-image-watch';
                    } elseif (str_contains($name, 'black')) {
                        $productClass = 'product-image-dark';
                    } elseif (str_contains($name, 'shoe') || str_contains($name, 'sneaker')) {
                        $productClass = 'product-image-light';
                    }

                    $orderImage = $item['variant_image_path'] ?? '';
                    if (empty($orderImage)) {
                        $orderImage = $item['product_image_path'] ?? '';
                    }
                    ?>

                    <div class="order-item-row">
                        <div class="order-col-product">
                            <div class="order-item-product">
                                <?php if (!empty($orderImage)): ?>
                                    <div class="order-item-image real-image" style="background-image: url('<?= htmlspecialchars($orderImage) ?>');"></div>
                                <?php else: ?>
                                    <div class="order-item-image <?= $productClass ?>"></div>
                                <?php endif; ?>

                                <div class="order-item-info">
                                    <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                                    <p>
                                        Size: <?= htmlspecialchars($item['size']) ?>,
                                        Color: <?= htmlspecialchars($item['colour']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="order-col-price">
                            £<?= number_format((float)$item['unit_price'], 2) ?>
                        </div>

                        <div class="order-col-qty">
                            <?= (int)$item['quantity'] ?>
                        </div>

                        <div class="order-col-total">
                            £<?= number_format((float)$item['line_total'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>



















            </div>
        </div>

        <div class="order-success-note">
            <p>Your order will be processed within 24 hours.</p>
            <p>You will receive an email confirmation shortly.</p>
        </div>

        <div class="order-success-actions">
            <a href="/shop" class="button button-secondary">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>