<div class="seller-dashboard-page">
    <div class="seller-shell">
        <aside class="seller-sidebar">
            <div class="seller-sidebar-brand">StoreName</div>

            <nav class="seller-sidebar-nav">
                <a href="/seller/dashboard" class="active">Dashboard</a>
                <a href="/seller/products">Products</a>
                <a href="/seller/orders">Orders</a>
                <a href="/seller/inventory">Inventory</a>
                <a href="/seller/export/orders">Reports</a>
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="seller-main">
            <div class="seller-topbar">
                <h1>Seller Dashboard</h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <div class="seller-stat-grid">
                <div class="seller-stat-card blue">
                    <div class="seller-stat-label">Total Sales</div>
                    <div class="seller-stat-value">£<?= number_format((float)$totalSales, 2) ?></div>
                </div>

                <div class="seller-stat-card green">
                    <div class="seller-stat-label">Total Orders</div>
                    <div class="seller-stat-value"><?= htmlspecialchars($totalPaidOrders) ?></div>
                </div>

                <div class="seller-stat-card orange">
                    <div class="seller-stat-label">Low Stock Items</div>
                    <div class="seller-stat-value"><?= htmlspecialchars($lowStockCount) ?></div>
                </div>
            </div>

            <div class="seller-dashboard-upper">
                <section class="dashboard-panel">
                    <div class="dashboard-panel-title">Recent Orders</div>

                    <?php if (empty($recentOrders)): ?>
                        <div class="dashboard-panel-body">
                            <p>No recent paid orders found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="seller-mini-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                            <td><?= htmlspecialchars($order['delivery_name']) ?></td>
                                            <td>£<?= number_format((float)$order['total_amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <div class="dashboard-right-column">
                    <section class="dashboard-panel">
                        <div class="dashboard-panel-title">Low Stock Alerts</div>

                        <div class="dashboard-alert-list">
                            <?php if (!empty($lowStockItems)): ?>
                                <?php foreach ($lowStockItems as $item): ?>
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
                                    ?>
                                    <div class="dashboard-alert-item">
                                        <div class="dashboard-alert-thumb <?= $productClass ?>"></div>
                                        <div class="dashboard-alert-text">
                                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                            <?php if (!empty($item['expiry_date']) && $item['expiry_date'] <= date('Y-m-d', strtotime('+7 days'))): ?>
                                                <p>Expiring Soon</p>
                                            <?php else: ?>
                                                <p>Only <?= (int)$item['stock_qty'] ?> left in stock</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dashboard-panel-body">
                                    <p>No low stock alerts right now.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="dashboard-quick-actions">
                        <a href="/seller/products/create" class="button quick-action-btn">+ Add Product</a>
                        <a href="/seller/orders" class="button button-secondary quick-action-btn">View Orders</a>
                        <a href="/seller/inventory" class="button button-secondary quick-action-btn">Manage Inventory</a>
                    </div>
                </div>
            </div>



            <section class="dashboard-panel dashboard-full-width">
                <div class="dashboard-panel-title">Order Overview</div>

                
                <?php if (empty($ordersOverview)): ?>
                    <div class="dashboard-panel-body">
                        <p>No recent orders available.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="seller-orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php foreach ($ordersOverview as $order): ?>
                                    <?php


                                    $status = strtolower($order['status']);
                                    $badgeClass = 'badge-neutral';

                                    if (in_array($status, ['paid', 'delivered'], true)) {
                                        $badgeClass = 'badge-success';
                                    } elseif (in_array($status, ['processing', 'pending_payment'], true)) {
                                        $badgeClass = 'badge-warning';
                                    } elseif (in_array($status, ['cancelled', 'failed'], true)) {
                                        $badgeClass = 'badge-danger';
                                    } elseif (in_array($status, ['shipped'], true)) {
                                        $badgeClass = 'badge-neutral';
                                    }
 
                                    
                                    ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                        <td><?= htmlspecialchars($order['delivery_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))) ?>
                                            </span>
                                        </td>
                                        <td>£<?= number_format((float)$order['total_amount'], 2) ?></td>
                                        <td>
                                            <a href="/seller/order?id=<?= (int)$order['id'] ?>" class="button">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>


                    <?php if (($totalOrderPages ?? 1) > 1): ?>
                        <div class="seller-dashboard-pagination">
                            <?php if (($ordersPage ?? 1) > 1): ?>
                                <a href="/seller/dashboard?orders_page=<?= (int)$ordersPage - 1 ?>" class="seller-page-link">‹</a>
                            <?php endif; ?>

                            <?php for ($page = 1; $page <= $totalOrderPages; $page++): ?>
                                <a
                                    href="/seller/dashboard?orders_page=<?= $page ?>"
                                    class="seller-page-link <?= $page === (int)$ordersPage ? 'active' : '' ?>"
                                >
                                    <?= $page ?>
                                </a>
                            <?php endfor; ?>

                            <?php if (($ordersPage ?? 1) < ($totalOrderPages ?? 1)): ?>
                                <a href="/seller/dashboard?orders_page=<?= (int)$ordersPage + 1 ?>" class="seller-page-link">›</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>


                <?php endif; ?>
            </section>
        </div>
    </div>
</div>