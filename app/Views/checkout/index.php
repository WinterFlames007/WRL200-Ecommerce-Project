<div class="checkout-page">
    <h1>Checkout</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <section class="checkout-form-panel">
            <div class="checkout-panel-box">
                <div class="checkout-panel-title">Billing Details</div>

                <form method="POST" action="/checkout" class="checkout-form">
                    <label for="delivery_name">Full Name</label>
                    <input
                        type="text"
                        id="delivery_name"
                        name="delivery_name"
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars($formData['delivery_name'] ?? '') ?>"
                        required
                    >

                    <label for="delivery_email">Email Address</label>
                    <input
                        type="email"
                        id="delivery_email"
                        name="delivery_email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($formData['delivery_email'] ?? '') ?>"
                        required
                    >

                    <label for="delivery_phone">Phone</label>
                    <input
                        type="text"
                        id="delivery_phone"
                        name="delivery_phone"
                        placeholder="Enter your phone number"
                        value="<?= htmlspecialchars($formData['delivery_phone'] ?? '') ?>"
                    >

                    <label for="delivery_address">Address</label>
                    <input
                        type="text"
                        id="delivery_address"
                        name="delivery_address"
                        placeholder="Enter your house address"
                        value="<?= htmlspecialchars($formData['delivery_address'] ?? '') ?>"
                        required
                    >

                    <label for="city">City</label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        placeholder="Enter your city"
                        value="<?= htmlspecialchars($formData['city'] ?? '') ?>"
                        required
                    >

                    <label for="postcode">Postcode</label>
                    <input
                        type="text"
                        id="postcode"
                        name="postcode"
                        placeholder="Enter your postcode"
                        value="<?= htmlspecialchars($formData['postcode'] ?? '') ?>"
                        required
                    >

                    <label for="country">Country</label>
                    <input
                        type="text"
                        id="country"
                        name="country"
                        placeholder="Enter your country"
                        value="<?= htmlspecialchars($formData['country'] ?? '') ?>"
                        required
                    >

                    <button type="submit" class="button checkout-submit-button">Continue to Payment</button>
                </form>
            </div>
        </section>

        <aside class="checkout-summary-panel">
            <div class="checkout-panel-box">
                <div class="checkout-panel-title">Order Summary</div>

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
                    <strong>£<?= number_format((float)$totalAmount, 2) ?></strong>
                </div>

                <div class="payment-preview-box">
                    <div class="payment-preview-header">
                        <span>Payment</span>
                        <span class="payment-badge">Stripe</span>
                    </div>

                    <div class="payment-preview-note single">
                        🔒 You will be redirected to Stripe to complete your payment securely.
                    </div>

                    <div class="payment-total-row">
                        <span>Total</span>
                        <strong>£<?= number_format((float)$totalAmount, 2) ?></strong>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>