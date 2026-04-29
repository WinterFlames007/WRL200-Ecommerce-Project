<div class="admin-page">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-brand">StoreName</div>

            <nav class="admin-sidebar-nav">
                <a href="/admin/dashboard">Dashboard</a>
                <a href="/admin/users">User List</a>
                <a href="/admin/settings">Settings</a>
                <a href="/admin/reports" class="active">Reports</a>
                <a href="/admin/logs">Logs</a>
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="admin-main">
            <div class="admin-topbar">
                <h1>Reports</h1>
                <div class="admin-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?>
                </div>
            </div>

            <div class="admin-stat-grid">
                <div class="admin-stat-card blue">
                    <div class="admin-stat-number"><?= (int) ($summary['total_users'] ?? 0) ?></div>
                    <div class="admin-stat-label">Total Users</div>
                </div>

                <div class="admin-stat-card green">
                    <div class="admin-stat-number"><?= (int) ($summary['total_active'] ?? 0) ?></div>
                    <div class="admin-stat-label">Active Users</div>
                </div>

                <div class="admin-stat-card orange">
                    <div class="admin-stat-number"><?= (int) ($summary['total_inactive'] ?? 0) ?></div>
                    <div class="admin-stat-label">Inactive Users</div>
                </div>

                <div class="admin-stat-card red">
                    <div class="admin-stat-number"><?= (int) ($summary['total_sellers'] ?? 0) ?></div>
                    <div class="admin-stat-label">Sellers</div>
                </div>
            </div>

            <div class="dashboard-panel">
                <div class="dashboard-panel-title">Available Exports</div>
                <div class="dashboard-panel-body">
                    <div class="admin-quick-actions">
                        <a href="/admin/reports/users" class="button">Download Full Users Report</a>
                        <a href="/admin/reports/user-status-summary" class="button button-secondary">Download User Status Summary</a>
                    </div>
                </div>
            </div>

            <div class="dashboard-panel spacer-top">
                <div class="dashboard-panel-title">Report Description</div>
                <div class="dashboard-panel-body">
                    <p><strong>Full Users Report:</strong> exports all users with account details, role, status, address, created date, and last login date.</p>
                    <p><strong>User Status Summary:</strong> exports grouped totals by role and account status.</p>
                </div>
            </div>
        </div>
    </div>
</div>