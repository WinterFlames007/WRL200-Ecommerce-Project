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
                <h1>Product Management</h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <?php
            $hasActiveFilters = !empty($search) || !empty($selectedCategoryId);
            ?>


            <form method="GET" action="/seller/products" class="product-toolbar product-toolbar-form">
                <div class="product-toolbar-left">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search product"
                        class="toolbar-input"
                        value="<?= htmlspecialchars($search ?? '') ?>"
                    >

                    <select name="category_id" class="toolbar-select">
                        <option value="0">All categories</option>
                        <?php foreach (($categories ?? []) as $category): ?>
                            <option
                                value="<?= (int)$category['id'] ?>"
                                <?= ((int)($selectedCategoryId ?? 0) === (int)$category['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="button button-secondary">Filter</button>

                    <?php if ($hasActiveFilters): ?>
                        
                        <a href="/seller/products" class="button">Clear Filters</a>
                    <?php endif; ?>



                </div>

                <a href="/seller/products/create" class="button product-add-btn">+ Add Product</a>
            </form>






            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <p>No products found.</p>
                </div>
            <?php else: ?>
                <div class="dashboard-panel">
                    <div class="table-wrap">
                        <table class="seller-products-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Expiry</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <?php
                                    $productClass = 'product-image-default';
                                    $name = strtolower($product['name']);

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

                                    $expiryText = 'N/A';
                                    if ((int)$product['is_food'] === 1) {
                                        $expiryText = !empty($product['nearest_expiry']) ? $product['nearest_expiry'] : 'No expiry set';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['image_path'])): ?>
                                                <div class="seller-product-thumb real-image" style="background-image: url('<?= htmlspecialchars($product['image_path']) ?>');"></div>
                                            <?php else: ?>
                                                <div class="seller-product-thumb <?= $productClass ?>"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                            <span class="muted">
                                                <?= (int)$product['is_food'] === 1 ? 'Food product' : 'Standard product' ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($product['category_name']) ?></td>
                                        <td><?= (int)($product['total_stock'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars($expiryText) ?></td>
                                        <td>£<?= number_format((float)($product['base_price'] ?? 0), 2) ?></td>
                                        <td>
                                            <div class="seller-action-group">
                                                <a href="/seller/variants?product_id=<?= (int)$product['id'] ?>" class="button">Variants</a>
                                                <a href="/seller/products/edit?id=<?= (int)$product['id'] ?>" class="button button-secondary">Edit</a>
                                                <a href="/seller/products/delete?id=<?= (int)$product['id'] ?>" class="button button-danger" data-confirm="Delete this product?">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="seller-table-footer">
                        <span>Showing <?= count($products) ?> of <?= (int)($totalProductsCount ?? count($products)) ?> product(s)</span>

                        <?php if (($totalPages ?? 1) > 1): ?>
                            <div class="seller-dashboard-pagination">
                                <?php
                                $queryBase = [];
                                if (!empty($search)) {
                                    $queryBase['search'] = $search;
                                }
                                if (!empty($selectedCategoryId)) {
                                    $queryBase['category_id'] = (int)$selectedCategoryId;
                                }
                                ?>

                                <?php if (($page ?? 1) > 1): ?>
                                    <?php $prevQuery = http_build_query(array_merge($queryBase, ['page' => $page - 1])); ?>
                                    <a href="/seller/products?<?= $prevQuery ?>" class="seller-page-link">‹</a>
                                <?php endif; ?>

                                <?php for ($p = 1; $p <= ($totalPages ?? 1); $p++): ?>
                                    <?php $pageQuery = http_build_query(array_merge($queryBase, ['page' => $p])); ?>
                                    <a href="/seller/products?<?= $pageQuery ?>" class="seller-page-link <?= $p === (int)$page ? 'active' : '' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if (($page ?? 1) < ($totalPages ?? 1)): ?>
                                    <?php $nextQuery = http_build_query(array_merge($queryBase, ['page' => $page + 1])); ?>
                                    <a href="/seller/products?<?= $nextQuery ?>" class="seller-page-link">›</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>