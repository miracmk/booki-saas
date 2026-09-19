<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Marketplace controller - tenant discovery portal (reservation.kibusiness.co / booki.kibusiness.co).
 *
 * Multi-tenant SaaS only. Reads from the master DB's `tenants` and `reviews` tables to display
 * a modern, searchable/filterable discovery site with comprehensive SEO & GEO (Generative Engine Optimization).
 * Stays on the master DB for the entire request - never tenant-resolves.
 */
class Marketplace extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_multi_tenant_mode()) {
            abort(404, 'Not Found');
        }

        $this->load->library('review_service');
    }

    /**
     * List all marketplace-opted-in tenants with search and filtering.
     */
    public function index(): void
    {
        method('get');

        check('q', 'string|null');
        check('category', 'string|null');
        check('city', 'string|null');
        check('district', 'string|null');
        check('sort', 'string|null');
        check('lat', 'string|null');
        check('lng', 'string|null');
        check('page', 'numeric|null');

        $q = trim((string) request('q'));
        $category = trim((string) request('category'));
        $city = trim((string) request('city'));
        $district = trim((string) request('district'));
        $sort = trim((string) request('sort')) ?: 'recommended';
        $lat = request('lat') !== null && request('lat') !== '' ? (float) request('lat') : null;
        $lng = request('lng') !== null && request('lng') !== '' ? (float) request('lng') : null;
        $page = max(1, (int) request('page', 1));
        $per_page = 12;

        $has_company_name = $this->db->field_exists('company_name', 'tenants');

        $apply_filters = function () use ($q, $category, $city, $district, $has_company_name) {
            $this->db->where('marketplace_opt_in', 1);
            $this->db->where('status', 'active');

            if ($q !== '') {
                $this->db->group_start();
                $this->db->like('tenants.subdomain', $q);
                if ($has_company_name) {
                    $this->db->or_like('tenants.company_name', $q);
                }
                $this->db->or_like('tenants.category', $q);
                $this->db->or_like('tenants.short_description', $q);
                $this->db->or_like('tenants.city', $q);
                $this->db->or_like('tenants.district', $q);
                $this->db->group_end();
            }

            if ($category !== '') {
                $this->db->where('category', $category);
            }

            if ($city !== '') {
                $this->db->where('city', $city);
            }

            if ($district !== '') {
                $this->db->where('district', $district);
            }
        };

        $apply_filters();
        $total = $this->db->count_all_results('tenants');

        $select_fields = 'tenants.*,' .
            '(SELECT COUNT(*) FROM ' . $this->db->dbprefix('reviews') .
            ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
            ' AND status = "published") AS review_count,' .
            '(SELECT AVG(rating) FROM ' . $this->db->dbprefix('reviews') .
            ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
            ' AND status = "published") AS avg_rating';

        if ($lat !== null && $lng !== null) {
            $select_fields .= ', (6371 * 2 * ASIN(SQRT(POWER(SIN((RADIANS(latitude - ' . $this->db->escape($lat) . ') / 2)), 2) + ' .
                'COS(RADIANS(' . $this->db->escape($lat) . ')) * COS(RADIANS(latitude)) * ' .
                'POWER(SIN((RADIANS(longitude - ' . $this->db->escape($lng) . ') / 2)), 2)))) AS distance';
        }

        $this->db->select($select_fields, false);
        $apply_filters();

        if ($sort === 'rating') {
            $this->db->order_by('avg_rating', 'desc');
            $this->db->order_by('review_count', 'desc');
        } elseif ($sort === 'reviews') {
            $this->db->order_by('review_count', 'desc');
            $this->db->order_by('avg_rating', 'desc');
        } elseif ($sort === 'distance' && $lat !== null && $lng !== null) {
            $this->db->order_by('distance', 'asc');
        } elseif ($sort === 'newest') {
            $this->db->order_by('created_at', 'desc');
        } else {
            // Recommended: Bayesian rating score + profile bonus
            $score_expr = '(((COALESCE((SELECT COUNT(*) FROM ' . $this->db->dbprefix('reviews') . ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id AND status = "published"), 0) * ' .
                'COALESCE((SELECT AVG(rating) FROM ' . $this->db->dbprefix('reviews') . ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id AND status = "published"), 4.0)) + 12.0) / ' .
                '(COALESCE((SELECT COUNT(*) FROM ' . $this->db->dbprefix('reviews') . ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id AND status = "published"), 0) + 3)) + ' .
                '(CASE WHEN cover_image_url IS NOT NULL THEN 0.5 ELSE 0 END) + (CASE WHEN short_description IS NOT NULL THEN 0.3 ELSE 0 END)';
            $this->db->order_by($score_expr, 'desc', false);
            $this->db->order_by('created_at', 'desc');
        }

        $tenants = $this->db
            ->limit($per_page, ($page - 1) * $per_page)
            ->get('tenants')
            ->result_array();

        // Fetch distinct categories, cities, districts for filters
        $categories = $this->db
            ->distinct()
            ->select('category')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('category IS NOT NULL', null, false)
            ->order_by('category', 'asc')
            ->get('tenants')
            ->result_array();

        $cities = $this->db
            ->distinct()
            ->select('city')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('city IS NOT NULL', null, false)
            ->order_by('city', 'asc')
            ->get('tenants')
            ->result_array();

        $districts = $this->db
            ->distinct()
            ->select('district')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('district IS NOT NULL', null, false)
            ->order_by('district', 'asc')
            ->get('tenants')
            ->result_array();

        $category_list = array_values(array_filter(array_column($categories, 'category')));
        $city_list = array_values(array_filter(array_column($cities, 'city')));
        $district_list = array_values(array_filter(array_column($districts, 'district')));

        // SEO & GEO: Build Schema.org ItemList JSON-LD
        $current_url = base_url('marketplace');
        $json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'BooKi Hizmet & Randevu Pazar Yeri',
            'description' => 'En iyi kuaför, berber, güzellik salonu, klinik ve uzmanları keşfedin, kolayca randevu alın.',
            'url' => $current_url,
            'numberOfItems' => count($tenants),
            'itemListElement' => [],
        ];

        foreach ($tenants as $idx => $t) {
            $displayName = !empty($t['company_name']) ? $t['company_name'] : $t['subdomain'];
            $bizUrl = base_url('marketplace/business/' . urlencode($t['subdomain']));
            $item = [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'item' => [
                    '@type' => 'LocalBusiness',
                    'name' => $displayName,
                    'url' => $bizUrl,
                    'image' => !empty($t['cover_image_url']) ? $t['cover_image_url'] : base_url('assets/img/logo.png'),
                    'description' => $t['short_description'] ?? 'BooKi randevu ve rezervasyon noktası.',
                ],
            ];
            if (!empty($t['city']) || !empty($t['district'])) {
                $item['item']['address'] = [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $t['city'] ?? null,
                    'addressRegion' => $t['district'] ?? null,
                    'addressCountry' => 'TR',
                ];
            }
            if ((int)$t['review_count'] > 0) {
                $item['item']['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float)$t['avg_rating'], 1),
                    'reviewCount' => (int)$t['review_count'],
                    'bestRating' => '5',
                ];
            }
            $json_ld['itemListElement'][] = $item;
        }

        // Popular category pills for quick discovery
        $popular_categories = [
            ['name' => 'Kuaför & Saç', 'icon' => 'fas fa-cut'],
            ['name' => 'Güzellik & Bakım', 'icon' => 'fas fa-spa'],
            ['name' => 'Masaj & Terapi', 'icon' => 'fas fa-hand-sparkles'],
            ['name' => 'Klinik & Sağlık', 'icon' => 'fas fa-stethoscope'],
            ['name' => 'Tırnak & Estetik', 'icon' => 'fas fa-paint-brush'],
            ['name' => 'Fitness & Antrenör', 'icon' => 'fas fa-dumbbell'],
            ['name' => 'Pet Kuaför', 'icon' => 'fas fa-paw'],
        ];

        html_vars([
            'page_title' => 'En İyi İşletmeleri Keşfedin & Online Randevu Alın — BooKi Marketplace',
            'meta_description' => 'Şehrinizdeki en iyi kuaför, berber, güzellik salonu, klinik ve randevulu hizmetleri keşfedin. Müşteri yorumlarını okuyun, fiyatları görün ve anında randevu alın.',
            'canonical_url' => $current_url,
            'json_ld' => $json_ld,
            'tenants' => $tenants,
            'categories' => $category_list,
            'cities' => $city_list,
            'districts' => $district_list,
            'popular_categories' => $popular_categories,
            'selected_q' => $q,
            'selected_category' => $category,
            'selected_city' => $city,
            'selected_district' => $district,
            'selected_sort' => $sort,
            'lat' => $lat,
            'lng' => $lng,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($total / $per_page)),
            'total' => $total,
            'per_page' => $per_page,
        ]);

        $this->load->view('pages/marketplace_index');
    }

    /**
     * Display a single business's marketplace profile with reviews, services and rich schema.
     */
    public function business(string $subdomain = ''): void
    {
        method('get');

        $subdomain = strtolower(trim($subdomain));

        if ($subdomain === '') {
            abort(404, 'Not Found');
        }

        $tenant = $this->db
            ->select(
                'tenants.*,' .
                '(SELECT COUNT(*) FROM ' . $this->db->dbprefix('reviews') .
                ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
                ' AND status = "published") AS review_count,' .
                '(SELECT AVG(rating) FROM ' . $this->db->dbprefix('reviews') .
                ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
                ' AND status = "published") AS avg_rating',
            )
            ->where('subdomain', $subdomain)
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->get('tenants')
            ->row_array();

        if (!$tenant) {
            abort(404, 'Not Found');
        }

        // Reviews (published)
        $reviews = $this->db
            ->where('id_tenants', $tenant['id'])
            ->where('status', 'published')
            ->order_by('created_at', 'desc')
            ->limit(50)
            ->get('reviews')
            ->result_array();

        // Rating distribution calculation
        $rating_dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($reviews as $rev) {
            $r = (int) $rev['rating'];
            if (isset($rating_dist[$r])) {
                $rating_dist[$r]++;
            }
        }

        // Booking URL with marketplace attribution
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
        $booking_url = 'https://' . $tenant['subdomain'] . '-' . $app_domain . '/booking';

        if (!empty($tenant['custom_domain'])) {
            $booking_url = 'https://' . $tenant['custom_domain'] . '/booking';
        }

        $booking_url .= (parse_url($booking_url, PHP_URL_QUERY) ? '&' : '?') . 'ref=marketplace';
        setcookie('booki_marketplace_ref', 'marketplace', time() + (86400 * 30), '/');

        // Fetch services preview from tenant's database
        $services = [];
        try {
            $tenant_db = $this->load->database([
                'hostname' => $tenant['db_host'],
                'username' => $tenant['db_username'],
                'password' => tenant_master_decrypt($tenant['db_password']),
                'database' => $tenant['db_name'],
                'dbdriver' => 'mysqli',
                'dbprefix' => 'ea_',
                'pconnect' => false,
                'db_debug' => false,
                'char_set' => 'utf8mb4',
                'dbcollat' => 'utf8mb4_unicode_ci',
            ], true);

            if ($tenant_db && $tenant_db->table_exists('services')) {
                $services = $tenant_db->select('s.id, s.name, s.duration, s.price, s.currency, s.description, sc.name AS category_name')
                    ->from('services s')
                    ->join('service_categories sc', 'sc.id = s.id_service_categories', 'left')
                    ->order_by('sc.name', 'asc')
                    ->order_by('s.name', 'asc')
                    ->limit(30)
                    ->get()
                    ->result_array();
            }
        } catch (Throwable $e) {
            // Silently fallback if tenant DB is not reachable
            $services = [];
        }

        $display_name = !empty($tenant['company_name']) ? $tenant['company_name'] : $tenant['subdomain'];
        $business_url = base_url('marketplace/business/' . urlencode($tenant['subdomain']));

        // Build Schema.org LocalBusiness / HealthAndBeautyBusiness JSON-LD
        $json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'HealthAndBeautyBusiness',
            'name' => $display_name,
            'image' => !empty($tenant['cover_image_url']) ? $tenant['cover_image_url'] : base_url('assets/img/logo.png'),
            'description' => $tenant['short_description'] ?? ($display_name . ' online randevu ve rezervasyon noktası.'),
            'url' => $business_url,
            'priceRange' => $tenant['price_range'] ?? '₺₺',
        ];

        if (!empty($tenant['phone_number'])) {
            $json_ld['telephone'] = $tenant['phone_number'];
        }

        if (!empty($tenant['address']) || !empty($tenant['city'])) {
            $json_ld['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $tenant['address'] ?? null,
                'addressLocality' => $tenant['city'] ?? null,
                'addressRegion' => $tenant['district'] ?? null,
                'addressCountry' => 'TR',
            ];
        }

        if (!empty($tenant['latitude']) && !empty($tenant['longitude'])) {
            $json_ld['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $tenant['latitude'],
                'longitude' => (float) $tenant['longitude'],
            ];
        }

        if ((int)$tenant['review_count'] > 0) {
            $json_ld['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float)$tenant['avg_rating'], 1),
                'reviewCount' => (int)$tenant['review_count'],
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        if (!empty($reviews)) {
            $json_ld['review'] = [];
            foreach (array_slice($reviews, 0, 10) as $rev) {
                $json_ld['review'][] = [
                    '@type' => 'Review',
                    'author' => ['@type' => 'Person', 'name' => $rev['customer_name']],
                    'datePublished' => date('Y-m-d', strtotime($rev['created_at'])),
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (int) $rev['rating'],
                    ],
                    'reviewBody' => $rev['comment'] ?? '',
                ];
            }
        }

        if (!empty($services)) {
            $json_ld['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => 'Hizmetler ve Fiyat Listesi',
                'itemListElement' => array_map(function ($s) {
                    return [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Service',
                            'name' => $s['name'],
                            'description' => $s['description'] ?? null,
                        ],
                        'price' => (float) ($s['price'] ?? 0),
                        'priceCurrency' => $s['currency'] ?? 'TRY',
                    ];
                }, array_slice($services, 0, 20)),
            ];
        }

        $json_ld['potentialAction'] = [
            '@type' => 'ReserveAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $booking_url,
                'inLanguage' => 'tr',
                'actionPlatform' => [
                    'http://schema.org/DesktopWebPlatform',
                    'http://schema.org/MobileWebPlatform',
                ],
            ],
            'result' => [
                '@type' => 'Reservation',
                'name' => 'Online Randevu',
            ],
        ];

        html_vars([
            'page_title' => $display_name . ' — Online Randevu & Değerlendirmeler | BooKi',
            'meta_description' => $tenant['short_description'] ?: ($display_name . ' için sunulan hizmetleri inceleyin, müşteri yorumlarını okuyun ve online randevu oluşturun.'),
            'canonical_url' => $business_url,
            'json_ld' => $json_ld,
            'tenant' => $tenant,
            'display_name' => $display_name,
            'reviews' => $reviews,
            'rating_dist' => $rating_dist,
            'services' => $services,
            'booking_url' => $booking_url,
        ]);

        $this->load->view('pages/marketplace_business');
    }

    /**
     * Submit a review for a tenant, verified against the tenant's own single-use token.
     */
    public function submit_review(): void
    {
        try {
            method('post');

            check('id_tenants', 'numeric');
            check('customer_name', 'string');
            check('rating', 'numeric');
            check('comment', 'string|null');
            check('source_appointment_hash', 'string');

            $tenant_id = (int) request('id_tenants');
            $customer_name = trim((string) request('customer_name'));
            $rating = (int) request('rating');
            $comment = trim((string) request('comment'));
            $token = trim((string) request('source_appointment_hash'));

            if ($token === '') {
                throw new InvalidArgumentException('Geçersiz değerlendirme bağlantısı.');
            }

            $tenant = $this->db
                ->get_where('tenants', ['id' => $tenant_id, 'marketplace_opt_in' => 1])
                ->row_array();

            if (!$tenant) {
                throw new InvalidArgumentException('İşletme marketplace\'te bulunamadı.');
            }

            if ($rating < 1 || $rating > 5) {
                throw new InvalidArgumentException('Derecelendirme 1-5 arasında olmalıdır.');
            }

            if ($customer_name === '' || strlen($customer_name) > 128) {
                throw new InvalidArgumentException('Geçerli bir müşteri adı girin.');
            }

            $claimed = $this->review_service->claim_in_tenant($tenant, $token);

            if (!$claimed) {
                throw new InvalidArgumentException('Bu değerlendirme bağlantısı geçersiz veya daha önce kullanılmış.');
            }

            $this->db->insert('reviews', [
                'id_tenants' => $tenant_id,
                'customer_name' => $customer_name,
                'customer_phone_hash' => $claimed['customer_phone_hash'] ?? null,
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
                'source_appointment_hash' => $token,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            json_response([
                'success' => true,
                'message' => 'Yorum başarıyla gönderildi. Yayınlanması için incelemeniz bekleniyor.',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Return JSON service list preview for an opted-in tenant.
     */
    public function services_preview(string $subdomain = ''): void
    {
        try {
            method('get');

            $subdomain = strtolower(trim($subdomain));
            if ($subdomain === '') {
                throw new InvalidArgumentException('Geçersiz işletme.');
            }

            $tenant = $this->db
                ->get_where('tenants', ['subdomain' => $subdomain, 'marketplace_opt_in' => 1, 'status' => 'active'])
                ->row_array();

            if (!$tenant) {
                throw new InvalidArgumentException('İşletme bulunamadı.');
            }

            $tenant_db = $this->load->database([
                'hostname' => $tenant['db_host'],
                'username' => $tenant['db_username'],
                'password' => tenant_master_decrypt($tenant['db_password']),
                'database' => $tenant['db_name'],
                'dbdriver' => 'mysqli',
                'dbprefix' => 'ea_',
                'pconnect' => false,
                'db_debug' => false,
                'char_set' => 'utf8mb4',
                'dbcollat' => 'utf8mb4_unicode_ci',
            ], true);

            $services = $tenant_db->select('s.id, s.name, s.duration, s.price, s.currency, s.description, sc.name AS category_name')
                ->from('services s')
                ->join('service_categories sc', 'sc.id = s.id_service_categories', 'left')
                ->order_by('sc.name', 'asc')
                ->order_by('s.name', 'asc')
                ->get()
                ->result_array();

            json_response(['success' => true, 'services' => $services]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Output dynamically generated XML Sitemap for SEO.
     */
    public function sitemap(): void
    {
        $tenants = $this->db
            ->select('subdomain, updated_at')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->order_by('updated_at', 'desc')
            ->get('tenants')
            ->result_array();

        $categories = $this->db
            ->distinct()
            ->select('category')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('category IS NOT NULL', null, false)
            ->get('tenants')
            ->result_array();

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Marketplace Home
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars(base_url('marketplace')) . '</loc>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        // Categories
        foreach ($categories as $c) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars(base_url('marketplace?category=' . urlencode($c['category']))) . '</loc>' . PHP_EOL;
            echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
            echo '    <priority>0.8</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        // Active Businesses
        foreach ($tenants as $t) {
            $lastmod = !empty($t['updated_at']) ? date('Y-m-d', strtotime($t['updated_at'])) : date('Y-m-d');
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars(base_url('marketplace/business/' . urlencode($t['subdomain']))) . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>daily</changefreq>' . PHP_EOL;
            echo '    <priority>0.9</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Output dynamically generated robots.txt friendly to search engines & AI crawlers (GEO).
     */
    public function robots(): void
    {
        $domain = getenv('MARKETPLACE_DOMAIN') ?: 'reservation.kibusiness.co';
        header('Content-Type: text/plain; charset=utf-8');
        echo "# BooKi Marketplace Robots.txt\n";
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Allow: /marketplace\n";
        echo "Allow: /marketplace/*\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /superadmin\n\n";

        echo "# AI Crawlers & Generative Engine Optimization (GEO)\n";
        $ai_bots = ['Googlebot', 'Bingbot', 'GPTBot', 'PerplexityBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot'];
        foreach ($ai_bots as $bot) {
            echo "User-agent: {$bot}\n";
            echo "Allow: /\n\n";
        }

        echo "Sitemap: https://{$domain}/sitemap.xml\n";
        exit;
    }

    /**
     * Output llms.txt standard document for LLMs & AI agents.
     */
    public function llms(): void
    {
        $tenants = $this->db
            ->select('subdomain, company_name, category, city, district, short_description')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->limit(100)
            ->get('tenants')
            ->result_array();

        $domain = getenv('MARKETPLACE_DOMAIN') ?: 'reservation.kibusiness.co';

        header('Content-Type: text/markdown; charset=utf-8');
        echo "# BooKi Platform & Marketplace\n\n";
        echo "> BooKi is an enterprise multi-tenant appointment scheduling and service discovery platform operating in Turkey.\n\n";
        echo "## Core URLs\n";
        echo "- Discovery Portal: https://{$domain}/marketplace\n";
        echo "- Sitemap: https://{$domain}/sitemap.xml\n";
        echo "- Service Preview API: https://{$domain}/marketplace/services_preview/{subdomain}\n\n";
        echo "## Listed Service Businesses\n\n";

        foreach ($tenants as $t) {
            $name = !empty($t['company_name']) ? $t['company_name'] : $t['subdomain'];
            $loc = array_filter([$t['district'] ?? null, $t['city'] ?? null]);
            $locStr = implode(', ', $loc);
            echo "### {$name} (" . ($t['category'] ?? 'Hizmet') . ")\n";
            if ($locStr !== '') {
                echo "- Location: {$locStr}\n";
            }
            if (!empty($t['short_description'])) {
                echo "- Description: {$t['short_description']}\n";
            }
            echo "- Profile & Booking: https://{$domain}/marketplace/business/{$t['subdomain']}\n\n";
        }

        exit;
    }
}
