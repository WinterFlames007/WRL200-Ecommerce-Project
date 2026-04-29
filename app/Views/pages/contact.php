<div class="info-page">
    <div class="page-header">
        <div>
            <h1>Contact Us</h1>
            <p class="muted">Send a message directly to the store seller.</p>
        </div>
    </div>

    <?php if (!empty($_SESSION['contact_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['contact_success']) ?>
        </div>
        <?php unset($_SESSION['contact_success']); ?>
    <?php endif; ?>

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

    <div class="grid grid-2">
        <div class="card">
            <h2>Contact Information</h2>
            <p><strong>Email:</strong> seller@gmail.com</p>
            <p><strong>Location:</strong> United Kingdom</p>
            <p class="muted">
                Only signed-in customers can send contact messages.
            </p>
        </div>

        <div class="card">
            <h2>Send a Message</h2>

            <form method="POST" action="/contact">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? '') ?>"
                    required
                >

                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>"
                    required
                >

                <label for="subject">Subject</label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    placeholder="Order enquiry, product question, support request..."
                    required
                >

                <label for="message">Message</label>
                <textarea
                    id="message"
                    name="message"
                    placeholder="Write your message here..."
                    required
                ></textarea>

                <button type="submit">Send Message</button>
            </form>
        </div>
    </div>
</div>