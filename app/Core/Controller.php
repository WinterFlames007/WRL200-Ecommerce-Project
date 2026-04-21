<?php
namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        $db = Database::connect();

        $globalSettings = [
            'support_email' => 'support@yourstore.com',
            'maintenance_mode' => '0',
            'email_notifications' => '1',
            'mode' => 'Live Mode'
        ];

        $settingsResult = mysqli_query(
            $db,
            "SELECT setting_key, setting_value
             FROM platform_settings
             WHERE setting_key IN ('support_email', 'maintenance_mode', 'email_notifications', 'mode')"
        );

        if ($settingsResult) {
            while ($row = mysqli_fetch_assoc($settingsResult)) {
                $globalSettings[$row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        }

        extract($data);
        extract($globalSettings, EXTR_PREFIX_ALL, 'setting');

        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        $headerPath = __DIR__ . '/../Views/layouts/header.php';
        $footerPath = __DIR__ . '/../Views/layouts/footer.php';

        if (!file_exists($viewPath)) {
            die("View {$view} not found.");
        }

        if (file_exists($headerPath)) {
            require $headerPath;
        }

        require $viewPath;

        if (file_exists($footerPath)) {
            require $footerPath;
        }
    }
}