<h1>Payment Cancelled</h1>

<p>Your payment was cancelled. Your order is still saved as pending payment.</p>

<?php if (!empty($orderNumber)): ?>
    <p>Order Number: <strong><?= htmlspecialchars($orderNumber) ?></strong></p>
<?php endif; ?>

<p>
    <a href="/customer/dashboard">Customer Dashboard</a> |
    <a href="/shop">Return to Shop</a> |
    <a href="/logout">Logout</a>
</p>