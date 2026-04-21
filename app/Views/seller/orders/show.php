<div class="seller-products-page">
    <div class="seller-shell">
        <aside class="seller-sidebar">
            <div class="seller-sidebar-brand">StoreName</div>

            <nav class="seller-sidebar-nav">
                <a href="/seller/dashboard">Dashboard</a>
                <a href="/seller/products">Products</a>
                <a href="/seller/orders" class="active">Orders</a>
                <a href="/seller/inventory">Inventory</a>
                <a href="/seller/export/orders">Reports</a>
                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="seller-main">
            <div class="seller-topbar">
                <h1>Order Details</h1>
                <div class="seller-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Seller') ?>
                </div>
            </div>

            <div class="inline-links">
                <a href="/seller/orders" class="button button-secondary">Back to Orders</a>
            </div>

            <div class="dashboard-panel">
                <div class="dashboard-panel-title">Order Information</div>
                <div class="dashboard-panel-body">
                    <p><strong>Order Number:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                    <!-- <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p> -->



                    <?php
                    $status = strtolower($order['status']);
                    $badgeClass = 'badge-neutral';

                    if (in_array($status, ['paid', 'delivered'], true)) {
                        $badgeClass = 'badge-success';
                    } elseif (in_array($status, ['processing', 'pending_payment'], true)) {
                        $badgeClass = 'badge-warning';
                    } elseif ($status === 'cancelled') {
                        $badgeClass = 'badge-danger';
                    }
                    ?>

                    <p>
                        <strong>Status:</strong>
                        <span class="badge <?= $badgeClass ?>">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))) ?>
                        </span>
                    </p>





                    <p><strong>Total Amount:</strong> £<?= number_format((float)$order['total_amount'], 2) ?></p>
                    <p><strong>Created At:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                </div>
            </div>

            <div class="dashboard-panel spacer-top">
                <div class="dashboard-panel-title">Update Order Status</div>
                <div class="dashboard-panel-body">
                    <form method="POST" action="/seller/order/update-status">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">

                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="pending_payment" <?= $order['status'] === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                        </select>

                        <div class="form-actions">
                            <button type="submit">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dashboard-panel spacer-top">
                <div class="dashboard-panel-title">Delivery Information</div>
                <div class="dashboard-panel-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($order['delivery_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['delivery_email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['delivery_phone']) ?></p>
                    <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                    <p><strong>City:</strong> <?= htmlspecialchars($order['city']) ?></p>
                    <p><strong>Postcode:</strong> <?= htmlspecialchars($order['postcode']) ?></p>
                    <p><strong>Country:</strong> <?= htmlspecialchars($order['country']) ?></p>
                </div>
            </div>

            <div class="dashboard-panel spacer-top">
                <div class="dashboard-panel-title">Order Items</div>

                <?php if (empty($items)): ?>
                    <div class="dashboard-panel-body">
                        <p>No items found for this order.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="seller-orders-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Size</th>
                                    <th>Colour</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['size']) ?></td>
                                        <td><?= htmlspecialchars($item['colour']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td>£<?= number_format((float)$item['unit_price'], 2) ?></td>
                                        <td>£<?= number_format((float)$item['line_total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>