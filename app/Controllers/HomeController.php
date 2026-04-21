<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class HomeController extends Controller
{
    public function index(): void
    {
        $db = Database::connect();


        $featuredSql = "
            SELECT
                p.id,
                p.name,
                p.base_price,
                p.description,
                p.image_path,
                c.name AS category_name,
                MIN(COALESCE(v.price, p.base_price)) AS display_price,
                (
                    SELECT v2.id
                    FROM product_variants v2
                    WHERE v2.product_id = p.id
                    AND v2.status = 'active'
                    AND v2.stock_qty > 0
                    ORDER BY v2.id ASC
                    LIMIT 1
                ) AS default_variant_id

            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_variants v ON v.product_id = p.id AND v.status = 'active'
            WHERE p.is_active = 1
            GROUP BY p.id, p.name, p.base_price, p.description, p.image_path, c.name
            ORDER BY p.id DESC
            LIMIT 4
        ";

        $featuredResult = mysqli_query($db, $featuredSql);
        $featuredProducts = mysqli_fetch_all($featuredResult, MYSQLI_ASSOC);

        $categorySql = "
            SELECT
                c.id,
                c.name,
                COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
            GROUP BY c.id, c.name
            ORDER BY c.name ASC
            LIMIT 4
        ";

        $categoryResult = mysqli_query($db, $categorySql);
        $categories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);

        $this->view('home/index', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories
        ]);
    }

    public function customerDashboard(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
            header('Location: /login');
            exit;
        }

        header('Location: /account');
        exit;
    }

    public function sellerDashboard(): void
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        header('Location: /seller/dashboard');
        exit;
    }

    public function adminDashboard(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        header('Location: /admin/dashboard');
        exit;
    }
}