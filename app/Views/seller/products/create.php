<?php
$isEdit = !empty($isEdit);
$product = $product ?? [];
?>

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
                <h1><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <div class="inline-links">
                <a href="/seller/products" class="button button-secondary">Back to Products</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Please fix the following issues:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= $isEdit ? '/seller/products/edit' : '/seller/products/create' ?>" enctype="multipart/form-data">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                <?php endif; ?>

                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($product['name'] ?? '') ?>">

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

                <label for="delivery_info">Delivery Information</label>
                <textarea id="delivery_info" name="delivery_info" rows="4" placeholder="Example: Delivery within 2-4 working days. Tracking provided after dispatch."><?= htmlspecialchars($product['delivery_info'] ?? 'Delivery within 2-4 working days. Tracking provided after dispatch.') ?></textarea>

                <label for="return_policy">Return Policy</label>
                <textarea id="return_policy" name="return_policy" rows="4" placeholder="Example: Returns accepted within 14 days if unused and in original packaging."><?= htmlspecialchars($product['return_policy'] ?? 'Returns accepted within 14 days if unused and in original packaging.') ?></textarea>

                <div class="form-row">
                    <div>
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?= (int)$category['id'] ?>"
                                    <?= ((int)($product['category_id'] ?? 0) === (int)$category['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="base_price">Base Price</label>
                        <input type="number" step="0.01" id="base_price" name="base_price" value="<?= htmlspecialchars($product['base_price'] ?? '') ?>">
                    </div>
                </div>

                <?php if (!empty($product['image_path'])): ?>
                    <div class="existing-image-preview">
                        <label>Current Main Image</label>
                        <div class="seller-product-thumb real-image" style="background-image: url('<?= htmlspecialchars($product['image_path']) ?>');"></div>
                    </div>
                <?php endif; ?>

                <label for="product_image">Product Images</label>
                <input type="file" id="product_image" name="product_image" accept=".jpg,.jpeg,.png,.webp">

                <label class="check-row">
                    <input type="checkbox" name="is_food" value="1" <?= !empty($product['is_food']) ? 'checked' : '' ?>>
                    This is a food product
                </label>

                <div class="form-actions">
                    <button type="submit"><?= $isEdit ? 'Update Product' : 'Save Product' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>