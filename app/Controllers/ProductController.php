<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ProductController extends Controller
{




    public function index(): void
    {
        $db = Database::connect();

        $sort = $_GET['sort'] ?? 'latest';
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $inStock = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';
        $minPrice = ($_GET['min_price'] ?? '') !== '' ? (float) $_GET['min_price'] : null;
        $maxPrice = ($_GET['max_price'] ?? '') !== '' ? (float) $_GET['max_price'] : null;

        $selectedSizes = $_GET['sizes'] ?? [];
        $selectedColours = $_GET['colours'] ?? [];

        if (!is_array($selectedSizes)) {
            $selectedSizes = [];
        }

        if (!is_array($selectedColours)) {
            $selectedColours = [];
        }

        $selectedSizes = array_values(array_filter(array_map('trim', $selectedSizes), fn($value) => $value !== ''));
        $selectedColours = array_values(array_filter(array_map('trim', $selectedColours), fn($value) => $value !== ''));

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        $orderBy = "p.id DESC";

        if ($sort === 'price_low_high') {
            $orderBy = "display_price ASC";
        } elseif ($sort === 'price_high_low') {
            $orderBy = "display_price DESC";
        } elseif ($sort === 'name_az') {
            $orderBy = "p.name ASC";
        } elseif ($sort === 'name_za') {
            $orderBy = "p.name DESC";
        }

        $whereParts = ["p.is_active = 1"];
        $params = [];
        $types = '';

        if ($categoryId > 0) {
            $whereParts[] = "p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        if ($inStock) {
            $whereParts[] = "EXISTS (
                SELECT 1
                FROM product_variants vs
                WHERE vs.product_id = p.id
                AND vs.status = 'active'
                AND vs.stock_qty > 0
            )";
        }

        if (!empty($selectedSizes)) {
            $sizePlaceholders = implode(',', array_fill(0, count($selectedSizes), '?'));
            $whereParts[] = "EXISTS (
                SELECT 1
                FROM product_variants vsize
                WHERE vsize.product_id = p.id
                AND vsize.status = 'active'
                AND vsize.size IN ($sizePlaceholders)
            )";

            foreach ($selectedSizes as $size) {
                $params[] = $size;
                $types .= 's';
            }
        }

        if (!empty($selectedColours)) {
            $colourPlaceholders = implode(',', array_fill(0, count($selectedColours), '?'));
            $whereParts[] = "EXISTS (
                SELECT 1
                FROM product_variants vcolour
                WHERE vcolour.product_id = p.id
                AND vcolour.status = 'active'
                AND vcolour.colour IN ($colourPlaceholders)
            )";

            foreach ($selectedColours as $colour) {
                $params[] = $colour;
                $types .= 's';
            }
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

        $havingParts = [];
        $havingParams = [];
        $havingTypes = '';

        if ($minPrice !== null) {
            $havingParts[] = "display_price >= ?";
            $havingParams[] = $minPrice;
            $havingTypes .= 'd';
        }

        if ($maxPrice !== null) {
            $havingParts[] = "display_price <= ?";
            $havingParams[] = $maxPrice;
            $havingTypes .= 'd';
        }

        $havingClause = '';
        if (!empty($havingParts)) {
            $havingClause = 'HAVING ' . implode(' AND ', $havingParts);
        }

        $baseSql = "
            SELECT 
                p.id,
                p.name,
                p.description,
                p.image_path,
                p.base_price,
                p.is_food,
                c.name AS category_name,
                MIN(COALESCE(v.price, p.base_price)) AS display_price,
                SUM(CASE WHEN v.stock_qty > 0 AND v.status = 'active' THEN 1 ELSE 0 END) AS available_variants
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_variants v ON v.product_id = p.id
            {$whereClause}
            GROUP BY p.id, p.name, p.description, p.image_path, p.base_price, p.is_food, c.name
            {$havingClause}
        ";

        $countSql = "SELECT COUNT(*) AS total_rows FROM ({$baseSql}) AS filtered_products";
        $countStmt = mysqli_prepare($db, $countSql);

        $allParams = array_merge($params, $havingParams);
        $allTypes = $types . $havingTypes;

        if (!empty($allParams)) {
            mysqli_stmt_bind_param($countStmt, $allTypes, ...$allParams);
        }

        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);
        $totalRows = (int) ($countRow['total_rows'] ?? 0);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = $baseSql . " ORDER BY {$orderBy} LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($db, $sql);

        $finalParams = $allParams;
        $finalParams[] = $perPage;
        $finalParams[] = $offset;
        $finalTypes = $allTypes . 'ii';

        mysqli_stmt_bind_param($stmt, $finalTypes, ...$finalParams);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $products = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $pageTitle = 'All Products';

        if ($categoryId > 0) {
            $categoryStmt = mysqli_prepare($db, "SELECT name FROM categories WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($categoryStmt, "i", $categoryId);
            mysqli_stmt_execute($categoryStmt);
            $categoryResult = mysqli_stmt_get_result($categoryStmt);
            $category = mysqli_fetch_assoc($categoryResult);

            if ($category) {
                $pageTitle = $category['name'] . ' Products';
            }
        }

        $sizesResult = mysqli_query(
            $db,
            "SELECT DISTINCT size
            FROM product_variants
            WHERE status = 'active'
            AND size IS NOT NULL
            AND TRIM(size) != ''
            AND size != 'N/A'
            ORDER BY size ASC"
        );
        $availableSizes = mysqli_fetch_all($sizesResult, MYSQLI_ASSOC);

        $coloursResult = mysqli_query(
            $db,
            "SELECT DISTINCT colour
            FROM product_variants
            WHERE status = 'active'
            AND colour IS NOT NULL
            AND TRIM(colour) != ''
            AND colour != 'N/A'
            ORDER BY colour ASC"
        );
        $availableColours = mysqli_fetch_all($coloursResult, MYSQLI_ASSOC);

        $this->view('products/index', [
            'products' => $products,
            'sort' => $sort,
            'pageTitle' => $pageTitle,
            'page' => $page,
            'totalPages' => $totalPages,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'inStock' => $inStock,
            'categoryId' => $categoryId,
            'selectedSizes' => $selectedSizes,
            'selectedColours' => $selectedColours,
            'availableSizes' => $availableSizes,
            'availableColours' => $availableColours
        ]);
    }



    public function show(): void
    {
        $productId = (int) ($_GET['id'] ?? 0);

        if ($productId <= 0) {
            echo 'Invalid product ID.';
            return;
        }

        $db = Database::connect();

        $stmt = mysqli_prepare(
            $db,
            "SELECT p.*, c.name AS category_name
             FROM products p
             INNER JOIN categories c ON p.category_id = c.id
             WHERE p.id = ? AND p.is_active = 1
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if (!$product) {
            echo 'Product not found.';
            return;
        }

        $variantStmt = mysqli_prepare(
            $db,
            "SELECT *
             FROM product_variants
             WHERE product_id = ? AND status = 'active'
             ORDER BY id ASC"
        );
        mysqli_stmt_bind_param($variantStmt, "i", $productId);
        mysqli_stmt_execute($variantStmt);
        $variantResult = mysqli_stmt_get_result($variantStmt);
        $variants = mysqli_fetch_all($variantResult, MYSQLI_ASSOC);


        $variantImagesStmt = mysqli_prepare(
            $db,
            "SELECT id, variant_id, image_path, sort_order
            FROM variant_images
            WHERE variant_id IN (
                SELECT id FROM product_variants WHERE product_id = ? AND status = 'active'
            )
            ORDER BY variant_id ASC, sort_order ASC, id ASC"
        );
        mysqli_stmt_bind_param($variantImagesStmt, "i", $productId);
        mysqli_stmt_execute($variantImagesStmt);
        $variantImagesResult = mysqli_stmt_get_result($variantImagesStmt);

        $variantImagesMap = [];
        while ($row = mysqli_fetch_assoc($variantImagesResult)) {
            $variantId = (int) $row['variant_id'];

            if (!isset($variantImagesMap[$variantId])) {
                $variantImagesMap[$variantId] = [];
            }

            $variantImagesMap[$variantId][] = $row['image_path'];
        }



        $galleryStmt = mysqli_prepare(
            $db,
            "SELECT id, image_path, sort_order
             FROM product_images
             WHERE product_id = ?
             ORDER BY sort_order ASC, id ASC"
        );
        mysqli_stmt_bind_param($galleryStmt, "i", $productId);
        mysqli_stmt_execute($galleryStmt);
        $galleryResult = mysqli_stmt_get_result($galleryStmt);
        $galleryImages = mysqli_fetch_all($galleryResult, MYSQLI_ASSOC);

        $relatedStmt = mysqli_prepare(
            $db,
            "SELECT
                p.id,
                p.name,
                p.base_price,
                p.image_path,
                c.name AS category_name
             FROM products p
             INNER JOIN categories c ON p.category_id = c.id
             WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
             ORDER BY p.id DESC
             LIMIT 4"
        );
        mysqli_stmt_bind_param($relatedStmt, "ii", $product['category_id'], $productId);
        mysqli_stmt_execute($relatedStmt);
        $relatedResult = mysqli_stmt_get_result($relatedStmt);
        $relatedProducts = mysqli_fetch_all($relatedResult, MYSQLI_ASSOC);


        $this->view('products/show', [
            'product' => $product,
            'variants' => $variants,
            'galleryImages' => $galleryImages,
            'variantImagesMap' => $variantImagesMap,
            'relatedProducts' => $relatedProducts
        ]);

    }
}