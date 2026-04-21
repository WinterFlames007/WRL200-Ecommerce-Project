<?php
$isEdit = !empty($isEdit);
$variant = $variant ?? [];
$variantImages = $variantImages ?? [];
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
                <h1><?= $isEdit ? 'Edit Variant for: ' : 'Add Variant for: ' ?><?= htmlspecialchars($product['name']) ?></h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <div class="inline-links">
                <a href="/seller/variants?product_id=<?= (int)$product['id'] ?>" class="button button-secondary">Back to Variants</a>
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

            <form method="POST" action="<?= $isEdit ? '/seller/variants/edit' : '/seller/variants/create' ?>" enctype="multipart/form-data">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$variant['id'] ?>">
                <?php endif; ?>

                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                <div class="form-row">
                    <div>
                        <label for="size">Size</label>
                        <input type="text" id="size" name="size" placeholder="e.g. S, M, L, XL" value="<?= htmlspecialchars($variant['size'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="colour">Colour</label>
                        <input type="text" id="colour" name="colour" placeholder="e.g. Black, Red, Honey, Wine" value="<?= htmlspecialchars($variant['colour'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label for="sku">SKU</label>
                        <input type="text" id="sku" name="sku" required value="<?= htmlspecialchars($variant['sku'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="price">Variant Price</label>
                        <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($variant['price'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label for="stock_qty">Stock Quantity</label>
                        <input type="number" id="stock_qty" name="stock_qty" min="0" value="<?= htmlspecialchars($variant['stock_qty'] ?? 0) ?>" required>
                    </div>

                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?= (($variant['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($variant['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($variant['image_path'])): ?>
                    <div class="existing-image-preview">
                        <label>Current Variant Image</label>
                        <div class="variant-thumb" style="background-image: url('<?= htmlspecialchars($variant['image_path']) ?>'); width:80px; height:80px;"></div>
                    </div>
                <?php endif; ?>




                <?php if ($isEdit && !empty($variantImages)): ?>
                    <div class="existing-image-preview">
                        <label>Current Variant Gallery Images</label>

                        <div class="variant-gallery-manage">
                            <?php foreach ($variantImages as $galleryImage): ?>
                                <div class="variant-gallery-manage-item">
                                    <div
                                        class="variant-thumb"
                                        style="background-image: url('<?= htmlspecialchars($galleryImage['image_path']) ?>'); width:80px; height:80px;"
                                    ></div>

                                    <a
                                        href="/seller/variant-images/delete?id=<?= (int)$galleryImage['id'] ?>&variant_id=<?= (int)$variant['id'] ?>"
                                        class="button button-danger"
                                        data-confirm="Delete this gallery image?"
                                    >
                                        Delete
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>




                <label for="variant_image">Main Variant Image</label>
                <input type="file" id="variant_image" name="variant_image" accept=".jpg,.jpeg,.png,.webp">


                <label for="variant_gallery_images">Additional Variant Images</label>
                <input type="file" id="variant_gallery_images" name="variant_gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple>


                <?php if ((int)$product['is_food'] === 1): ?>
                    <label for="expiry_date">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?= htmlspecialchars($variant['expiry_date'] ?? '') ?>">
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit"><?= $isEdit ? 'Update Variant' : 'Save Variant' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>