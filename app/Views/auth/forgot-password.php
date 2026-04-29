<h1>Forgot Password</h1>

<?php if (!empty($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <ul style="color: red;">
        <?php foreach ($errors as $error): ?>
            <li><?= $error ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="POST" action="/forgot-password">
    <label>Email Address</label>
    <input type="email" name="email" required>
    <button type="submit">Send Reset Link</button>
</form>