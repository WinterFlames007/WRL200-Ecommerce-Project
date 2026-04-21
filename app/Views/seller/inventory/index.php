<?php
$today = date('Y-m-d');
$soonDate = date('Y-m-d', strtotime('+7 days'));
?>


<div class="seller-products-page">
    <div class="seller-shell">
        <aside class="seller-sidebar">
            <div class="seller-sidebar-brand">StoreName</div>

            <nav class="seller-sidebar-nav">
                <a href="/seller/dashboard">Dashboard</a>
                <a href="/seller/products">Products</a>
                <a href="/seller/orders">Orders</a>
                <a href="/seller/inventory" class="active">Inventory</a>
                <a href="/seller/export/orders">Reports</a>
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="seller-main">
            <div class="seller-topbar">
                <h1>Inventory Management</h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <div class="inventory-stat-grid">
                <div class="inventory-stat-card blue">
                    <div class="inventory-stat-number"><?= $totalProducts ?></div>
                    <div class="inventory-stat-label">Variants</div>
                </div>

                <div class="inventory-stat-card yellow">
                    <div class="inventory-stat-number"><?= $totalUnits ?></div>
                    <div class="inventory-stat-label">Units</div>
                </div>

                <a href="/seller/inventory?inventory_filter=expired" class="inventory-stat-card orange inventory-stat-link">
                    <div class="inventory-stat-number"><?= $totalExpired ?></div>
                    <div class="inventory-stat-label">Expired</div>
                </a>

                <a href="/seller/inventory?inventory_filter=out_of_stock" class="inventory-stat-card red inventory-stat-link">
                    <div class="inventory-stat-number"><?= $totalOutOfStock ?></div>
                    <div class="inventory-stat-label">Out Of Stock</div>
                </a>
            </div>

            <div class="dashboard-panel">
                <div class="inventory-header-row">
                    <div class="dashboard-panel-title">Inventory</div>

                    <div class="inventory-header-actions">
                        <a href="/seller/products" class="button">+ Adjust Stock</a>

                        <?php if (!empty($inventoryFilter)): ?>
                            <a href="/seller/inventory" class="button button-secondary">Clear Filter</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($inventoryItems)): ?>
                    <div class="dashboard-panel-body">
                        <p>No inventory records found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="seller-products-table inventory-table">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>SKU</th>
                                    <th>Expiry</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventoryItems as $item): ?>
                                    <?php
                                    $alert = 'OK';
                                    $badgeClass = 'badge-neutral';

                                    if ((int)$item['stock_qty'] <= 0) {
                                        $alert = 'Out of Stock';
                                        $badgeClass = 'badge-danger';
                                    } elseif ((int)$item['stock_qty'] <= 3) {
                                        $alert = 'Low Stock';
                                        $badgeClass = 'badge-warning';
                                    }

                                    if ((int)$item['is_food'] === 1 && !empty($item['expiry_date'])) {
                                        if ($item['expiry_date'] < $today) {
                                            $alert = 'Expired';
                                            $badgeClass = 'badge-danger';
                                        } elseif ($item['expiry_date'] <= $soonDate) {
                                            $alert = 'Expiring Soon';
                                            $badgeClass = 'badge-warning';
                                        } else {
                                            $badgeClass = 'badge-success';
                                        }
                                    }

                                    $expiryDisplay = 'N/A';
                                    if (!empty($item['expiry_date'])) {
                                        $expiryDisplay = $item['expiry_date'];
                                    }
                                    ?>
                                    <tr>
                                        <td>#PRD<?= htmlspecialchars($item['variant_id']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                            <div class="muted">
                                                <?= htmlspecialchars($item['size']) ?> / <?= htmlspecialchars($item['colour']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($item['category_name']) ?></td>
                                        <td><?= htmlspecialchars($item['stock_qty']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td class="inventory-expiry-cell">
                                            <?php if ($expiryDisplay !== 'N/A'): ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= htmlspecialchars($expiryDisplay) ?>
                                                </span>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>£<?= number_format((float)($item['price'] ?? 0), 2) ?></td>
                            

                                            <td>
                                                <div class="inventory-action-cell">
                                                    <a href="/seller/variants?product_id=<?= (int)$item['product_id'] ?>" class="button">Update</a>
                                                    <a href="/seller/variants/edit?id=<?= (int)$item['variant_id'] ?>" class="button button-secondary">Edit Variant</a>
                                                </div>
                                            </td>



                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="seller-table-footer">
                        <span>Showing <?= count($inventoryItems) ?> inventory item(s)</span>

                        <?php if (($totalPages ?? 1) > 1): ?>
                            <div class="fake-pagination">
                                <?php
                                $baseParams = [];
                                if (!empty($inventoryFilter)) {
                                    $baseParams['inventory_filter'] = $inventoryFilter;
                                }
                                ?>

                                <?php if (($page ?? 1) > 1): ?>
                                    <?php $prevParams = array_merge($baseParams, ['page' => $page - 1]); ?>
                                    <a href="/seller/inventory?<?= http_build_query($prevParams) ?>">‹</a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php $pageParams = array_merge($baseParams, ['page' => $i]); ?>
                                    <a href="/seller/inventory?<?= http_build_query($pageParams) ?>" class="<?= $i === (int)$page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if (($page ?? 1) < ($totalPages ?? 1)): ?>
                                    <?php $nextParams = array_merge($baseParams, ['page' => $page + 1]); ?>
                                    <a href="/seller/inventory?<?= http_build_query($nextParams) ?>">›</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>