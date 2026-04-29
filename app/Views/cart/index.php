<div class="cart-page">
    <h1>Shopping Cart</h1>

    <?php if (empty($cart)): ?>
        <div class="cart-empty-state">
            <div class="cart-empty-icon">🛒</div>
            <h2>Your cart is empty</h2>
            <p>Looks like you have not added anything yet.</p>
            <a href="/shop" class="button">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <section class="cart-items-panel">
                <div class="cart-table-header">
                    <div class="cart-col-product">Product</div>
                    <div class="cart-col-price">Price</div>
                    <div class="cart-col-qty">Quantity</div>
                    <div class="cart-col-subtotal">Subtotal</div>
                </div>

                <?php foreach ($cart as $item): ?>
                    <?php
                    $lineTotal = (float)$item['unit_price'] * (int)$item['quantity'];

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

                    $cartImage = $item['variant_image_path'] ?? '';
                    if (empty($cartImage)) {
                        $cartImage = $item['product_image_path'] ?? '';
                    }

                    $productUrl = '/product?id=' . (int)$item['product_id'] . '&variant_id=' . (int)$item['variant_id'];
                    ?>
                    <div class="cart-item-row">
                        <div class="cart-col-product">
                            <div class="cart-product-box">
                                <a href="<?= htmlspecialchars($productUrl) ?>" class="cart-product-image-link">
                                    <?php if (!empty($cartImage)): ?>
                                        <div class="cart-product-image real-image" style="background-image: url('<?= htmlspecialchars($cartImage) ?>');"></div>
                                    <?php else: ?>
                                        <div class="cart-product-image <?= $productClass ?>"></div>
                                    <?php endif; ?>
                                </a>

                                <div class="cart-product-info">
                                    <h3>
                                        <a href="<?= htmlspecialchars($productUrl) ?>" class="listing-title-link">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </a>
                                    </h3>

                                    <p class="cart-variant-meta">
                                        Size: <?= htmlspecialchars($item['size']) ?>,
                                        Color: <?= htmlspecialchars($item['colour']) ?>
                                    </p>

                                    <a href="/cart/remove?variant_id=<?= (int)$item['variant_id'] ?>" class="cart-remove-link">
                                        Remove
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="cart-col-price">
                            £<?= number_format((float)$item['unit_price'], 2) ?>
                        </div>

                        <div class="cart-col-qty">
                            <form method="POST" action="/cart/update" class="cart-qty-form">
                                <input type="hidden" name="variant_id" value="<?= (int)$item['variant_id'] ?>">
                                <button type="submit" name="action" value="decrease" class="cart-qty-btn">−</button>
                                <span class="cart-qty-value"><?= (int)$item['quantity'] ?></span>
                                <button type="submit" name="action" value="increase" class="cart-qty-btn">+</button>
                            </form>
                        </div>

                        <div class="cart-col-subtotal">
                            £<?= number_format($lineTotal, 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="cart-bottom-link">
                    <a href="/shop">Continue Shopping</a>
                </div>
            </section>

            <aside class="cart-summary-panel">
                <div class="summary-box">
                    <h2>Order Summary</h2>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong>£<?= number_format((float)$subtotal, 2) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <strong>£<?= number_format((float)$deliveryFee, 2) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>VAT (20%)</span>
                        <strong>£<?= number_format((float)$vatAmount, 2) ?></strong>
                    </div>

                    <div class="summary-total">
                        <span>Total</span>
                        <strong>£<?= number_format((float)$total, 2) ?></strong>
                    </div>

                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer'): ?>
                        <a href="/checkout" class="button cart-checkout-button">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="/login" class="button cart-checkout-button">Login to Checkout</a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>