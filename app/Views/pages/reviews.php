<div class="info-page">
    <div class="page-header">
        <div>
            <h1>Customer Reviews</h1>
            <p class="muted">Read customer feedback and share your own experience.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
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

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer'): ?>
        <div class="card">
            <h2>Add Your Review</h2>

            <form method="POST" action="/reviews">
                <label for="rating">Rating</label>
                <select name="rating" id="rating" required>
                    <option value="">Select Rating</option>
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>
                </select>

                <label for="title">Review Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Example: Great shopping experience"
                    required
                >

                <label for="review_text">Review</label>
                <textarea
                    id="review_text"
                    name="review_text"
                    placeholder="Write your review here..."
                    required
                ></textarea>

                <button type="submit">Submit Review</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card-soft">
            <p>You must sign in as a customer to add a review.</p>
            <a href="/login" class="button">Login to Add Review</a>
        </div>
    <?php endif; ?>

    <div class="dashboard-panel spacer-top">
        <div class="dashboard-panel-title">All Reviews</div>

        <?php if (empty($reviews)): ?>
            <div class="dashboard-panel-body">
                <p>No reviews have been added yet.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3 dashboard-panel-body">
                <?php foreach ($reviews as $review): ?>
                    <div class="card-soft">
                        <h3><?= htmlspecialchars($review['title']) ?></h3>

                        <p class="review-stars">
                            <?= str_repeat('★', (int)$review['rating']) ?>
                            <?= str_repeat('☆', 5 - (int)$review['rating']) ?>
                        </p>

                        <p><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>

                        <p class="muted">
                            By <?= htmlspecialchars($review['full_name']) ?>
                            on <?= htmlspecialchars(date('d-m-Y', strtotime($review['created_at']))) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>