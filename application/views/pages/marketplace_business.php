<?php defined('BASEPATH') or exit('No direct script access allowed');

// This view is a standalone public page (not wrapped by layouts/backend_layout's extend()/section()
// template engine), so - unlike admin pages - it needs its html_vars() values pulled into plain PHP
// variables explicitly (CodeIgniter's view loader does not auto-extract them for this render path).
extract(html_vars());
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Ki Reservation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            margin-bottom: 1.5rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #4CAF50;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .business-header {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .business-cover {
            width: 100%;
            height: 200px;
            background-color: #e0e0e0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #999;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .business-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .business-info h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .business-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .business-meta-item {
            display: flex;
            flex-direction: column;
        }

        .business-meta-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .business-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .business-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 1.5rem;
        }

        .rating-box {
            text-align: center;
        }

        .rating-stars {
            font-size: 1.5rem;
            color: #ffc107;
            margin-bottom: 0.25rem;
        }

        .rating-score {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
        }

        .rating-count {
            font-size: 0.85rem;
            color: #999;
        }

        .booking-button {
            display: inline-block;
            padding: 0.75rem 2rem;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: background-color 0.2s;
        }

        .booking-button:hover {
            background-color: #45a049;
        }

        .reviews-section {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .reviews-section h2 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }

        .review-form {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 4px;
            margin-bottom: 2rem;
            border: 1px solid #eee;
        }

        .review-form h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 4px rgba(76, 175, 80, 0.2);
        }

        .rating-input {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .rating-input input[type="radio"] {
            display: none;
            width: auto;
        }

        .rating-input label {
            display: inline-block;
            width: 2.5rem;
            height: 2.5rem;
            text-align: center;
            line-height: 2.5rem;
            cursor: pointer;
            font-size: 1.5rem;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin-bottom: 0;
            transition: background-color 0.2s, color 0.2s;
            border: 2px solid transparent;
        }

        .rating-input input[type="radio"]:checked + label {
            background-color: #ffc107;
            color: white;
            border-color: #ff9800;
        }

        .rating-input label:hover {
            background-color: #e0e0e0;
        }

        .submit-button {
            background-color: #4CAF50;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .submit-button:hover {
            background-color: #45a049;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .review-item {
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #fafafa;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .review-author {
            font-weight: 600;
            color: #333;
        }

        .review-date {
            font-size: 0.85rem;
            color: #999;
        }

        .review-rating {
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .review-comment {
            color: #666;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            color: #999;
            padding: 2rem 1rem;
        }

        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: none;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <a href="<?php echo base_url('marketplace'); ?>" class="back-link">← Marketplace'e Dön</a>
        </div>
    </div>

    <div class="container">
        <div class="business-header">
            <div class="business-cover">
                <?php if (!empty($tenant['cover_image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($tenant['cover_image_url']); ?>" alt="">
                <?php else: ?>
                    📍
                <?php endif; ?>
            </div>

            <div class="business-info">
                <h1><?php echo htmlspecialchars($tenant['company_name'] ?? $tenant['subdomain']); ?></h1>

                <div class="business-meta">
                    <?php if (!empty($tenant['category'])): ?>
                        <div class="business-meta-item">
                            <span class="business-meta-label">Kategori</span>
                            <span><?php echo htmlspecialchars($tenant['category']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($tenant['city'])): ?>
                        <div class="business-meta-item">
                            <span class="business-meta-label">Şehir</span>
                            <span><?php echo htmlspecialchars($tenant['city']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($tenant['short_description'])): ?>
                    <div class="business-description">
                        <?php echo htmlspecialchars($tenant['short_description']); ?>
                    </div>
                <?php endif; ?>

                <div class="business-rating">
                    <div class="rating-box">
                        <?php if ((int)$tenant['review_count'] > 0): ?>
                            <div class="rating-stars">★★★★★</div>
                            <div class="rating-score"><?php echo round((float)$tenant['avg_rating'], 1); ?></div>
                            <div class="rating-count"><?php echo $tenant['review_count']; ?> yorum</div>
                        <?php else: ?>
                            <div class="rating-count">Henüz yorum yok</div>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="<?php echo htmlspecialchars($booking_url); ?>" target="_blank" class="booking-button">Randevu Al</a>
            </div>
        </div>

        <div class="reviews-section">
            <h2>Müşteri Yorumları</h2>

            <div class="review-form">
                <h3>Bir Yorum Bırak</h3>
                <div id="form-message" class="message"></div>

                <form id="review-form" onsubmit="submitReview(event)">
                    <div class="form-group">
                        <label for="customer_name">Adınız *</label>
                        <input type="text" id="customer_name" name="customer_name" required maxlength="128">
                    </div>

                    <div class="form-group">
                        <label>Derecelendirme *</label>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" id="rating-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="rating-<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comment">Yorum</label>
                        <textarea id="comment" name="comment"></textarea>
                    </div>

                    <button type="submit" class="submit-button">Yorumu Gönder</button>
                </form>
            </div>

            <?php if (count($reviews) > 0): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-author"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                                <span class="review-date">
                                    <?php
                                    $date = new DateTime($review['created_at']);
                                    echo $date->format('d.m.Y');
                                    ?>
                                </span>
                            </div>
                            <div class="review-rating">
                                <?php echo str_repeat('★', $review['rating']); ?>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <div class="review-comment">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <p>Henüz bu işletme için yorum yapılmamıştır. İlk yorumu siz yapın!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function submitReview(event) {
            event.preventDefault();

            const form = document.getElementById('review-form');
            const messageDiv = document.getElementById('form-message');

            // Get form data
            const formData = new FormData(form);
            const data = new URLSearchParams(formData);

            // Add tenant ID
            data.append('id_tenants', <?php echo (int)$tenant['id']; ?>);

            // Submit via fetch
            fetch('<?php echo base_url('marketplace/submit_review'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data.toString()
            })
            .then(response => response.json())
            .then(result => {
                messageDiv.classList.remove('success', 'error');
                messageDiv.style.display = 'block';

                if (result.success) {
                    messageDiv.classList.add('success');
                    messageDiv.textContent = result.message;
                    form.reset();
                    // Reload the page after 2 seconds to show the new review
                    setTimeout(() => location.reload(), 2000);
                } else {
                    messageDiv.classList.add('error');
                    messageDiv.textContent = result.message || 'Bir hata oluştu.';
                }
            })
            .catch(error => {
                messageDiv.classList.remove('success');
                messageDiv.classList.add('error');
                messageDiv.style.display = 'block';
                messageDiv.textContent = 'İsteğiniz işlenirken bir hata oluştu.';
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
