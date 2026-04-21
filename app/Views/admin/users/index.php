<div class="admin-page">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-brand">StoreName</div>

            <nav class="admin-sidebar-nav">
                <a href="/admin/dashboard">Dashboard</a>
                <a href="/admin/users" class="active">User List</a>
                <a href="/admin/settings">Settings</a>

                <a href="/admin/reports">Reports</a>
                <a href="/admin/logs">Logs</a>
  
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="admin-main">
            <div class="admin-topbar">
                <h1>User Management</h1>
                <div class="admin-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?>
                </div>
            </div>

            <?php
            $hasFilters = !empty($search) || !empty($role) || !empty($status);
            ?>

            <form method="GET" action="/admin/users" class="product-toolbar-form">
                <div class="product-toolbar">
                    <div class="product-toolbar-left">
                        <input
                            type="text"
                            name="search"
                            placeholder="Search by name or email..."
                            class="toolbar-input"
                            value="<?= htmlspecialchars($search ?? '') ?>"
                        >

                        <select name="role" class="toolbar-select">
                            <option value="">All Roles</option>
                            <option value="customer" <?= (($role ?? '') === 'customer') ? 'selected' : '' ?>>Customer</option>
                            <option value="seller" <?= (($role ?? '') === 'seller') ? 'selected' : '' ?>>Seller</option>
                            <option value="admin" <?= (($role ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>

                        <select name="status" class="toolbar-select">
                            <option value="">All Status</option>
                            <option value="active" <?= (($status ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($status ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="inventory-header-actions">
                        <button type="submit" class="button product-add-btn">Apply Filters</button>

                        <?php if ($hasFilters): ?>
                            <a href="/admin/users" class="button button-secondary">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="dashboard-panel">
                <div class="dashboard-panel-title">Users</div>

                <?php if (empty($users)): ?>
                    <div class="dashboard-panel-body">
                        <p>No users found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="admin-users-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <?php $statusClass = $user['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>
                                    <tr>
                                        <td>U<?= str_pad((string)$user['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                                        <td>
                                            <span class="badge <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= !empty($user['last_login_at']) ? htmlspecialchars(date('d-m-Y H:i', strtotime($user['last_login_at']))) : 'Never' ?>
                                        </td>
                                        <td class="admin-action-cell">
                                            <?php if ((int)$user['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
                                                <a
                                                    href="/admin/user/toggle-status?id=<?= (int)$user['id'] ?>"
                                                    class="button"
                                                    data-confirm="Are you sure you want to <?= $user['status'] === 'active' ? 'disable' : 'enable' ?> this user?"
                                                >
                                                    <?= $user['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                                </a>

                                                <a
                                                    href="/admin/user/delete?id=<?= (int)$user['id'] ?>"
                                                    class="button button-danger"
                                                    data-confirm="Are you sure you want to delete this user?"
                                                >
                                                    Delete
                                                </a>
                                            <?php else: ?>
                                                <span class="muted">Current admin</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="seller-table-footer">
                        <span>Showing <?= count($users) ?> of <?= (int)$totalRows ?> user(s)</span>

                        <?php if (($totalPages ?? 1) > 1): ?>
                            <div class="seller-dashboard-pagination">
                                <?php $queryParams = $_GET; ?>

                                <?php if (($page ?? 1) > 1): ?>
                                    <?php $queryParams['page'] = $page - 1; ?>
                                    <a href="/admin/users?<?= htmlspecialchars(http_build_query($queryParams)) ?>" class="seller-page-link">‹</a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php $queryParams['page'] = $i; ?>
                                    <a
                                        href="/admin/users?<?= htmlspecialchars(http_build_query($queryParams)) ?>"
                                        class="seller-page-link <?= $i === (int)$page ? 'active' : '' ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if (($page ?? 1) < $totalPages): ?>
                                    <?php $queryParams['page'] = $page + 1; ?>
                                    <a href="/admin/users?<?= htmlspecialchars(http_build_query($queryParams)) ?>" class="seller-page-link">›</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>