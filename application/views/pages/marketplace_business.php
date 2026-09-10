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
                    <p>Henüz bu işletme için onaylı yorum bulunmamaktadır.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
