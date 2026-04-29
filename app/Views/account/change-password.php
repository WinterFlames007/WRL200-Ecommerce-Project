<div class="account-page">
    <div class="page-header">
        <div>
            <h1>Change Password</h1>
            <p class="muted">Update your account password securely.</p>
        </div>

        <a href="/account" class="button button-secondary">Back to Account</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please fix the following issues:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/account/change-password">
        <label for="current_password">Current Password</label>
        <input
            type="password"
            id="current_password"
            name="current_password"
            required
        >

        <label for="new_password">New Password</label>
        <input
            type="password"
            id="new_password"
            name="new_password"
            required
        >

        <label for="confirm_password">Confirm New Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
        >

        <div class="form-actions">
            <button type="submit">Update Password</button>
            <a href="/account" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>