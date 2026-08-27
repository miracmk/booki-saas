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
    <title><?php echo htmlspecialchars($page_title); ?></title>
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
            padding: 2rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 0.95rem;
        }

        .filters {
            background-color: #fff;
            padding: 1.5rem 0;
            margin: 1.5rem 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .filters form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters select,
        .filters input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .filters button {
            padding: 0.5rem 1.5rem;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .filters button:hover {
            background-color: #45a049;
        }

        .filters a.clear-filters {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .filters a.clear-filters:hover {
            color: #333;
        }

        .results-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .businesses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .business-card {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }

        .business-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .business-card-image {
            width: 100%;
            height: 150px;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #999;
            overflow: hidden;
        }

        .business-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .business-card-content {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .business-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .business-card-meta {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 0.75rem;
        }

        .business-card-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .business-card-rating {
            font-size: 0.9rem;
            margin-top: auto;
        }

        .stars {
            color: #ffc107;
            margin-right: 0.5rem;
        }

        .review-count {
            color: #999;
            font-size: 0.85rem;
        }

        .no-results {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            color: #666;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            font-size: 0.9rem;
        }

        .pagination a:hover {
            background-color: #f0f0f0;
        }

        .pagination .active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <p>En iyi işletmeleri keşfedin ve randevu alın</p>
        </div>
    </div>

    <div class="container">
        <div class="filters">
            <form method="get" action="">
                <select name="category" onchange="this.form.submit()">
                    <option value="">-- Tüm Kategoriler --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected_category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="city" onchange="this.form.submit()">
                    <option value="">-- Tüm Şehirler --</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $selected_city === $c ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($selected_category !== '' || $selected_city !== ''): ?>
                    <a href="" class="clear-filters">Filtreleri Temizle</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="results-info">
            <?php echo $total; ?> işletme bulundu
        </div>

        <?php if (count($tenants) > 0): ?>
            <div class="businesses-grid">
                <?php foreach ($tenants as $tenant): ?>
                    <a href="<?php echo base_url('marketplace/business/' . urlencode($tenant['subdomain'])); ?>" class="business-card">
                        <div class="business-card-image">
                            <?php if (!empty($tenant['cover_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($tenant['cover_image_url']); ?>" alt="">
                            <?php else: ?>
                                📍
                            <?php endif; ?>
                        </div>
                        <div class="business-card-content">
                            <div class="business-card-title">
                                <?php echo htmlspecialchars($tenant['company_name'] ?? $tenant['subdomain']); ?>
                            </div>
                            <div class="business-card-meta">
                                <?php if (!empty($tenant['category'])): ?>
                                    <?php echo htmlspecialchars($tenant['category']); ?>
                                <?php endif; ?>
                                <?php if (!empty($tenant['category']) && !empty($tenant['city'])): ?>
                                    &nbsp;|&nbsp;
                                <?php endif; ?>
                                <?php if (!empty($tenant['city'])): ?>
                                    <?php echo htmlspecialchars($tenant['city']); ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($tenant['short_description'])): ?>
                                <div class="business-card-description">
                                    <?php echo htmlspecialchars($tenant['short_description']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="business-card-rating">
                                <?php if ((int)$tenant['review_count'] > 0): ?>
                                    <span class="stars">★★★★★</span>
                                    <span><?php echo round((float)$tenant['avg_rating'], 1); ?></span>
                                    <span class="review-count">(<?php echo $tenant['review_count']; ?> yorum)</span>
                                <?php else: ?>
                                    <span class="review-count">Henüz yorum yok</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo base_url('marketplace?page=1' . ($selected_category !== '' ? '&category=' . urlencode($selected_category) : '') . ($selected_city !== '' ? '&city=' . urlencode($selected_city) : '')); ?>">« İlk</a>
                        <a href="<?php echo base_url('marketplace?page=' . ($page - 1) . ($selected_category !== '' ? '&category=' . urlencode($selected_category) : '') . ($selected_city !== '' ? '&city=' . urlencode($selected_city) : '')); ?>">‹ Önceki</a>
                    <?php else: ?>
                        <span class="disabled">« İlk</span>
                        <span class="disabled">‹ Önceki</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php elseif ($i >= $page - 2 && $i <= $page + 2): ?>
                            <a href="<?php echo base_url('marketplace?page=' . $i . ($selected_category !== '' ? '&category=' . urlencode($selected_category) : '') . ($selected_city !== '' ? '&city=' . urlencode($selected_city) : '')); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo base_url('marketplace?page=' . ($page + 1) . ($selected_category !== '' ? '&category=' . urlencode($selected_category) : '') . ($selected_city !== '' ? '&city=' . urlencode($selected_city) : '')); ?>">Sonraki ›</a>
                        <a href="<?php echo base_url('marketplace?page=' . $total_pages . ($selected_category !== '' ? '&category=' . urlencode($selected_category) : '') . ($selected_city !== '' ? '&city=' . urlencode($selected_city) : '')); ?>">Son »</a>
                    <?php else: ?>
                        <span class="disabled">Sonraki ›</span>
                        <span class="disabled">Son »</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <p>Aramanızla eşleşen işletme bulunamadı. Filtreleri değiştirip tekrar deneyin.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
