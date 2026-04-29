<h1>Reset Password</h1>

<?php if (!empty($errors)): ?>
    <ul style="color: red;">
        <?php foreach ($errors as $error): ?>
            <li><?= $error ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="POST" action="/reset-password">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <label>New Password</label>
    <input type="password" name="new_password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" required>

    <button type="submit">Reset Password</button>
</form>