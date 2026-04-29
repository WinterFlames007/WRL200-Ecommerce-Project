<div class="admin-page">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-brand">StoreName</div>

            <nav class="admin-sidebar-nav">
                <a href="/admin/dashboard">Dashboard</a>
                <a href="/admin/users">User List</a>
                <a href="/admin/settings">Settings</a>
                <a href="/admin/reports">Reports</a>
                <a href="/admin/logs" class="active">Logs</a>
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="admin-main">
            <div class="admin-topbar">
                <h1>Audit Logs</h1>
                <div class="admin-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?>
                </div>
            </div>

            <form method="GET" action="/admin/logs" class="product-toolbar-form">
                <div class="product-toolbar">
                    <div class="product-toolbar-left">
                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search ?? '') ?>"
                            placeholder="Search admin name, target, or details"
                            class="toolbar-input"
                        >

                        <select name="action" class="toolbar-select">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $actionItem): ?>
                                <option
                                    value="<?= htmlspecialchars($actionItem['action']) ?>"
                                    <?= (($selectedAction ?? '') === $actionItem['action']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $actionItem['action']))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="inventory-header-actions">
                        <button type="submit" class="button product-add-btn">Apply Filters</button>

                        <?php if (!empty($search) || !empty($selectedAction)): ?>
                            <a href="/admin/logs" class="button button-secondary">Clear Filter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="dashboard-panel">
                <div class="dashboard-panel-title">System Audit Trail</div>

                <?php if (empty($logs)): ?>
                    <div class="dashboard-panel-body">
                        <p>No logs found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="admin-users-table">
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>Details</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>#<?= (int) $log['id'] ?></td>
                                        <td><?= htmlspecialchars($log['admin_name']) ?></td>
                                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))) ?></td>
                                        <td>
                                            <?= htmlspecialchars($log['target_type'] ?? 'N/A') ?>
                                            <?= !empty($log['target_id']) ? '#' . (int) $log['target_id'] : '' ?>
                                        </td>
                                        <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="seller-table-footer">
                        <span>Showing <?= count($logs) ?> log(s)</span>

                        <?php if (($totalPages ?? 1) > 1): ?>
                            <div class="seller-dashboard-pagination">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a
                                        href="/admin/logs?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                        class="seller-page-link<?= ($page === $i) ? ' active' : '' ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>