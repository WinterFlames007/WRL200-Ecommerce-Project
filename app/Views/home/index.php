<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>




<div class="home-page">
    <section class="hero-banner">
        <div class="hero-overlay">
            <h1>Shop Quality Clothing, Hair, Food &amp; Accessories</h1>
            <a href="/shop" class="button hero-button">Shop Now</a>
        </div>
    </section>

    <section class="home-section">
        <div class="section-title-row">
            <span class="section-line"></span>
            <h2>Shop by Category</h2>
            <span class="section-line"></span>
        </div>

        <div class="category-grid">
            <?php
            $categoryStyles = [
                'category-soft-pink',
                'category-soft-blue',
                'category-soft-lilac',
                'category-soft-purple'
            ];

            $categoryIcons = [
                'clothing' => '👕',
                'hair' => '🧴',
                'food' => '🥘',
                'accessories' => '👜'
            ];
            ?>

            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $index => $category): ?>
                    <?php
                    $styleClass = $categoryStyles[$index % count($categoryStyles)];
                    $categoryNameLower = strtolower($category['name']);
                    $icon = '📦';

                    foreach ($categoryIcons as $key => $value) {
                        if (str_contains($categoryNameLower, $key)) {
                            $icon = $value;
                            break;
                        }
                    }
                    ?>
                    <a href="/shop?category_id=<?= (int)$category['id'] ?>" class="category-card <?= $styleClass ?>">
                        <div class="category-icon"><?= $icon ?></div>
                        <div class="category-name"><?= htmlspecialchars($category['name']) ?></div>
                        <div class="category-count"><?= (int)$category['product_count'] ?> item(s)</div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <a href="/shop" class="category-card category-soft-pink">
                    <div class="category-icon">👕</div>
                    <div class="category-name">Clothing</div>
                </a>

                <a href="/shop" class="category-card category-soft-blue">
                    <div class="category-icon">🧴</div>
                    <div class="category-name">Hair Product</div>
                </a>

                <a href="/shop" class="category-card category-soft-lilac">
                    <div class="category-icon">🥘</div>
                    <div class="category-name">Food Product</div>
                </a>

                <a href="/shop" class="category-card category-soft-purple">
                    <div class="category-icon">👜</div>
                    <div class="category-name">Accessories</div>
                </a>
            <?php endif; ?>
        </div>
    </section>




    <section class="home-section">
        <div class="section-title-row">
            <span class="section-line"></span>
            <h2>Featured Product</h2>
            <span class="section-line"></span>
        </div>

        <div class="featured-grid">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
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

                    $productUrl = '/product?id=' . (int)$product['id'];
                    ?>

                    <div class="product-card home-feature-card">
                        <a href="<?= htmlspecialchars($productUrl) ?>" class="listing-card-image-link">
                            <?php if (!empty($product['image_path'])): ?>
                                <div class="product-image-wrap real-image" style="background-image: url('<?= htmlspecialchars($product['image_path']) ?>');"></div>
                            <?php else: ?>
                                <div class="product-image-wrap <?= $productClass ?>"></div>
                            <?php endif; ?>
                        </a>

                        <div class="product-card-body">
                            <h3>
                                <a href="<?= htmlspecialchars($productUrl) ?>" class="listing-title-link">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h3>

                            <p class="product-price">£<?= number_format((float)($product['display_price'] ?? 0), 2) ?></p>

                            <div class="home-product-actions">
                                <a href="<?= htmlspecialchars($productUrl) ?>" class="button">Add to Cart</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="product-card home-feature-card">
                    <a href="/shop" class="listing-card-image-link">
                        <div class="product-image-wrap product-image-green"></div>
                    </a>
                    <div class="product-card-body">
                        <h3><a href="/shop" class="listing-title-link">Green Jacket</a></h3>
                        <p class="product-price">£49.99</p>
                        <div class="home-product-actions">
                            <a href="/shop" class="button">Add to Cart</a>
                        </div>
                    </div>
                </div>

                <div class="product-card home-feature-card">
                    <a href="/shop" class="listing-card-image-link">
                        <div class="product-image-wrap product-image-hair"></div>
                    </a>
                    <div class="product-card-body">
                        <h3><a href="/shop" class="listing-title-link">Hair Care Set</a></h3>
                        <p class="product-price">£29.99</p>
                        <div class="home-product-actions">
                            <a href="/shop" class="button">Add to Cart</a>
                        </div>
                    </div>
                </div>

                <div class="product-card home-feature-card">
                    <a href="/shop" class="listing-card-image-link">
                        <div class="product-image-wrap product-image-chips"></div>
                    </a>
                    <div class="product-card-body">
                        <h3><a href="/shop" class="listing-title-link">Organic Chips</a></h3>
                        <p class="product-price">£9.99</p>
                        <div class="home-product-actions">
                            <a href="/shop" class="button">Add to Cart</a>
                        </div>
                    </div>
                </div>

                <div class="product-card home-feature-card">
                    <a href="/shop" class="listing-card-image-link">
                        <div class="product-image-wrap product-image-watch"></div>
                    </a>
                    <div class="product-card-body">
                        <h3><a href="/shop" class="listing-title-link">Black Watch</a></h3>
                        <p class="product-price">£59.99</p>
                        <div class="home-product-actions">
                            <a href="/shop" class="button">Add to Cart</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
</div>