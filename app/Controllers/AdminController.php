<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class AdminController extends Controller
{
    private function logAdminAction(
        int $adminUserId,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $details = null
    ): void {
        $db = Database::connect();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = mysqli_prepare(
            $db,
            "INSERT INTO audit_logs
            (admin_user_id, action, target_type, target_id, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ississ",
            $adminUserId,
            $action,
            $targetType,
            $targetId,
            $details,
            $ipAddress
        );

        mysqli_stmt_execute($stmt);
    }

    public function dashboard(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $usersResult = mysqli_query($db, "SELECT COUNT(*) AS total_users FROM users");
        $usersData = mysqli_fetch_assoc($usersResult);

        $sellerResult = mysqli_query($db, "SELECT COUNT(*) AS total_sellers FROM users WHERE role = 'seller'");
        $sellerData = mysqli_fetch_assoc($sellerResult);

        $customerResult = mysqli_query($db, "SELECT COUNT(*) AS total_customers FROM users WHERE role = 'customer'");
        $customerData = mysqli_fetch_assoc($customerResult);

        $inactiveResult = mysqli_query($db, "SELECT COUNT(*) AS total_inactive FROM users WHERE status = 'inactive'");
        $inactiveData = mysqli_fetch_assoc($inactiveResult);

        $search = trim($_GET['search'] ?? '');
        $role = trim($_GET['role'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $dashboardFilter = trim($_GET['dashboard_filter'] ?? '');

        $allowedRoles = ['customer', 'seller', 'admin'];
        $allowedStatuses = ['active', 'inactive'];
        $allowedDashboardFilters = ['all_users', 'sellers', 'customers', 'inactive'];

        $perPage = 10;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $where = "WHERE 1=1";
        $types = '';
        $params = [];

        if ($search !== '') {
            $where .= " AND (full_name LIKE ? OR email LIKE ?)";
            $types .= 'ss';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if (in_array($role, $allowedRoles, true)) {
            $where .= " AND role = ?";
            $types .= 's';
            $params[] = $role;
        }

        if (in_array($status, $allowedStatuses, true)) {
            $where .= " AND status = ?";
            $types .= 's';
            $params[] = $status;
        }

        if (in_array($dashboardFilter, $allowedDashboardFilters, true)) {
            if ($dashboardFilter === 'sellers') {
                $where .= " AND role = 'seller'";
            } elseif ($dashboardFilter === 'customers') {
                $where .= " AND role = 'customer'";
            } elseif ($dashboardFilter === 'inactive') {
                $where .= " AND status = 'inactive'";
            }
        }

        $countSql = "SELECT COUNT(*) AS total_rows FROM users $where";
        $countStmt = mysqli_prepare($db, $countSql);

        if (!empty($params)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
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

        $sql = "
            SELECT
                id,
                full_name,
                email,
                phone,
                role,
                status,
                created_at,
                last_login_at
            FROM users
            $where
            ORDER BY id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = mysqli_prepare($db, $sql);

        $finalParams = $params;
        $finalParams[] = $perPage;
        $finalParams[] = $offset;
        $finalTypes = $types . 'ii';

        mysqli_stmt_bind_param($stmt, $finalTypes, ...$finalParams);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $this->view('admin/dashboard/index', [
            'totalUsers' => $usersData['total_users'] ?? 0,
            'totalSellers' => $sellerData['total_sellers'] ?? 0,
            'totalCustomers' => $customerData['total_customers'] ?? 0,
            'totalInactive' => $inactiveData['total_inactive'] ?? 0,
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'dashboardFilter' => $dashboardFilter,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows
        ]);
    }

    public function users(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $search = trim($_GET['search'] ?? '');
        $role = trim($_GET['role'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $allowedRoles = ['customer', 'seller', 'admin'];
        $allowedStatuses = ['active', 'inactive'];

        $perPage = 10;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $where = "WHERE 1=1";
        $types = '';
        $params = [];

        if ($search !== '') {
            $where .= " AND (full_name LIKE ? OR email LIKE ?)";
            $types .= 'ss';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if (in_array($role, $allowedRoles, true)) {
            $where .= " AND role = ?";
            $types .= 's';
            $params[] = $role;
        }

        if (in_array($status, $allowedStatuses, true)) {
            $where .= " AND status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $countSql = "SELECT COUNT(*) AS total_rows FROM users $where";
        $countStmt = mysqli_prepare($db, $countSql);

        if (!empty($params)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
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

        $sql = "
            SELECT
                id,
                full_name,
                email,
                phone,
                role,
                status,
                created_at,
                last_login_at
            FROM users
            $where
            ORDER BY id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = mysqli_prepare($db, $sql);

        $finalParams = $params;
        $finalParams[] = $perPage;
        $finalParams[] = $offset;
        $finalTypes = $types . 'ii';

        mysqli_stmt_bind_param($stmt, $finalTypes, ...$finalParams);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $this->view('admin/users/index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows
        ]);
    }

    public function toggleUserStatus(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $userId = (int) ($_GET['id'] ?? 0);

        if ($userId <= 0) {
            echo 'Invalid user ID.';
            return;
        }

        $currentAdminId = (int) $_SESSION['user']['id'];

        if ($userId === $currentAdminId) {
            echo 'You cannot disable your own admin account.';
            return;
        }

        $db = Database::connect();

        $stmt = mysqli_prepare($db, "SELECT id, full_name, status, role FROM users WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            echo 'User not found.';
            return;
        }

        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';

        $updateStmt = mysqli_prepare($db, "UPDATE users SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($updateStmt, "si", $newStatus, $userId);
        mysqli_stmt_execute($updateStmt);

        $this->logAdminAction(
            $currentAdminId,
            'toggle_user_status',
            'user',
            $userId,
            'Changed status to ' . $newStatus . ' for ' . $user['full_name'] . ' (' . $user['role'] . ')'
        );

        $redirect = $_SERVER['HTTP_REFERER'] ?? '/admin/users';
        header('Location: ' . $redirect);
        exit;
    }

    public function deleteUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $userId = (int) ($_GET['id'] ?? 0);

        if ($userId <= 0) {
            echo 'Invalid user ID.';
            return;
        }

        $currentAdminId = (int) $_SESSION['user']['id'];

        if ($userId === $currentAdminId) {
            echo 'You cannot delete your own admin account.';
            return;
        }

        $db = Database::connect();

        $checkStmt = mysqli_prepare($db, "SELECT id, full_name, role, email FROM users WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, "i", $userId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $user = mysqli_fetch_assoc($checkResult);

        if (!$user) {
            echo 'User not found.';
            return;
        }

        $this->logAdminAction(
            $currentAdminId,
            'delete_user',
            'user',
            $userId,
            'Deleted user ' . $user['full_name'] . ' (' . $user['email'] . ', ' . $user['role'] . ')'
        );

        $deleteStmt = mysqli_prepare($db, "DELETE FROM users WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($deleteStmt, "i", $userId);
        mysqli_stmt_execute($deleteStmt);

        $redirect = $_SERVER['HTTP_REFERER'] ?? '/admin/users';
        header('Location: ' . $redirect);
        exit;
    }




    public function settings(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $defaultSettings = [
            'two_factor_enabled' => '0',
            'minimum_password_length' => '8',
            'session_timeout' => '30 Minutes',
            'stripe_api_key' => '',
            'webhook_url' => '',
            'mode' => 'Test Mode',
            'smtp_host' => '',
            'support_email' => '',
            'email_notifications' => '0',
            'maintenance_mode' => '0',
            'backup_schedule' => 'Daily at 02:00 AM',
            'system_version' => '1.0.0'
        ];

        $result = mysqli_query($db, "SELECT setting_key, setting_value FROM platform_settings");
        $settings = $defaultSettings;

        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $success = $_SESSION['settings_success'] ?? null;
        unset($_SESSION['settings_success']);

        $this->view('admin/settings/index', [
            'settings' => $settings,
            'success' => $success
        ]);
    }




    public function saveSettings(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $minimumPasswordLength = trim($_POST['minimum_password_length'] ?? '8');
        $sessionTimeout = trim($_POST['session_timeout'] ?? '30 Minutes');
        $mode = trim($_POST['mode'] ?? 'Test Mode');
        $backupSchedule = trim($_POST['backup_schedule'] ?? 'Daily at 02:00 AM');
        $systemVersion = trim($_POST['system_version'] ?? '1.0.0');

        $allowedPasswordLengths = ['6', '8', '10', '12'];
        $allowedSessionTimeouts = ['15 Minutes', '30 Minutes', '1 Hour'];
        $allowedModes = ['Test Mode', 'Live Mode'];
        $allowedBackupSchedules = ['Daily at 02:00 AM', 'Weekly on Sunday', 'Monthly on 1st'];

        if (!in_array($minimumPasswordLength, $allowedPasswordLengths, true)) {
            $minimumPasswordLength = '8';
        }

        if (!in_array($sessionTimeout, $allowedSessionTimeouts, true)) {
            $sessionTimeout = '30 Minutes';
        }

        if (!in_array($mode, $allowedModes, true)) {
            $mode = 'Test Mode';
        }

        if (!in_array($backupSchedule, $allowedBackupSchedules, true)) {
            $backupSchedule = 'Daily at 02:00 AM';
        }

        $settings = [
            'two_factor_enabled' => isset($_POST['two_factor_enabled']) ? '1' : '0',
            'minimum_password_length' => $minimumPasswordLength,
            'session_timeout' => $sessionTimeout,
            'stripe_api_key' => trim($_POST['stripe_api_key'] ?? ''),
            'webhook_url' => trim($_POST['webhook_url'] ?? ''),
            'mode' => $mode,
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'support_email' => trim($_POST['support_email'] ?? ''),
            'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'backup_schedule' => $backupSchedule,
            'system_version' => $systemVersion
        ];

        $stmt = mysqli_prepare(
            $db,
            "INSERT INTO platform_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        foreach ($settings as $key => $value) {
            mysqli_stmt_bind_param($stmt, "ss", $key, $value);
            mysqli_stmt_execute($stmt);
        }

        $_SESSION['settings_success'] = 'Platform settings updated successfully.';
        header('Location: /admin/settings');
        exit;
    }




















    public function reports(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $summary = [
            'total_users' => 0,
            'total_active' => 0,
            'total_inactive' => 0,
            'total_customers' => 0,
            'total_sellers' => 0,
            'total_admins' => 0
        ];

        $sql = "
            SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS total_active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS total_inactive,
                SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) AS total_customers,
                SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) AS total_sellers,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS total_admins
            FROM users
        ";

        $result = mysqli_query($db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            if ($row) {
                $summary = $row;
            }
        }

        $this->view('admin/reports/index', [
            'summary' => $summary
        ]);
    }

    public function exportUsersCsv(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $sql = "
            SELECT
                id,
                full_name,
                email,
                phone,
                address_line1,
                city,
                postcode,
                country,
                role,
                status,
                created_at,
                last_login_at
            FROM users
            ORDER BY id DESC
        ";

        $result = mysqli_query($db, $sql);

        $this->logAdminAction(
            (int) $_SESSION['user']['id'],
            'export_users_report',
            'report',
            null,
            'Exported full users CSV report'
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_report.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'User ID',
            'Full Name',
            'Email',
            'Phone',
            'Address',
            'City',
            'Postcode',
            'Country',
            'Role',
            'Status',
            'Created At',
            'Last Login'
        ]);

        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['full_name'],
                $row['email'],
                $row['phone'],
                $row['address_line1'],
                $row['city'],
                $row['postcode'],
                $row['country'],
                $row['role'],
                $row['status'],
                $row['created_at'],
                $row['last_login_at']
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportUserStatusSummaryCsv(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $sql = "
            SELECT
                role,
                status,
                COUNT(*) AS total_users
            FROM users
            GROUP BY role, status
            ORDER BY role ASC, status ASC
        ";

        $result = mysqli_query($db, $sql);

        $this->logAdminAction(
            (int) $_SESSION['user']['id'],
            'export_user_status_summary',
            'report',
            null,
            'Exported user status summary CSV report'
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="user_status_summary.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Role',
            'Status',
            'Total Users'
        ]);

        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                ucfirst($row['role']),
                ucfirst($row['status']),
                $row['total_users']
            ]);
        }

        fclose($output);
        exit;
    }

    public function logs(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = Database::connect();

        $search = trim($_GET['search'] ?? '');
        $action = trim($_GET['action'] ?? '');

        $perPage = 10;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $where = "WHERE 1=1";
        $types = '';
        $params = [];

        if ($search !== '') {
            $where .= " AND (u.full_name LIKE ? OR al.details LIKE ? OR al.target_type LIKE ?)";
            $types .= 'sss';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($action !== '') {
            $where .= " AND al.action = ?";
            $types .= 's';
            $params[] = $action;
        }

        $countSql = "
            SELECT COUNT(*) AS total_rows
            FROM audit_logs al
            INNER JOIN users u ON al.admin_user_id = u.id
            $where
        ";

        $countStmt = mysqli_prepare($db, $countSql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
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

        $sql = "
            SELECT
                al.id,
                al.action,
                al.target_type,
                al.target_id,
                al.details,
                al.ip_address,
                al.created_at,
                u.full_name AS admin_name
            FROM audit_logs al
            INNER JOIN users u ON al.admin_user_id = u.id
            $where
            ORDER BY al.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = mysqli_prepare($db, $sql);

        $finalParams = $params;
        $finalParams[] = $perPage;
        $finalParams[] = $offset;
        $finalTypes = $types . 'ii';

        mysqli_stmt_bind_param($stmt, $finalTypes, ...$finalParams);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $logs = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $actionsResult = mysqli_query($db, "SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
        $actions = mysqli_fetch_all($actionsResult, MYSQLI_ASSOC);

        $this->view('admin/logs/index', [
            'logs' => $logs,
            'actions' => $actions,
            'search' => $search,
            'selectedAction' => $action,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows
        ]);
    }
}