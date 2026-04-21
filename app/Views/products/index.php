<?php
$queryBase = [
    'sort' => $sort ?? 'latest'
];

if (!empty($categoryId)) {
    $queryBase['category_id'] = (int) $categoryId;
}

if ($minPrice !== null) {
    $queryBase['min_price'] = $minPrice;
}

if ($maxPrice !== null) {
    $queryBase['max_price'] = $maxPrice;
}

if (!empty($inStock)) {
    $queryBase['in_stock'] = '1';
}

if (!empty($selectedSizes)) {
    $queryBase['sizes'] = $selectedSizes;
}

if (!empty($selectedColours)) {
    $queryBase['colours'] = $selectedColours;
}

$hasActiveFilters =
    !empty($categoryId) ||
    $minPrice !== null ||
    $maxPrice !== null ||
    !empty($inStock) ||
    !empty($selectedSizes) ||
    !empty($selectedColours);
?>



<div class="listing-page">
    <div class="listing-layout">
        <aside class="filters-sidebar">
            <div class="filters-box">
                <h2>Filters</h2>

                <form method="GET" action="/shop">
                    <?php if (!empty($categoryId)): ?>
                        <input type="hidden" name="category_id" value="<?= (int)$categoryId ?>">
                    <?php endif; ?>

                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort ?? 'latest') ?>">

                    <div class="filter-group">
                        <h3>Price</h3>

                        <label for="min_price">Minimum Price</label>
                        <input type="number" step="0.01" min="0" id="min_price" name="min_price" value="<?= htmlspecialchars((string)($minPrice ?? '')) ?>">

                        <label for="max_price">Maximum Price</label>
                        <input type="number" step="0.01" min="0" id="max_price" name="max_price" value="<?= htmlspecialchars((string)($maxPrice ?? '')) ?>">
                    </div>


                    <div class="filter-group">
                        <h3>Size</h3>

                        <?php if (!empty($availableSizes)): ?>
                            <?php foreach ($availableSizes as $sizeRow): ?>
                                <?php $sizeValue = (string) ($sizeRow['size'] ?? ''); ?>
                                <label class="check-row">
                                    <input
                                        type="checkbox"
                                        name="sizes[]"
                                        value="<?= htmlspecialchars($sizeValue) ?>"
                                        <?= in_array($sizeValue, $selectedSizes ?? [], true) ? 'checked' : '' ?>
                                    >
                                    <?= htmlspecialchars($sizeValue) ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="muted">No sizes available</div>
                        <?php endif; ?>
                    </div>


                    <!-- <div class="filter-group">
                        <h3>Color</h3>
                        <div class="muted">Visual only for now</div>
                        <div class="color-row"><span class="color-dot black"></span> Black</div>
                        <div class="color-row"><span class="color-dot blue"></span> Blue</div>
                        <div class="color-row"><span class="color-dot green"></span> Green</div>
                        <div class="color-row"><span class="color-dot red"></span> Red</div>
                    </div>
 -->

                    <div class="filter-group">
                        <h3>Color</h3>

                        <?php if (!empty($availableColours)): ?>
                            <?php foreach ($availableColours as $colourRow): ?>
                                <?php
                                $colourValue = (string) ($colourRow['colour'] ?? '');
                                $colourClass = 'neutral';
                                $colourName = strtolower(trim($colourValue));

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
                                <label class="check-row colour-check-row">
                                    <input
                                        type="checkbox"
                                        name="colours[]"
                                        value="<?= htmlspecialchars($colourValue) ?>"
                                        <?= in_array($colourValue, $selectedColours ?? [], true) ? 'checked' : '' ?>
                                    >
                                    <span class="color-dot <?= $colourClass ?>"></span>
                                    <span><?= htmlspecialchars($colourValue) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="muted">No colours available</div>
                        <?php endif; ?>
                    </div>



                    <div class="filter-group">
                        <h3>Availability</h3>
                        <label class="check-row">
                            <input type="checkbox" name="in_stock" value="1" <?= !empty($inStock) ? 'checked' : '' ?>>
                            In Stock
                        </label>
                    </div>


                    <button type="submit" class="button filters-button">Apply Filters</button>

                    <?php if ($hasActiveFilters): ?>
                        <a href="/shop<?= !empty($categoryId) ? '?category_id=' . (int)$categoryId : '' ?>" class="button button-secondary filters-button secondary">
                            Clear Filters
                        </a>
                    <?php endif; ?>


                </form>
            </div>
        </aside>

        <section class="listing-content">
            <div class="listing-topbar">
                <h1><?= htmlspecialchars($pageTitle ?? 'Products') ?></h1>

                <form method="GET" action="/shop" class="sort-form">
                    <?php if (!empty($categoryId)): ?>
                        <input type="hidden" name="category_id" value="<?= (int)$categoryId ?>">
                    <?php endif; ?>
                    <?php if ($minPrice !== null): ?>
                        <input type="hidden" name="min_price" value="<?= htmlspecialchars((string)$minPrice) ?>">
                    <?php endif; ?>
                    <?php if ($maxPrice !== null): ?>
                        <input type="hidden" name="max_price" value="<?= htmlspecialchars((string)$maxPrice) ?>">
                    <?php endif; ?>



                    <?php if (!empty($selectedSizes)): ?>
                        <?php foreach ($selectedSizes as $size): ?>
                            <input type="hidden" name="sizes[]" value="<?= htmlspecialchars($size) ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($selectedColours)): ?>
                        <?php foreach ($selectedColours as $colour): ?>
                            <input type="hidden" name="colours[]" value="<?= htmlspecialchars($colour) ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>



                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="latest" <?= ($sort ?? '') === 'latest' ? 'selected' : '' ?>>Latest</option>
                        <option value="price_low_high" <?= ($sort ?? '') === 'price_low_high' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="price_high_low" <?= ($sort ?? '') === 'price_high_low' ? 'selected' : '' ?>>Price (High to Low)</option>
                        <option value="name_az" <?= ($sort ?? '') === 'name_az' ? 'selected' : '' ?>>Name (A–Z)</option>
                        <option value="name_za" <?= ($sort ?? '') === 'name_za' ? 'selected' : '' ?>>Name (Z–A)</option>
                    </select>
                </form>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <p>No products available.</p>
                </div>
            <?php else: ?>
                <div class="listing-grid">
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
                        ?>
                        <div class="listing-card">
                            <a href="/product?id=<?= (int)$product['id'] ?>" class="listing-card-image-link">
                                <?php if (!empty($product['image_path'])): ?>
                                    <div class="listing-card-image real-image" style="background-image: url('<?= htmlspecialchars($product['image_path']) ?>');"></div>
                                <?php else: ?>
                                    <div class="listing-card-image <?= $productClass ?>"></div>
                                <?php endif; ?>
                            </a>

                            <div class="listing-card-body">
                                <h3>
                                    <a href="/product?id=<?= (int)$product['id'] ?>" class="listing-title-link">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h3>

                                <p class="listing-price">£<?= number_format((float)($product['display_price'] ?? 0), 2) ?></p>

                                <div class="listing-card-actions">
                                    <a href="/product?id=<?= (int)$product['id'] ?>" class="button">Add to Cart</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (($totalPages ?? 1) > 1): ?>
                    <div class="fake-pagination">
                        <?php
                        $prevPage = max(1, (int)$page - 1);
                        $nextPage = min((int)$totalPages, (int)$page + 1);
                        ?>

                        <?php $prevQuery = http_build_query(array_merge($queryBase, ['page' => $prevPage])); ?>
                        <?php $nextQuery = http_build_query(array_merge($queryBase, ['page' => $nextPage])); ?>

                        <a href="/shop?<?= $prevQuery ?>">‹</a>

                        <?php for ($i = 1; $i <= (int)$totalPages; $i++): ?>
                            <?php $pageQuery = http_build_query(array_merge($queryBase, ['page' => $i])); ?>
                            <a href="/shop?<?= $pageQuery ?>" class="<?= (int)$page === $i ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <a href="/shop?<?= $nextQuery ?>">›</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>