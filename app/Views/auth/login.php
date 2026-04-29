<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>



<div class="auth-shell">
    <div class="page-header">
        <div>
            <h1>Welcome Back</h1>
            <p class="muted">Sign in to continue to your account.</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>There was a problem with your login:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email address"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <div class="form-actions">
            <button type="submit">Login</button>
            <a href="/register" class="button button-secondary">Create Account</a> 
        </div>

    </form>
            <p>
                <a href="/forgot-password">Forgot Password?</a>
            </p>
    <div class="card-soft">
        <p class="muted">
            Need an account? Create a customer account to start shopping and tracking your orders.
        </p>
    </div>



























</div>