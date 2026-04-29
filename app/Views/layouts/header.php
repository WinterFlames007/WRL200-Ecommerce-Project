<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkRelated E-Commerce</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php
$cartCount = 0;

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer') {
    try {
        $db = \App\Core\Database::connect();
        $userId = (int) $_SESSION['user']['id'];

        $stmt = mysqli_prepare(
            $db,
            "SELECT COALESCE(SUM(ci.quantity), 0) AS total_items
             FROM carts c
             INNER JOIN cart_items ci ON c.id = ci.cart_id
             INNER JOIN product_variants v ON ci.variant_id = v.id
             INNER JOIN products p ON v.product_id = p.id
             WHERE c.user_id = ?
               AND v.status = 'active'
               AND p.is_active = 1"
        );

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        $cartCount = (int) ($row['total_items'] ?? 0);
    } catch (\Throwable $e) {
        $cartCount = 0;
    }
} elseif (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int) $item['quantity'];
    }
}
?>

    <header class="site-header">
        <div class="container header-inner">



            <div class="brand">
                <a href="/" class="brand-link">
                    <img
                        src="/assets/images/logo.png"
                        alt="Store Logo"
                        class="brand-logo"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
                    >
                    <span class="brand-text-fallback" style="display:none;">WorkRelated Store</span>
                </a>
            </div>


            <nav class="main-nav">
                <a href="/">Home</a>
                <a href="/shop">Shop</a>
                <a href="/about">About</a>
                <a href="/contact">Contact</a>
                <a href="/reviews">Reviews</a>

                
                <!-- <a href="/shop">Categories</a> -->

                <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($_SESSION['user']['role'] === 'customer'): ?>
                        <a href="/account">Account</a>

                        <a href="/cart" class="cart-link">
                            🛒
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <?php if (in_array($_SESSION['user']['role'], ['seller', 'admin'], true)): ?>
                        <a href="/seller/dashboard">Seller Dashboard</a>
                        <a href="/seller/products">Products</a>
                        <a href="/seller/orders">Orders</a>
                        <a href="/seller/inventory">Inventory</a>
                    <?php endif; ?>

                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <a href="/admin/dashboard">Admin</a>
                        <a href="/admin/users">Users</a>
                    <?php endif; ?>

                    <a href="/logout" class="nav-cta">Logout</a>
                <?php else: ?>
                    <a href="/login">Account</a>

                    <a href="/cart" class="cart-link">
                        🛒
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="container">