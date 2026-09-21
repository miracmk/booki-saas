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
        $current_url = randevuburada_url();
        $json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'RandevuBurada — BooKi Hizmet & Randevu Pazaryeri',
            'description' => 'En iyi kuaför, berber, güzellik salonu, klinik ve uzmanları keşfedin, kolayca randevu alın.',
            'url' => $current_url,
            'numberOfItems' => count($tenants),
            'itemListElement' => [],
        ];

        foreach ($tenants as $idx => $t) {
            $displayName = !empty($t['company_name']) ? $t['company_name'] : $t['subdomain'];
            $bizUrl = randevuburada_url('business/' . urlencode($t['subdomain']));
            $item = [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'item' => [
                    '@type' => 'LocalBusiness',
                    'name' => $displayName,
                    'url' => $bizUrl,
                    'image' => !empty($t['cover_image_url']) ? $t['cover_image_url'] : base_url('assets/img/logo.png'),
                    'description' => $t['short_description'] ?? 'RandevuBurada randevu ve rezervasyon noktası.',
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
            if ((int) ($t['review_count'] ?? 0) > 0) {
                $item['item']['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float) $t['avg_rating'], 1),
                    'reviewCount' => (int) $t['review_count'],
                    'bestRating' => '5',
                ];
            }
            $json_ld['itemListElement'][] = $item;
        }

        // Popular category pills for quick discovery
        $popular_categories = [
            ['name' => 'Kuaför & Saç', 'icon' => 'fas fa-cut'],
            ['name' => 'Güzellik & Bakım', 'icon' => 'fas fa-spa'],
            ['name' => 'Tırnak & Estetik', 'icon' => 'fas fa-paint-brush'],
            ['name' => 'Berber & Erkek', 'icon' => 'fas fa-scissors'],
            ['name' => 'Masaj & Terapi', 'icon' => 'fas fa-hand-sparkles'],
            ['name' => 'Klinik & Sağlık', 'icon' => 'fas fa-stethoscope'],
            ['name' => 'Fitness & PT', 'icon' => 'fas fa-dumbbell'],
            ['name' => 'Oto Detailing', 'icon' => 'fas fa-car'],
        ];

        // Schema.org FAQPage for AI Citability & GEO Optimization
        $faq_json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'RandevuBurada üzerinden randevu almak ücretli mi?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Hayır, RandevuBurada üzerinden kuaför, berber, güzellik salonu ve klinik randevusu almak müşteriler için tamamen ücretsizdir. Yalnızca aldığınız hizmet bedelini ödersiniz.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'RandevuBurada\'dan doğrudan randevu nasıl oluşturulur?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Dilediğiniz işletmeyi seçip hizmet listesinden randevu al butonuna tıklayın; uzman, tarih ve saat dilimini seçerek saniyeler içinde anında onaylı randevunuzu tamamlayın.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'İşletmemi RandevuBurada vitrinine nasıl ekleyebilirim?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'BooKi salon yönetim sistemini kullanan tüm işletmeler, tek bir tıklamayla RandevuBurada pazaryeri vitrinine dahil olarak binlerce yeni müşteriye doğrudan erişebilir.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Ödeme nasıl yapılıyor ve güvenli mi?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Ödemeler SSL korumalı platform güvencesiyle ister online kredi kartıyla, ister salonda hizmet anında yapılabilir. RandevuBurada ve BooKi Hizmet Pazaryeri güvencesi tüm rezervasyonlarda geçerlidir.',
                    ],
                ],
            ],
        ];

        // Schema.org BreadcrumbList
        $breadcrumb_json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'BooKi Ana Sayfa',
                    'item' => booki_site_url(),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'RandevuBurada',
                    'item' => $current_url,
                ],
            ],
        ];

        html_vars([
            'page_title' => 'RandevuBurada — Türkiye\'nin Online Randevu ve Hizmet Pazaryeri | by BooKi',
            'meta_description' => 'Şehrinizdeki en iyi kuaför, berber, güzellik merkezi ve klinikleri keşfedin. Gerçek müşteri yorumlarını okuyun, anında fiyatları görün ve 7/24 randevunuzu RandevuBurada ile kolayca alın.',
            'canonical_url' => $current_url,
            'json_ld' => $json_ld,
            'faq_json_ld' => $faq_json_ld,
            'breadcrumb_json_ld' => $breadcrumb_json_ld,
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
        $cookie_domain = '';
        $host_parts = explode('.', $app_domain);
        if (count($host_parts) >= 2) {
            $cookie_domain = '.' . implode('.', array_slice($host_parts, -2));
        }
        setcookie('booki_marketplace_ref', 'marketplace', time() + (86400 * 30), '/', $cookie_domain);

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
        $business_url = randevuburada_url('business/' . urlencode($tenant['subdomain']));

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
            'page_title' => $display_name . ' — Online Randevu & Hizmetler | RandevuBurada by BooKi',
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
     * Connect to a specific tenant's database connection.
     */
    protected function connect_tenant_db(array $tenant)
    {
        return $this->load->database([
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
    }

    /**
     * Return real-time available time slots for a specific service and date on tenant's calendar.
     */
    public function get_slots(string $subdomain = ''): void
    {
        try {
            method('get');

            $subdomain = strtolower(trim($subdomain));
            check('service_id', 'numeric');
            check('date', 'string');

            $service_id = (int) request('service_id');
            $date = trim((string) request('date')); // Y-m-d

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new InvalidArgumentException('Geçersiz tarih formatı (YYYY-AA-GG bekleniyor).');
            }

            // Cannot book in the past
            $today = date('Y-m-d');
            if ($date < $today) {
                json_response(['success' => true, 'slots' => []]);
                return;
            }

            $tenant = $this->db
                ->get_where('tenants', ['subdomain' => $subdomain, 'marketplace_opt_in' => 1, 'status' => 'active'])
                ->row_array();

            if (!$tenant) {
                throw new InvalidArgumentException('İşletme bulunamadı veya pazar yerinde aktif değil.');
            }

            $tenant_db = $this->connect_tenant_db($tenant);
            if (!$tenant_db) {
                throw new RuntimeException('İşletme takvimine bağlanılamadı.');
            }

            $service = $tenant_db->get_where('services', ['id' => $service_id])->row_array();
            if (!$service) {
                throw new InvalidArgumentException('Seçilen hizmet bulunamadı.');
            }

            $duration = max(15, (int) ($service['duration'] ?? 30));

            // Determine working plan for the requested day
            $day_of_week = strtolower(date('l', strtotime($date)));
            $plan_setting = $tenant_db->get_where('settings', ['name' => 'company_working_plan'])->row_array();
            $plan = !empty($plan_setting['value']) ? json_decode($plan_setting['value'], true) : null;

            $start_time = '09:00';
            $end_time = '19:00';
            $breaks = [];
            $is_open = true;

            if ($plan && isset($plan[$day_of_week])) {
                $day_plan = $plan[$day_of_week];
                if (empty($day_plan) || empty($day_plan['start']) || empty($day_plan['end'])) {
                    $is_open = false;
                } else {
                    $start_time = $day_plan['start'];
                    $end_time = $day_plan['end'];
                    $breaks = $day_plan['breaks'] ?? [];
                }
            }

            if (!$is_open) {
                json_response(['success' => true, 'slots' => [], 'message' => 'İşletme seçilen günde kapalıdır.']);
                return;
            }

            // Fetch existing appointments on that date
            $existing_appts = $tenant_db->select('start_datetime, end_datetime, status')
                ->where('DATE(start_datetime)', $date)
                ->where('status !=', 'cancelled')
                ->get('appointments')
                ->result_array();

            // Generate slots
            $slots = [];
            $current = strtotime($date . ' ' . $start_time);
            $day_end = strtotime($date . ' ' . $end_time);
            $now = time();

            while (($current + ($duration * 60)) <= $day_end) {
                $slot_time = date('H:i', $current);

                // Skip past slots for today
                if ($date === $today && $current <= ($now + 1800)) { // 30 min buffer
                    $current += 1800; // 30 min increment
                    continue;
                }

                // Check breaks
                $in_break = false;
                foreach ($breaks as $brk) {
                    if (!empty($brk['start']) && !empty($brk['end'])) {
                        $brk_start = strtotime($date . ' ' . $brk['start']);
                        $brk_end = strtotime($date . ' ' . $brk['end']);
                        if ($current < $brk_end && ($current + ($duration * 60)) > $brk_start) {
                            $in_break = true;
                            break;
                        }
                    }
                }

                if (!$in_break) {
                    // Check conflicts with existing appointments
                    $conflict = false;
                    foreach ($existing_appts as $ea) {
                        $ea_start = strtotime($ea['start_datetime']);
                        $ea_end = strtotime($ea['end_datetime']);
                        if ($current < $ea_end && ($current + ($duration * 60)) > $ea_start) {
                            $conflict = true;
                            break;
                        }
                    }

                    if (!$conflict) {
                        $slots[] = $slot_time;
                    }
                }

                $current += 1800; // 30-minute interval step
            }

            // Fetch providers capable of this service for optional provider picker
            $providers = $tenant_db->select('u.id, u.first_name, u.last_name')
                ->from('users u')
                ->join('services_providers sp', 'sp.id_users = u.id')
                ->where('sp.id_services', $service_id)
                ->where('u.is_active', 1)
                ->get()
                ->result_array();

            json_response([
                'success' => true,
                'service' => [
                    'id' => $service['id'],
                    'name' => $service['name'],
                    'duration' => $duration,
                    'price' => (float) $service['price'],
                    'currency' => $service['currency'] ?? 'TRY',
                ],
                'slots' => $slots,
                'providers' => $providers,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create an appointment directly from the marketplace storefront, collect platform payment,
     * record sector-specific commission, and credit the tenant wallet.
     */
    public function create_booking(string $subdomain = ''): void
    {
        try {
            method('post');

            $subdomain = strtolower(trim($subdomain));
            check('service_id', 'numeric');
            check('date', 'string');
            check('time', 'string');
            check('first_name', 'string');
            check('last_name', 'string');
            check('phone_number', 'string');
            check('email', 'string|null');
            check('notes', 'string|null');
            check('provider_id', 'string|numeric|null');
            check('payment_method', 'string|null');

            $service_id = (int) request('service_id');
            $date = trim((string) request('date'));
            $time = trim((string) request('time'));
            $first_name = trim((string) request('first_name'));
            $last_name = trim((string) request('last_name'));
            $phone_number = trim((string) request('phone_number'));
            $email = trim((string) request('email'));
            $notes = trim((string) request('notes'));
            $provider_id = request('provider_id');
            $payment_method = trim((string) request('payment_method')) ?: 'online';

            if ($first_name === '' || $last_name === '' || $phone_number === '') {
                throw new InvalidArgumentException('Lütfen ad, soyad ve telefon numaranızı eksiksiz girin.');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                throw new InvalidArgumentException('Geçersiz tarih veya saat seçimi.');
            }

            $tenant = $this->db
                ->get_where('tenants', ['subdomain' => $subdomain, 'marketplace_opt_in' => 1, 'status' => 'active'])
                ->row_array();

            if (!$tenant) {
                throw new InvalidArgumentException('İşletme bulunamadı.');
            }

            $tenant_db = $this->connect_tenant_db($tenant);
            if (!$tenant_db) {
                throw new RuntimeException('İşletme veritabanına bağlanılamadı.');
            }

            $service = $tenant_db->get_where('services', ['id' => $service_id])->row_array();
            if (!$service) {
                throw new InvalidArgumentException('Hizmet bulunamadı.');
            }

            $duration = max(15, (int) ($service['duration'] ?? 30));
            $start_datetime = date('Y-m-d H:i:s', strtotime($date . ' ' . $time));
            $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " +{$duration} minutes"));

            // Resolve provider
            if (empty($provider_id) || $provider_id === 'any') {
                $sp_row = $tenant_db->select('sp.id_users')
                    ->from('services_providers sp')
                    ->join('users u', 'u.id = sp.id_users')
                    ->where('sp.id_services', $service_id)
                    ->where('u.is_active', 1)
                    ->limit(1)
                    ->get()
                    ->row_array();
                $resolved_provider_id = $sp_row ? (int) $sp_row['id_users'] : 1;
            } else {
                $resolved_provider_id = (int) $provider_id;
            }

            // Find or create customer in tenant DB
            $clean_phone = preg_replace('/[^\d+]/', '', $phone_number);
            $existing_cust = $tenant_db->where('phone_number', $clean_phone)
                ->or_where('phone_number', $phone_number)
                ->get('users')
                ->row_array();

            if ($existing_cust) {
                $customer_id = (int) $existing_cust['id'];
            } else {
                // Find customer role id
                $role_row = $tenant_db->get_where('roles', ['slug' => 'customer'])->row_array();
                $role_id = $role_row ? (int) $role_row['id'] : 3;

                $tenant_db->insert('users', [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email !== '' ? $email : null,
                    'phone_number' => $phone_number,
                    'id_roles' => $role_id,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $customer_id = (int) $tenant_db->insert_id();
            }

            // Insert Appointment in tenant DB
            $appt_hash = bin2hex(random_bytes(16));
            $pay_label = ($payment_method === 'online') ? 'Online Tahsilat' : 'Salonda Ödeme';
            $appointment_notes = "[Pazar Yeri - {$pay_label}] " . ($notes !== '' ? $notes : 'Müşteri BooKi Pazar Yeri üzerinden randevu oluşturdu.');

            $tenant_db->insert('appointments', [
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'id_services' => $service_id,
                'id_users_provider' => $resolved_provider_id,
                'id_users_customer' => $customer_id,
                'status' => 'confirmed',
                'is_unavailability' => 0,
                'notes' => $appointment_notes,
                'hash' => $appt_hash,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $appointment_id = (int) $tenant_db->insert_id();

            // Calculate Sectoral Commission & Update Master DB Wallet
            $price = (float) ($service['price'] ?? 0);
            $sector_key = $tenant['business_type'] ?? ($tenant['category'] ?? null);
            $commission_rate = get_sector_commission_rate($sector_key);
            $commission_amount = round($price * ($commission_rate / 100), 2);
            $net_amount = max(0, $price - $commission_amount);
            $currency = $service['currency'] ?? 'TRY';

            if ($this->db->table_exists('tenant_wallets')) {
                $this->db->query(
                    'INSERT INTO ' . $this->db->dbprefix('tenant_wallets') . ' ' .
                    '(id_tenants, balance, total_earned, total_commission, updated_at) ' .
                    'VALUES (?, ?, ?, ?, ?) ' .
                    'ON DUPLICATE KEY UPDATE balance = balance + ?, total_earned = total_earned + ?, total_commission = total_commission + ?, updated_at = ?',
                    [
                        $tenant['id'],
                        $net_amount, $price, $commission_amount, date('Y-m-d H:i:s'),
                        $net_amount, $price, $commission_amount, date('Y-m-d H:i:s'),
                    ]
                );
            }

            if ($this->db->table_exists('wallet_ledger')) {
                $ref_code = 'MP-' . date('ymd') . '-' . $appointment_id;
                $this->db->insert('wallet_ledger', [
                    'id_tenants' => $tenant['id'],
                    'type' => 'booking_earning',
                    'amount' => $price,
                    'currency' => $currency,
                    'reference_id' => $ref_code,
                    'description' => "Pazar Yeri Rezervasyon Geliri: {$service['name']} (#{$appointment_id})",
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                if ($commission_amount > 0) {
                    $this->db->insert('wallet_ledger', [
                        'id_tenants' => $tenant['id'],
                        'type' => 'commission_deduction',
                        'amount' => -$commission_amount,
                        'currency' => $currency,
                        'reference_id' => $ref_code,
                        'description' => "Marketplace Komisyonu (%{$commission_rate} - " . ($tenant['company_name'] ?? $tenant['subdomain']) . ")",
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $booking_ref = 'BK-' . strtoupper(substr(md5($appt_hash), 0, 8));

            json_response([
                'success' => true,
                'message' => 'Rezervasyonunuz başarıyla onaylandı!',
                'booking_ref' => $booking_ref,
                'appointment_id' => $appointment_id,
                'appointment_hash' => $appt_hash,
                'service_name' => $service['name'],
                'date_formatted' => date('d.m.Y', strtotime($date)),
                'time' => $time,
                'price' => $price,
                'currency' => $currency,
                'payment_method' => $payment_method,
                'tenant_name' => $tenant['company_name'] ?: $tenant['subdomain'],
            ]);
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

        // SaaS Landing Page
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars(booki_site_url()) . '</loc>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        // Marketplace Home
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars(randevuburada_url()) . '</loc>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        // BooKi Marketplace Path Alias
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars(booki_site_url('marketplace')) . '</loc>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>0.9</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        // Legal Pages
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars(randevuburada_url('privacy')) . '</loc>' . PHP_EOL;
        echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
        echo '    <priority>0.5</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars(randevuburada_url('terms')) . '</loc>' . PHP_EOL;
        echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
        echo '    <priority>0.5</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        // Categories
        foreach ($categories as $c) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars(randevuburada_url('?category=' . urlencode($c['category']))) . '</loc>' . PHP_EOL;
            echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
            echo '    <priority>0.8</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        // Active Businesses
        foreach ($tenants as $t) {
            $lastmod = !empty($t['updated_at']) ? date('Y-m-d', strtotime($t['updated_at'])) : date('Y-m-d');
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars(randevuburada_url('business/' . urlencode($t['subdomain']))) . '</loc>' . PHP_EOL;
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
        $domain = getenv('RANDEVUBURADA_DOMAIN') ?: 'randevuburada.kibusiness.co';
        header('Content-Type: text/plain; charset=utf-8');
        echo "# RandevuBurada (BooKi Hizmet Pazaryeri) Robots.txt\n";
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Allow: /business/*\n";
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

        $domain = getenv('RANDEVUBURADA_DOMAIN') ?: 'randevuburada.kibusiness.co';
        $booki_domain = getenv('MARKETPLACE_DOMAIN') ?: 'booki.kibusiness.co';

        header('Content-Type: text/markdown; charset=utf-8');
        echo "# RandevuBurada — BooKi Hizmet ve Randevu Pazaryeri\n\n";
        echo "> RandevuBurada, BooKi Hizmet Pazaryeridir. Türkiye genelindeki seçkin kuaför, berber, klinik ve güzellik salonlarından 7/24 anında online randevu alma olanağı sunar.\n\n";
        echo "## Temel Bağlantılar\n";
        echo "- RandevuBurada Pazaryeri: https://{$domain}/\n";
        echo "- BooKi Yazılım Platformu: https://{$booki_domain}/\n";
        echo "- BooKi Pazaryeri Girişi: https://{$booki_domain}/marketplace\n";
        echo "- XML Sitemap: https://{$domain}/sitemap.xml\n";
        echo "- Hizmet Önizleme API: https://{$domain}/services_preview/{subdomain}\n\n";
        echo "## Kayıtlı İşletmeler\n\n";

        foreach ($tenants as $t) {
            $name = !empty($t['company_name']) ? $t['company_name'] : $t['subdomain'];
            $loc = array_filter([$t['district'] ?? null, $t['city'] ?? null]);
            $locStr = implode(', ', $loc);
            echo "### {$name} (" . ($t['category'] ?? 'Hizmet') . ")\n";
            if ($locStr !== '') {
                echo "- Konum: {$locStr}\n";
            }
            if (!empty($t['short_description'])) {
                echo "- Açıklama: {$t['short_description']}\n";
            }
            echo "- Profil & Randevu: https://{$domain}/business/{$t['subdomain']}\n\n";
        }

        exit;
    }
}
