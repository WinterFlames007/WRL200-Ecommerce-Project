<div class="seller-products-page">
    <div class="seller-shell">
        <aside class="seller-sidebar">
            <div class="seller-sidebar-brand">StoreName</div>

            <nav class="seller-sidebar-nav">
                <a href="/seller/dashboard">Dashboard</a>
                <a href="/seller/products">Products</a>
                <a href="/seller/orders" class="active">Orders</a>
                <a href="/seller/inventory">Inventory</a>
                <a href="/seller/export/orders">Reports</a>
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="seller-main">
            <div class="seller-topbar">
                <h1>Order Management</h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <form method="GET" action="/seller/orders" class="product-toolbar">
                <div class="product-toolbar-left">
                    <select name="category_id" class="toolbar-select">
                        <option value="">All Categories</option>
                        <?php foreach (($categories ?? []) as $category): ?>
                            <option value="<?= (int)$category['id'] ?>" <?= ((int)($selectedCategoryId ?? 0) === (int)$category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="toolbar-select">
                        <option value="">All Status</option>
                        <option value="pending_payment" <?= ($selectedStatus ?? '') === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                        <option value="paid" <?= ($selectedStatus ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="processing" <?= ($selectedStatus ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= ($selectedStatus ?? '') === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= ($selectedStatus ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= ($selectedStatus ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>

                    <input
                        type="date"
                        name="date_from"
                        class="toolbar-input"
                        value="<?= htmlspecialchars($selectedDateFrom ?? '') ?>"
                    >

                    <input
                        type="date"
                        name="date_to"
                        class="toolbar-input"
                        value="<?= htmlspecialchars($selectedDateTo ?? '') ?>"
                    >
                </div>

                <div class="product-toolbar-left" style="justify-content: flex-end;">
                    <button type="submit" class="button product-add-btn">Apply Filters</button>

                    <?php if (
                        !empty($selectedCategoryId) ||
                        !empty($selectedStatus) ||
                        !empty($selectedDateFrom) ||
                        !empty($selectedDateTo)
                    ): ?>
                        <a href="/seller/orders" class="button button-secondary">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="dashboard-panel">
                <div class="dashboard-panel-title">Current Orders</div>

                <?php if (empty($orders)): ?>
                    <div class="dashboard-panel-body">
                        <p>No orders found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="seller-orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    $status = strtolower($order['status']);
                                    $badgeClass = 'badge-neutral';

                                    if (in_array($status, ['paid', 'delivered'], true)) {
                                        $badgeClass = 'badge-success';
                                    } elseif (in_array($status, ['processing', 'pending_payment'], true)) {
                                        $badgeClass = 'badge-warning';
                                    } elseif ($status === 'cancelled') {
                                        $badgeClass = 'badge-danger';
                                    } elseif ($status === 'shipped') {
                                        $badgeClass = 'badge-neutral';
                                    }
                                    ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                        <td><?= htmlspecialchars($order['delivery_name']) ?></td>
                                        <td><?= htmlspecialchars(date('d-m-Y', strtotime($order['created_at']))) ?></td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))) ?>
                                            </span>
                                        </td>
                                        <td>£<?= number_format((float)$order['total_amount'], 2) ?></td>
                                        <td>
                                            <a href="/seller/order?id=<?= (int)$order['id'] ?>" class="button">View / Update</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="seller-table-footer">
                        <span>Showing <?= count($orders) ?> order(s)</span>

                        <?php if (($totalPages ?? 1) > 1): ?>
                            <div class="fake-pagination">
                                <?php
                                $baseParams = [];
                                if (!empty($selectedCategoryId)) $baseParams['category_id'] = $selectedCategoryId;
                                if (!empty($selectedStatus)) $baseParams['status'] = $selectedStatus;
                                if (!empty($selectedDateFrom)) $baseParams['date_from'] = $selectedDateFrom;
                                if (!empty($selectedDateTo)) $baseParams['date_to'] = $selectedDateTo;
                                ?>

                                <?php if (($page ?? 1) > 1): ?>
                                    <?php $prevParams = array_merge($baseParams, ['page' => $page - 1]); ?>
                                    <a href="/seller/orders?<?= http_build_query($prevParams) ?>">‹</a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php $pageParams = array_merge($baseParams, ['page' => $i]); ?>
                                    <a
                                        href="/seller/orders?<?= http_build_query($pageParams) ?>"
                                        class="<?= $i === (int)$page ? 'active' : '' ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if (($page ?? 1) < ($totalPages ?? 1)): ?>
                                    <?php $nextParams = array_merge($baseParams, ['page' => $page + 1]); ?>
                                    <a href="/seller/orders?<?= http_build_query($nextParams) ?>">›</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>