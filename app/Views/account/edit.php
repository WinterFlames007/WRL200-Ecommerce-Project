<div class="account-page">
    <h1>Edit Profile</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="account-section">
        <div class="account-section-header">
            <h2>Profile Information</h2>
        </div>

        <form method="POST" action="/account/edit">
            <label for="full_name">Full Name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                required
            >

            <label for="phone">Phone Number</label>
            <input
                type="text"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
            >

            <label for="address_line1">Delivery Address</label>
            <input
                type="text"
                id="address_line1"
                name="address_line1"
                value="<?= htmlspecialchars($user['address_line1'] ?? '') ?>"
            >

            <label for="city">City</label>
            <input
                type="text"
                id="city"
                name="city"
                value="<?= htmlspecialchars($user['city'] ?? '') ?>"
            >

            <label for="postcode">Postcode</label>
            <input
                type="text"
                id="postcode"
                name="postcode"
                value="<?= htmlspecialchars($user['postcode'] ?? '') ?>"
            >

            <label for="country">Country</label>
            <input
                type="text"
                id="country"
                name="country"
                value="<?= htmlspecialchars($user['country'] ?? '') ?>"
            >

            <div class="form-actions">
                <button type="submit">Save Changes</button>
                <a href="/account" class="button button-secondary">Back to Account</a>
            </div>
        </form>
    </div>
</div>