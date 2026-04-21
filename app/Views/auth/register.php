<div class="auth-shell">
    <div class="page-header">
        <div>
            <h1>Create Account</h1>
            <p class="muted">Register to start using the e-commerce platform.</p>
        </div>
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

    <form method="POST" action="/register">
        <div class="form-row">
            <div>
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div>
                <label for="phone">Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="Enter your phone number"
                >
            </div>
        </div>

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
            placeholder="Enter a secure password"
            required
        >

        <!-- <label for="role">Account Type</label>
        <select id="role" name="role">
            <option value="customer">Customer</option>
            <option value="seller">Seller</option>
        </select> -->

        <div class="form-actions">
            <button type="submit">Create Account</button>
            <a href="/login" class="button button-secondary">Back to Login</a>
        </div>
    </form>


    <div class="card-soft">
        <p class="muted">
            Create a customer account to browse products, place orders, and track your purchases.
        </p>
    </div>


</div>