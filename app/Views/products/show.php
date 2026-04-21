<?php
$requestedVariantId = (int) ($_GET['variant_id'] ?? 0);

$selectedVariant = null;

if ($requestedVariantId > 0) {
    foreach ($variants as $variantCheck) {
        if ((int)$variantCheck['id'] === $requestedVariantId) {
            $selectedVariant = $variantCheck;
            break;
        }
    }
}

if (!$selectedVariant) {
    foreach ($variants as $variantCheck) {
        if ((int)$variantCheck['stock_qty'] > 0) {
            $selectedVariant = $variantCheck;
            break;
        }
    }
}

if (!$selectedVariant && !empty($variants)) {
    $selectedVariant = $variants[0];
}

$displayPrice = $selectedVariant && $selectedVariant['price'] !== null
    ? (float)$selectedVariant['price']
    : (float)($product['base_price'] ?? 0);

$inStock = $selectedVariant && (int)$selectedVariant['stock_qty'] > 0;

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

$sizes = [];
$colours = [];

foreach ($variants as $variant) {
    if (!in_array($variant['size'], $sizes, true)) {
        $sizes[] = $variant['size'];
    }
    if (!in_array($variant['colour'], $colours, true)) {
        $colours[] = $variant['colour'];
    }
}



$productGalleryImages = [];

if (!empty($product['image_path'])) {
    $productGalleryImages[] = $product['image_path'];
}

if (!empty($galleryImages)) {
    foreach ($galleryImages as $galleryImage) {
        if (!empty($galleryImage['image_path'])) {
            $productGalleryImages[] = $galleryImage['image_path'];
        }
    }
}

$productGalleryImages = array_values(array_unique($productGalleryImages));

$selectedVariantImages = [];

if (!empty($selectedVariant)) {
    $selectedVariantId = (int) $selectedVariant['id'];

    if (!empty($variantImagesMap[$selectedVariantId])) {
        $selectedVariantImages = $variantImagesMap[$selectedVariantId];
    } elseif (!empty($selectedVariant['image_path'])) {
        $selectedVariantImages[] = $selectedVariant['image_path'];
    }
}

$thumbImages = !empty($selectedVariantImages) ? $selectedVariantImages : $productGalleryImages;

$mainImage = !empty($thumbImages[0]) ? $thumbImages[0] : '';



$variantsForJs = [];
foreach ($variants as $variant) {
    $variantPrice = $variant['price'] !== null
        ? (float)$variant['price']
        : (float)($product['base_price'] ?? 0);





    $variantId = (int) $variant['id'];

    $variantImageList = [];
    if (!empty($variantImagesMap[$variantId])) {
        $variantImageList = $variantImagesMap[$variantId];
    } elseif (!empty($variant['image_path'])) {
        $variantImageList[] = $variant['image_path'];
    }

    $variantsForJs[] = [
        'id' => $variantId,
        'size' => (string)($variant['size'] ?? ''),
        'colour' => (string)($variant['colour'] ?? ''),
        'price' => $variantPrice,
        'stock_qty' => (int)$variant['stock_qty'],
        'status' => (string)($variant['status'] ?? 'inactive'),
        'image_path' => (string)($variant['image_path'] ?? ''),
        'images' => $variantImageList
    ];



}
?>

<div class="product-detail-page">
    <div class="product-detail-top">
        <div class="product-gallery">
            <?php if (!empty($mainImage)): ?>
                <div
                    class="product-main-image real-image"
                    id="product-main-image"
                    data-default-image="<?= htmlspecialchars($mainImage) ?>"
                    style="background-image: url('<?= htmlspecialchars($mainImage) ?>');"
                ></div>
            <?php else: ?>
                <div
                    class="product-main-image <?= $productClass ?>"
                    id="product-main-image"
                    data-default-image=""
                ></div>
            <?php endif; ?>

            <?php if (!empty($thumbImages)): ?>

                
                <div class="product-thumbs" id="product-thumbs">
                    <?php foreach ($thumbImages as $index => $thumb): ?>
                        <?php $isActiveThumb = ($thumb === $mainImage); ?>
                        <button
                            type="button"
                            class="product-thumb<?= $isActiveThumb ? ' active' : '' ?>"
                            data-product-thumb
                            data-image="<?= htmlspecialchars($thumb) ?>"
                            style="background-image: url('<?= htmlspecialchars($thumb) ?>');"
                            aria-label="Product image <?= $index + 1 ?>"
                        ></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-summary">
            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <div class="product-detail-price" id="product-detail-price">
                £<?= number_format($displayPrice, 2) ?>
            </div>

            <div class="stock-status-row">
                <?php if ($inStock): ?>
                    <span class="badge badge-success" id="product-stock-badge">In stock</span>
                <?php else: ?>
                    <span class="badge badge-danger" id="product-stock-badge">Out of stock</span>
                <?php endif; ?>
            </div>

            <p class="product-detail-description">
                <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
            </p>

            <?php if (!empty($sizes)): ?>
                <div class="detail-option-group">
                    <h3>Size</h3>
                    <div class="detail-size-options">
                        <?php foreach ($sizes as $size): ?>
                            <button
                                type="button"
                                class="size-pill<?= $selectedVariant && $selectedVariant['size'] === $size ? ' active' : '' ?>"
                                data-variant-size="<?= htmlspecialchars($size) ?>"
                            >
                                <?= htmlspecialchars($size) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($colours)): ?>
                <div class="detail-option-group">
                    <h3>Color</h3>
                    <div class="detail-colour-options">
                        <?php foreach ($colours as $colour): ?>
                            <?php
                            $colourClass = 'neutral';
                            $colourName = strtolower(trim($colour));

                            if (str_contains($colourName, 'black')) {
                                $colourClass = 'black';
                            } elseif (str_contains($colourName, 'blue')) {
                                $colourClass = 'blue';
                            } elseif (str_contains($colourName, 'green')) {
                                $colourClass = 'green';
                            } elseif (str_contains($colourName, 'red')) {
                                $colourClass = 'red';
                            } elseif (str_contains($colourName, 'white')) {
                                $colourClass = 'white';
                            }
                            ?>
                            <button
                                type="button"
                                class="colour-pill <?= $colourClass ?><?= $selectedVariant && $selectedVariant['colour'] === $colour ? ' active' : '' ?>"
                                data-variant-colour="<?= htmlspecialchars($colour) ?>"
                                title="<?= htmlspecialchars($colour) ?>"
                                aria-label="<?= htmlspecialchars($colour) ?>"
                            ></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($variants) && $selectedVariant): ?>
                <div
                    class="product-buy-box"
                    id="product-variant-box"
                    data-variants='<?= htmlspecialchars(json_encode($variantsForJs), ENT_QUOTES, 'UTF-8') ?>'
                    data-default-size="<?= htmlspecialchars((string)$selectedVariant['size']) ?>"
                    data-default-colour="<?= htmlspecialchars((string)$selectedVariant['colour']) ?>"
                    data-default-variant-id="<?= (int)$selectedVariant['id'] ?>"
                >
                    <form method="POST" action="/cart/add" class="product-buy-form">
                        <input type="hidden" name="variant_id" id="selected-variant-id" value="<?= (int)$selectedVariant['id'] ?>">

                        <div class="quantity-box">
                            <button type="button" class="qty-btn" data-qty-action="decrease">−</button>
                            <input
                                type="number"
                                name="quantity"
                                id="selected-variant-quantity"
                                class="qty-input"
                                value="1"
                                min="1"
                                max="<?= (int)$selectedVariant['stock_qty'] > 0 ? (int)$selectedVariant['stock_qty'] : 1 ?>"
                            >
                            <button type="button" class="qty-btn" data-qty-action="increase">+</button>
                        </div>

                        <?php if ((int)$selectedVariant['stock_qty'] > 0): ?>
                            <button type="submit" class="button product-buy-button" id="product-buy-button">Add to Cart</button>
                        <?php else: ?>
                            <button type="button" class="button button-danger product-buy-button" id="product-buy-button" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="product-tabs-section">
        <div class="product-tabs">
            <button type="button" class="product-tab active" data-tab-target="description">Description</button>
            <button type="button" class="product-tab" data-tab-target="delivery">Delivery Information</button>
            <button type="button" class="product-tab" data-tab-target="returns">Return Policy</button>
        </div>

        <div class="product-tab-panels">
            <div class="product-tab-content active" data-tab-panel="description">
                <p><?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?></p>
            </div>

            <div class="product-tab-content" data-tab-panel="delivery">
                <p><?= nl2br(htmlspecialchars($product['delivery_info'] ?? 'Delivery information will be provided at checkout.')) ?></p>
            </div>

            <div class="product-tab-content" data-tab-panel="returns">
                <p><?= nl2br(htmlspecialchars($product['return_policy'] ?? 'Returns are subject to store policy and product condition.')) ?></p>
            </div>
        </div>
    </div>

    <div class="related-products-section">
        <h2>Related Products</h2>

        <?php if (empty($relatedProducts)): ?>
            <div class="empty-state">
                <p>No related products available.</p>
            </div>
        <?php else: ?>
            <div class="related-grid">
                <?php foreach ($relatedProducts as $related): ?>
                    <?php
                    $relatedClass = 'product-image-default';
                    $relatedName = strtolower($related['name']);

                    if (str_contains($relatedName, 'green')) {
                        $relatedClass = 'product-image-green';
                    } elseif (str_contains($relatedName, 'hair')) {
                        $relatedClass = 'product-image-hair';
                    } elseif (str_contains($relatedName, 'chip')) {
                        $relatedClass = 'product-image-chips';
                    } elseif (str_contains($relatedName, 'watch')) {
                        $relatedClass = 'product-image-watch';
                    } elseif (str_contains($relatedName, 'black')) {
                        $relatedClass = 'product-image-dark';
                    } elseif (str_contains($relatedName, 'shoe') || str_contains($relatedName, 'sneaker')) {
                        $relatedClass = 'product-image-light';
                    }
                    ?>
                    <div class="related-card">
                        <a href="/product?id=<?= (int)$related['id'] ?>" class="related-card-image-link">
                            <?php if (!empty($related['image_path'])): ?>
                                <div class="related-card-image real-image" style="background-image: url('<?= htmlspecialchars($related['image_path']) ?>');"></div>
                            <?php else: ?>
                                <div class="related-card-image <?= $relatedClass ?>"></div>
                            <?php endif; ?>
                        </a>

                        <div class="related-card-body">
                            <h3>
                                <a href="/product?id=<?= (int)$related['id'] ?>" class="listing-title-link">
                                    <?= htmlspecialchars($related['name']) ?>
                                </a>
                            </h3>
                            <p class="related-price">£<?= number_format((float)$related['base_price'], 2) ?></p>
                            <a href="/product?id=<?= (int)$related['id'] ?>" class="button">Add to Cart</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>