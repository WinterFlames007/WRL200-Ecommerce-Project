<div class="seller-products-page">
    <div class="seller-shell">
        <aside class="seller-sidebar">
            <div class="seller-sidebar-brand">StoreName</div>

            <nav class="seller-sidebar-nav">
                <a href="/seller/dashboard">Dashboard</a>
                <a href="/seller/products" class="active">Products</a>
                <a href="/seller/orders">Orders</a>
                <a href="/seller/inventory">Inventory</a>
                <a href="/seller/export/orders">Reports</a>
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="seller-main">
            <div class="seller-topbar">
                <h1>Variants for: <?= htmlspecialchars($product['name']) ?></h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <div class="inline-links">
                <a href="/seller/products" class="button button-secondary">Back to Products</a>
                <a href="/seller/variants/create?product_id=<?= (int)$product['id'] ?>" class="button">Add Variant</a>
                <a href="/seller/products/edit?id=<?= (int)$product['id'] ?>" class="button button-secondary">Edit Product</a>
            </div>

            <div class="card-soft">
                <p><strong>Category:</strong> <?= htmlspecialchars($product['category_name']) ?></p>
                <p><strong>Food Product:</strong> <?= (int)$product['is_food'] === 1 ? 'Yes' : 'No' ?></p>
            </div>

            <?php if (empty($variants)): ?>
                <div class="empty-state">
                    <p>No variants found for this product.</p>
                </div>
            <?php else: ?>
                <div class="dashboard-panel">
                    <div class="table-wrap">
                        <table class="seller-products-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Size</th>
                                    <th>Colour</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($variants as $variant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($variant['id']) ?></td>
                                        <td>
                                            <?php if (!empty($variant['image_path'])): ?>
                                                <div class="variant-thumb" style="background-image: url('<?= htmlspecialchars($variant['image_path']) ?>');"></div>
                                            <?php else: ?>
                                                <div class="variant-thumb variant-thumb-placeholder"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($variant['size']) ?></td>
                                        <td><?= htmlspecialchars($variant['colour']) ?></td>
                                        <td><?= htmlspecialchars($variant['sku']) ?></td>
                                        <td>£<?= number_format((float)($variant['price'] ?? 0), 2) ?></td>
                                        <td><?= htmlspecialchars($variant['stock_qty']) ?></td>
                                        <td><?= !empty($variant['expiry_date']) ? htmlspecialchars($variant['expiry_date']) : 'N/A' ?></td>
                                        <td>
                                            <span class="badge <?= $variant['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>">
                                                <?= htmlspecialchars($variant['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="seller-action-group">
                                                <a href="/seller/variants/edit?id=<?= (int)$variant['id'] ?>" class="button button-secondary">Edit</a>
                                                <a href="/seller/variants/delete?id=<?= (int)$variant['id'] ?>&product_id=<?= (int)$product['id'] ?>" class="button button-danger" data-confirm="Delete this variant?">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>