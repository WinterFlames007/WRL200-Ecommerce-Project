<h1>Payment Submitted</h1>

<p>Your payment was completed on Stripe.</p>

<?php if (!empty($sessionId)): ?>
    <p>Stripe Session ID: <strong><?= htmlspecialchars($sessionId) ?></strong></p>
<?php endif; ?>

<p>
    <a href="/customer/dashboard">Customer Dashboard</a> |
    <a href="/shop">Continue Shopping</a> |
    <a href="/logout">Logout</a>
</p>

<p><strong>Important:</strong> The final payment confirmation will be completed in the webhook step.</p>