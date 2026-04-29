<div class="account-page">
    <h1>Account</h1>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success auto-hide-alert" id="account-success-alert">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="account-section">
        <div class="account-section-header">
            <h2>Account Information</h2>
        </div>

        <div class="account-info-grid">
            <div class="account-labels">
                <p><strong>Name</strong></p>
                <p><strong>Email</strong></p>
                <p><strong>Phone Number</strong></p>
                <p><strong>Delivery Address</strong></p>
                <p><strong>City</strong></p>
                <p><strong>Postcode</strong></p>
                <p><strong>Country</strong></p>
            </div>

            <div class="account-values">
                <p><?= htmlspecialchars($user['full_name'] ?? 'N/A') ?></p>
                <p><?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
                <p><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></p>
                <p><?= htmlspecialchars($user['address_line1'] ?? 'No address yet') ?></p>
                <p><?= htmlspecialchars($user['city'] ?? 'N/A') ?></p>
                <p><?= htmlspecialchars($user['postcode'] ?? 'N/A') ?></p>
                <p><?= htmlspecialchars($user['country'] ?? 'N/A') ?></p>
            </div>
        </div>


        <div class="account-actions">
            <a href="/account/edit" class="button">Edit Profile</a>

            <a href="/account/change-password" class="button button-secondary">
                Change Password
            </a>
        </div>


    </div>

    <div class="account-section">
        <div class="account-section-header">
            <h2>Order History</h2>
        </div>


        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <p>No orders found yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="account-orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $status = strtolower($order['status']);
                            $badgeClass = 'badge-neutral';

                            if (in_array($status, ['paid', 'delivered'], true)) {
                                $badgeClass = 'badge-success';
                            } elseif (in_array($status, ['pending_payment', 'processing'], true)) {
                                $badgeClass = 'badge-warning';
                            } elseif (in_array($status, ['cancelled', 'failed'], true)) {
                                $badgeClass = 'badge-danger';
                            }
                            ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                <td><?= htmlspecialchars(date('F d, Y', strtotime($order['created_at']))) ?></td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))) ?>
                                    </span>
                                </td>
                                <td>£<?= number_format((float)$order['total_amount'], 2) ?></td>
                                <td>
                                    <a href="/account/order?id=<?= (int)$order['id'] ?>" class="button">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($totalPages ?? 1) > 1): ?>
                <div class="account-pagination">
                    <?php if (($currentPage ?? 1) > 1): ?>
                        <a href="/account?page=<?= (int)$currentPage - 1 ?>" class="account-page-link">‹</a>
                    <?php endif; ?>

                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                        <a
                            href="/account?page=<?= $page ?>"
                            class="account-page-link <?= $page === (int)$currentPage ? 'active' : '' ?>"
                        >
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>

                    <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                        <a href="/account?page=<?= (int)$currentPage + 1 ?>" class="account-page-link">›</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>









        

    </div>
</div>