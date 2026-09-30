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
class Marketplace extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_multi_tenant_mode()) {
            abort(404, 'Not Found');
        }

        $this->load->helper('industry');
        $this->load->library('review_service');
        $this->load->library('whatsapp_bridge');
        $this->load->model('leads_model');
    }

    /**
     * Filter out demo / test / dummy tenants from marketplace discovery.
     * These tenants are reserved for customer demos.
     */
    protected function filter_dummy_tenants(): void
    {
        $this->db->not_like('subdomain', 'demo-', 'after');
        $this->db->not_like('subdomain', 'test-', 'after');
        $this->db->where_not_in('subdomain', ['qatest', 'waveaudit', 'test']);
        if ($this->db->field_exists('company_name', 'tenants')) {
            $this->db->not_like('company_name', 'Test');
            $this->db->not_like('company_name', 'Demo');
        }
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
            $this->filter_dummy_tenants();

            if ($q !== '') {
                $this->db->group_start();
                $this->db->like('subdomain', $q);
                if ($has_company_name) {
                    $this->db->or_like('company_name', $q);
                }
                $this->db->or_like('category', $q);
                $this->db->or_like('short_description', $q);
                $this->db->or_like('city', $q);
                $this->db->or_like('district', $q);
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
        $this->filter_dummy_tenants();
        $categories = $this->db
            ->distinct()
            ->select('category')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('category IS NOT NULL', null, false)
            ->order_by('category', 'asc')
            ->get('tenants')
            ->result_array();

        $this->filter_dummy_tenants();
        $cities = $this->db
            ->distinct()
            ->select('city')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('city IS NOT NULL', null, false)
            ->order_by('city', 'asc')
            ->get('tenants')
            ->result_array();

        $this->filter_dummy_tenants();
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
            $itemSchemaType = resolve_schema_org_type([
                'business_type' => $t['business_type'] ?? null,
                'category' => $t['category'] ?? null,
                'company_name' => $displayName,
            ]);
            $item = [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'item' => [
                    '@type' => $itemSchemaType,
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

        // Storefront leads: Only enriched or claimed members on homepage for high quality
        $storefront_leads = [];
        if ($this->db->table_exists('leads')) {
            $lead_filters = [
                'q' => $q,
                'category' => $category,
                'city' => $city,
                'district' => $district,
            ];
            $lead_data = $this->leads_model->get_for_storefront($per_page, ($page - 1) * $per_page, $lead_filters);
            $storefront_leads = $lead_data['leads'] ?? [];
        }

        html_vars([
            'page_title' => 'RandevuBurada — Türkiye\'nin Online Randevu ve Hizmet Pazaryeri | by BooKi',
            'meta_description' => 'Şehrinizdeki en iyi kuaför, berber, güzellik merkezi ve klinikleri keşfedin. Gerçek müşteri yorumlarını okuyun, anında fiyatları görün ve 7/24 randevunuzu RandevuBurada ile kolayca alın.',
            'canonical_url' => $current_url,
            'json_ld' => $json_ld,
            'faq_json_ld' => $faq_json_ld,
            'breadcrumb_json_ld' => $breadcrumb_json_ld,
            'tenants' => $tenants,
            'leads' => $storefront_leads,
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
            'total_pages' => max(1, (int) ceil(($total + count($storefront_leads)) / $per_page)),
            'total' => $total + count($storefront_leads),
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

        // Build Schema.org sector-specific JSON-LD across all 6 core sectors
        $schema_type = resolve_schema_org_type([
            'business_type' => $tenant['business_type'] ?? null,
            'category' => $tenant['category'] ?? null,
            'company_name' => $display_name,
        ]);

        $json_ld = [
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
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
        $this->filter_dummy_tenants();
        $tenants = $this->db
            ->select('subdomain, updated_at')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->order_by('updated_at', 'desc')
            ->get('tenants')
            ->result_array();

        $this->filter_dummy_tenants();
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
        echo '    <loc>' . htmlspecialchars(randevuburada_url()) . '</loc>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
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

        // Enriched Leads on Storefront
        $leads = $this->db
            ->select('slug, updated_at')
            ->group_start()
                ->where('enrichment_status', 'enriched_lead')
                ->or_where('membership_status', 'claimed_member')
            ->group_end()
            ->where('slug IS NOT NULL', null, false)
            ->order_by('updated_at', 'desc')
            ->get('leads')
            ->result_array();

        foreach ($leads as $l) {
            if (empty($l['slug'])) {
                continue;
            }
            $lastmod = !empty($l['updated_at']) ? date('Y-m-d', strtotime($l['updated_at'])) : date('Y-m-d');
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars(randevuburada_url('isletme/' . urlencode($l['slug']))) . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
            echo '    <priority>0.8</priority>' . PHP_EOL;
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
        echo "Allow: /isletme/*\n";
        echo "Allow: /kategori/*\n";
        echo "Allow: /sahiplen/*\n";
        echo "Allow: /marketplace\n";
        echo "Allow: /marketplace/*\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /admin/*\n";
        echo "Disallow: /superadmin\n";
        echo "Disallow: /superadmin/*\n";
        echo "Disallow: /backend\n";
        echo "Disallow: /backend/*\n";
        echo "Disallow: /account\n";
        echo "Disallow: /account/*\n";
        echo "Disallow: /customer_portal\n";
        echo "Disallow: /customer_portal/*\n";
        echo "Disallow: /portal\n";
        echo "Disallow: /portal/*\n";
        echo "Disallow: /api\n";
        echo "Disallow: /api/*\n\n";

        echo "# AI Crawlers & Generative Engine Optimization (GEO)\n";
        $ai_bots = ['Googlebot', 'Bingbot', 'GPTBot', 'PerplexityBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot'];
        foreach ($ai_bots as $bot) {
            echo "User-agent: {$bot}\n";
            echo "Allow: /\n\n";
        }

        echo "Sitemap: https://{$domain}/sitemap.xml\n";
        if ($this->db->table_exists('leads')) {
            $total_slugs = $this->leads_model->count_slugs();
            $chunks = max(1, (int) ceil($total_slugs / 5000));
            for ($c = 1; $c <= $chunks; $c++) {
                echo "Sitemap: https://{$domain}/sitemap-places-{$c}.xml\n";
            }
        }
        exit;
    }

    /**
     * Output llms.txt standard document for LLMs & AI agents.
     */
    public function llms(): void
    {
        $this->filter_dummy_tenants();
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

    // =========================================================================
    //  pSEO & Lead Storefront Methods
    // =========================================================================

    /**
     * Display a single lead's marketplace profile page with lazy enrichment.
     *
     * When a raw_lead is visited, Google Places Details API is called on-demand
     * to enrich the lead before serving the page to the user or Googlebot.
     *
     * URL: /isletme/{slug}
     */
    public function isletme(string $slug = ''): void
    {
        method('get');

        $slug = strtolower(trim($slug));

        if ($slug === '' || $slug === 'contact') {
            abort(404, 'Not Found');
        }

        $lead = $this->leads_model->get_by_slug($slug);

        if (!$lead) {
            abort(404, 'Not Found');
        }

        // If unpublished on marketplace and not admin preview / claimed, 404
        $is_admin = (bool) (session('superadmin_logged_in') || $this->input->get('preview') === '1');
        if (empty($lead['is_marketplace_published']) && !$is_admin && ($lead['membership_status'] ?? '') !== 'claimed_member') {
            abort(404, 'Not Found');
        }

        // Lazy Enrichment: if raw_lead, enrich on-demand via Google Places API
        if (($lead['enrichment_status'] ?? 'raw_lead') === 'raw_lead' && !empty($lead['place_id'])) {
            $this->load->library('lazy_enrichment');
            $lead = $this->lazy_enrichment->enrich_on_visit((int) $lead['id']) ?: $lead;
        }

        // If claimed_member and linked to a tenant, redirect to the tenant's BooKi profile
        if (($lead['membership_status'] ?? 'unclaimed') === 'claimed_member' && !empty($lead['claimed_tenant_id'])) {
            $tenant = $this->db->get_where('tenants', [
                'id' => $lead['claimed_tenant_id'],
                'status' => 'active',
            ])->row_array();

            if ($tenant) {
                redirect(randevuburada_url('business/' . urlencode($tenant['subdomain'])));
                return;
            }
        }

        // Parse photo references for proxy URLs
        $photos = [];
        if (!empty($lead['photo_references'])) {
            $refs = json_decode($lead['photo_references'], true);
            if (is_array($refs)) {
                foreach ($refs as $ref) {
                    $photos[] = randevuburada_url('api/places/photo?ref=' . urlencode($ref) . '&maxwidth=800');
                }
            }
        }

        // Parse opening hours
        $opening_hours = null;
        if (!empty($lead['opening_hours_json'])) {
            $opening_hours = json_decode($lead['opening_hours_json'], true);
        }

        // Parse reviews
        $google_reviews = [];
        if (!empty($lead['reviews_json'])) {
            $google_reviews = json_decode($lead['reviews_json'], true) ?: [];
        }

        $display_name = $lead['name'] ?? 'İşletme';
        $page_url = randevuburada_url('isletme/' . urlencode($slug));
        $city = $lead['city'] ?? '';
        $district = $lead['district'] ?? '';
        $neighborhood = $lead['neighborhood'] ?? '';

        // Build claim URL
        $claim_url = !empty($lead['claim_token'])
            ? randevuburada_url('sahiplen/' . urlencode($lead['claim_token']))
            : randevuburada_url();

        // Build dynamic SEO meta tags
        $location_parts = array_filter([$district, $city]);
        $location_str = implode(', ', $location_parts);

        $meta_title = $display_name;
        if ($location_str !== '') {
            $meta_title .= ' - ' . $location_str;
        }
        $meta_title .= ' Randevu & İletişim | RandevuBurada';

        $meta_desc = $display_name;
        if ($location_str !== '') {
            $meta_desc .= ' ' . $location_str . ' adresinde hizmet vermektedir.';
        }
        $meta_desc .= ' Çalışma saatleri, adres, fotoğraflar ve randevu talebi için tıklayın.';

        // Build Schema.org sector-specific JSON-LD across all 6 core sectors
        $schema_type = resolve_schema_org_type([
            'primary_type' => $lead['primary_type'] ?? null,
            'sector' => $lead['sector'] ?? null,
            'company_name' => $display_name,
        ]);

        $json_ld = [
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'name' => $display_name,
            'url' => $page_url,
            'description' => $meta_desc,
        ];

        // Images
        if (!empty($photos)) {
            $json_ld['image'] = $photos;
        } elseif (!empty($lead['cover_image_url'])) {
            $json_ld['image'] = $lead['cover_image_url'];
        }

        // Phone
        if (!empty($lead['phone'])) {
            $json_ld['telephone'] = $lead['phone'];
        }

        // Address
        $json_ld['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $lead['address'] ?? null,
            'addressLocality' => $district ?: null,
            'addressRegion' => $city ?: null,
            'addressCountry' => 'TR',
        ];

        // Geo coordinates
        if (!empty($lead['latitude']) && !empty($lead['longitude'])) {
            $json_ld['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lead['latitude'],
                'longitude' => (float) $lead['longitude'],
            ];
        }

        // Aggregate Rating
        if (!empty($lead['rating']) && (float) $lead['rating'] > 0) {
            $json_ld['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $lead['rating'], 1),
                'reviewCount' => (int) ($lead['user_rating_count'] ?? 1),
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        // Opening Hours Specification
        if ($opening_hours && !empty($opening_hours['periods'])) {
            $day_map = [
                0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
            ];
            $specs = [];
            foreach ($opening_hours['periods'] as $period) {
                if (!empty($period['open'])) {
                    $day_num = $period['open']['day'] ?? 0;
                    $open_time = sprintf('%02d:%02d', $period['open']['hour'] ?? 9, $period['open']['minute'] ?? 0);
                    $close_time = '23:59';
                    if (!empty($period['close'])) {
                        $close_time = sprintf('%02d:%02d', $period['close']['hour'] ?? 23, $period['close']['minute'] ?? 59);
                    }
                    $specs[] = [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => $day_map[$day_num] ?? 'Monday',
                        'opens' => $open_time,
                        'closes' => $close_time,
                    ];
                }
            }
            if (!empty($specs)) {
                $json_ld['openingHoursSpecification'] = $specs;
            }
        } elseif ($opening_hours && !empty($opening_hours['weekdayDescriptions'])) {
            // Use weekday text descriptions as fallback
            $json_ld['openingHours'] = $opening_hours['weekdayDescriptions'];
        }

        // Google Maps URL
        if (!empty($lead['google_maps_uri'])) {
            $json_ld['hasMap'] = $lead['google_maps_uri'];
        }

        // Breadcrumb JSON-LD
        $breadcrumb_items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'RandevuBurada', 'item' => randevuburada_url()],
        ];
        if ($city !== '') {
            $breadcrumb_items[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $city,
                'item' => randevuburada_url('kategori/' . urlencode(tr_slug($lead['sector'] ?? 'isletme')) . '/' . urlencode(tr_slug($city))),
            ];
        }
        if ($district !== '') {
            $breadcrumb_items[] = [
                '@type' => 'ListItem',
                'position' => count($breadcrumb_items) + 1,
                'name' => $district,
                'item' => randevuburada_url('kategori/' . urlencode(tr_slug($lead['sector'] ?? 'isletme')) . '/' . urlencode(tr_slug($city)) . '/' . urlencode(tr_slug($district))),
            ];
        }
        $breadcrumb_items[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumb_items) + 1,
            'name' => $display_name,
            'item' => $page_url,
        ];

        $breadcrumb_json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumb_items,
        ];

        html_vars([
            'page_title' => $meta_title,
            'meta_description' => $meta_desc,
            'canonical_url' => $page_url,
            'json_ld' => $json_ld,
            'breadcrumb_json_ld' => $breadcrumb_json_ld,
            'lead' => $lead,
            'display_name' => $display_name,
            'photos' => $photos,
            'opening_hours' => $opening_hours,
            'google_reviews' => $google_reviews,
            'claim_url' => $claim_url,
            'is_claimed' => ($lead['membership_status'] ?? 'unclaimed') === 'claimed_member',
        ]);

        $this->load->view('pages/marketplace_isletme');
    }

    /**
     * pSEO category / city / district directory pages.
     *
     * URL patterns:
     *   /kategori/{category-slug}
     *   /kategori/{category-slug}/{city}
     *   /kategori/{category-slug}/{city}/{district}
     */
    public function category(string $category_slug = '', string $city_slug = '', string $district_slug = ''): void
    {
        method('get');

        $page = max(1, (int) request('page', 1));
        $per_page = 24;

        // Map category slug back to display name
        $category_map = [
            'kuafor' => 'Kuaför', 'berber' => 'Berber', 'guzellik-salonu' => 'Güzellik Salonu',
            'tirnak' => 'Tırnak & Estetik', 'masaj' => 'Masaj & Terapi', 'klinik' => 'Klinik',
            'fitness' => 'Fitness', 'spa' => 'Spa & Hamam', 'dis-klinigi' => 'Diş Kliniği',
            'goz-klinigi' => 'Göz Kliniği', 'veteriner' => 'Veteriner', 'oto-yikama' => 'Oto Yıkama',
            'oto-detailing' => 'Oto Detailing', 'diyetisyen' => 'Diyetisyen', 'psikolog' => 'Psikolog',
            'fizyoterapi' => 'Fizyoterapi', 'pet-kuafor' => 'Pet Kuaför',
        ];

        $category_display = $category_map[$category_slug] ?? str_replace('-', ' ', ucfirst($category_slug));

        // Build filters
        $filters = [];
        if ($category_slug !== '') {
            $filters['category'] = $category_display;
        }
        if ($city_slug !== '') {
            // Reverse tr_slug is hard, so we do a LIKE search on city field
            $filters['city_slug'] = $city_slug;
        }
        if ($district_slug !== '') {
            $filters['district_slug'] = $district_slug;
        }

        // Query leads — directory shows published leads
        $this->db->where('is_marketplace_published', 1);
        $this->db->where('business_status', 'OPERATIONAL');

        if (!empty($filters['category'])) {
            $this->db->group_start()
                ->like('sector', $filters['category'])
                ->or_like('primary_type', $category_slug)
                ->or_like('matched_categories', $filters['category'])
                ->group_end();
        }

        if (!empty($filters['city_slug'])) {
            // Match slug-like city (e.g., 'istanbul' matches 'İstanbul')
            $this->db->group_start()
                ->like('LOWER(city)', str_replace('-', ' ', $city_slug))
                ->or_like('LOWER(city)', str_replace('-', '', $city_slug))
                ->group_end();
        }

        if (!empty($filters['district_slug'])) {
            $this->db->group_start()
                ->like('LOWER(district)', str_replace('-', ' ', $district_slug))
                ->or_like('LOWER(district)', str_replace('-', '', $district_slug))
                ->group_end();
        }

        $total = $this->db->count_all_results('leads');

        // Re-apply filters for data
        $this->db->where('is_marketplace_published', 1);
        $this->db->where('business_status', 'OPERATIONAL');
        if (!empty($filters['category'])) {
            $this->db->group_start()
                ->like('sector', $filters['category'])
                ->or_like('primary_type', $category_slug)
                ->or_like('matched_categories', $filters['category'])
                ->group_end();
        }
        if (!empty($filters['city_slug'])) {
            $this->db->group_start()
                ->like('LOWER(city)', str_replace('-', ' ', $city_slug))
                ->or_like('LOWER(city)', str_replace('-', '', $city_slug))
                ->group_end();
        }
        if (!empty($filters['district_slug'])) {
            $this->db->group_start()
                ->like('LOWER(district)', str_replace('-', ' ', $district_slug))
                ->or_like('LOWER(district)', str_replace('-', '', $district_slug))
                ->group_end();
        }

        $this->db->order_by("FIELD(enrichment_status, 'enriched_lead', 'raw_lead')", '', false);
        $this->db->order_by('rating', 'desc');

        $leads = $this->db
            ->limit($per_page, ($page - 1) * $per_page)
            ->get('leads')
            ->result_array();

        // Build SEO title
        $title_parts = [$category_display];
        $city_display = !empty($city_slug) ? ucfirst(str_replace('-', ' ', $city_slug)) : '';
        $district_display = !empty($district_slug) ? ucfirst(str_replace('-', ' ', $district_slug)) : '';

        if ($district_display !== '') {
            $title_parts[] = $district_display;
        }
        if ($city_display !== '') {
            $title_parts[] = $city_display;
        }

        $seo_title = implode(' ', $title_parts) . ' — Randevu & İletişim | RandevuBurada';
        $seo_desc = $category_display;
        if ($city_display !== '') {
            $seo_desc .= ' ' . $city_display;
        }
        if ($district_display !== '') {
            $seo_desc .= ' ' . $district_display;
        }
        $seo_desc .= ' bölgesindeki en iyi işletmeleri keşfedin. Çalışma saatleri, müşteri yorumları ve online randevu.';

        $canonical_parts = ['kategori', $category_slug];
        if ($city_slug !== '') $canonical_parts[] = $city_slug;
        if ($district_slug !== '') $canonical_parts[] = $district_slug;
        $canonical_url = randevuburada_url(implode('/', $canonical_parts));

        // Breadcrumb JSON-LD
        $breadcrumb_items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'RandevuBurada', 'item' => randevuburada_url()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $category_display, 'item' => randevuburada_url('kategori/' . urlencode($category_slug))],
        ];
        if ($city_slug !== '') {
            $breadcrumb_items[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $city_display, 'item' => randevuburada_url('kategori/' . urlencode($category_slug) . '/' . urlencode($city_slug))];
        }
        if ($district_slug !== '') {
            $breadcrumb_items[] = ['@type' => 'ListItem', 'position' => count($breadcrumb_items) + 1, 'name' => $district_display, 'item' => $canonical_url];
        }

        // ItemList JSON-LD for the directory
        $json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => implode(' ', $title_parts),
            'description' => $seo_desc,
            'url' => $canonical_url,
            'numberOfItems' => $total,
            'itemListElement' => [],
        ];

        foreach ($leads as $idx => $l) {
            $item_type = resolve_schema_org_type([
                'primary_type' => $l['primary_type'] ?? null,
                'sector' => $l['sector'] ?? null,
                'category' => $category_display,
                'company_name' => $l['name'] ?? '',
            ]);
            $item = [
                '@type' => 'ListItem',
                'position' => (($page - 1) * $per_page) + $idx + 1,
                'item' => [
                    '@type' => $item_type,
                    'name' => $l['name'] ?? 'İşletme',
                    'url' => !empty($l['slug']) ? randevuburada_url('isletme/' . urlencode($l['slug'])) : '#',
                ],
            ];
            if (!empty($l['address'])) {
                $item['item']['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $l['address'],
                    'addressLocality' => $l['district'] ?? null,
                    'addressRegion' => $l['city'] ?? null,
                    'addressCountry' => 'TR',
                ];
            }
            if (!empty($l['rating']) && (float) $l['rating'] > 0) {
                $item['item']['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float) $l['rating'], 1),
                    'reviewCount' => (int) ($l['user_rating_count'] ?? 1),
                ];
            }
            $json_ld['itemListElement'][] = $item;
        }

        $distinct_cities = $this->leads_model->get_distinct_cities();
        $city_list = array_values(array_filter(array_column($distinct_cities, 'city')));
        $district_list = [];
        if (!empty($city_display)) {
            $distinct_districts = $this->leads_model->get_neighborhoods($city_display);
            $district_list = array_values(array_filter(array_column($distinct_districts, 'neighborhood')));
        }

        html_vars([
            'page_title' => $seo_title,
            'meta_description' => $seo_desc,
            'canonical_url' => $canonical_url,
            'json_ld' => $json_ld,
            'breadcrumb_json_ld' => ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumb_items],
            'tenants' => [],
            'leads' => $leads,
            'cities' => $city_list,
            'districts' => $district_list,
            'selected_category' => $category_display,
            'selected_city' => $city_display,
            'selected_district' => $district_display,
            'category_display' => $category_display,
            'category_slug' => $category_slug,
            'city_display' => $city_display,
            'city_slug' => $city_slug,
            'district_display' => $district_display,
            'district_slug' => $district_slug,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($total / $per_page)),
            'total' => $total,
            'per_page' => $per_page,
        ]);

        $this->load->view('pages/marketplace_index');
    }

    /**
     * Handle contact / appointment request from unclaimed lead profile.
     *
     * Sends a WhatsApp message to the business via the Baileys Bridge,
     * notifying them that a customer wants to book.
     *
     * POST /isletme/contact
     */
    public function contact_request(): void
    {
        try {
            method('post');

            check('lead_id', 'numeric');
            check('customer_name', 'string');
            check('customer_phone', 'string');

            $lead_id = (int) request('lead_id');
            $customer_name = trim((string) request('customer_name'));
            $customer_phone = trim((string) request('customer_phone'));

            if ($customer_name === '' || $customer_phone === '') {
                throw new InvalidArgumentException('Lütfen adınızı ve telefon numaranızı girin.');
            }

            $lead = $this->db->get_where('leads', ['id' => $lead_id])->row_array();

            if (!$lead) {
                throw new InvalidArgumentException('İşletme bulunamadı.');
            }

            // Determine WhatsApp target number
            $wa_number = $lead['whatsapp_number'] ?: $lead['whatsapp'] ?: $lead['phone'] ?: '';
            $clean_wa = preg_replace('/[^0-9]/', '', $wa_number);
            if ($clean_wa !== '' && !str_starts_with($clean_wa, '90')) {
                $clean_wa = '90' . ltrim($clean_wa, '0');
            }

            // Build claim URL
            $claim_url = !empty($lead['claim_token'])
                ? randevuburada_url('sahiplen/' . urlencode($lead['claim_token']))
                : randevuburada_url();

            // WhatsApp message text (updated per user feedback)
            $message = "Merhaba, ben BooKi! 💈\n\n"
                . "Bir müşteriniz RandevuBurada sayfanız üzerinden sizinle iletişime geçmek ve randevu almak istedi.\n\n"
                . "👤 Müşteri: {$customer_name}\n"
                . "📞 İletişim: {$customer_phone}\n\n"
                . "Bu talepten başlayarak, rezervasyon süreçlerinizi otomatik hale getirmek, telefon trafiğinden kurtulmak, "
                . "3 katmanlı çakışma önleyici sistemimizle birlikte profesyonel portföyünüzü sitemizde sergilemek "
                . "için profilinizi hemen sahiplenin:\n"
                . "👉 {$claim_url}";

            $wa_sent = false;

            // Try to send via Baileys Bridge (Platform Admin's bridge)
            if ($clean_wa !== '') {
                try {
                    // Platform-level bridge config: master_settings → env → default.
                    // The 'platform' session is started from the superadmin CRM panel.
                    $bridge_url = master_setting('wa_bridge_url') ?: (getenv('WA_BRIDGE_URL') ?: 'http://wa-bridge:3000');
                    $bridge_secret = master_setting('wa_bridge_secret') ?: (getenv('WA_BRIDGE_SECRET') ?: '');

                    if ($bridge_url !== '') {
                        $bridge = new Whatsapp_bridge($bridge_url, $bridge_secret);
                        $result = $bridge->send('platform', $clean_wa, $message);
                        $wa_sent = !empty($result['success']);
                    }
                } catch (Throwable $e) {
                    log_message('error', 'Marketplace contact_request WhatsApp send failed: ' . $e->getMessage());
                }
            }

            // Log the contact request in lead_activities
            $this->leads_model->add_activity(
                $lead_id,
                'marketplace_contact',
                'RandevuBurada Randevu Talebi',
                "Müşteri: {$customer_name} ({$customer_phone})" . ($wa_sent ? ' — WhatsApp gönderildi ✓' : ' — WhatsApp gönderilemedi'),
                'RandevuBurada'
            );

            json_response([
                'success' => true,
                'message' => 'Talebiniz işletmeye iletildi! En kısa sürede sizinle iletişime geçeceklerdir.',
                'whatsapp_sent' => $wa_sent,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Start the profile claim (sahiplenme) process for an unclaimed lead.
     *
     * GET /sahiplen/{claim_token}
     */
    public function claim(string $token = ''): void
    {
        method('get');

        $token = trim($token);

        if ($token === '') {
            abort(404, 'Not Found');
        }

        $lead = $this->leads_model->get_by_claim_token($token);

        if (!$lead) {
            abort(404, 'Bu sahiplenme bağlantısı geçersiz veya süresi dolmuş.');
        }

        if (($lead['membership_status'] ?? 'unclaimed') === 'claimed_member') {
            // Already claimed — redirect to business profile
            if (!empty($lead['slug'])) {
                redirect(randevuburada_url('isletme/' . urlencode($lead['slug'])));
            } else {
                redirect(randevuburada_url());
            }
            return;
        }

        // Log the claim visit
        $this->leads_model->add_activity(
            (int) $lead['id'],
            'claim_visit',
            'Profil Sahiplenme Sayfası Ziyaret Edildi',
            'İşletme sahibi sahiplenme bağlantısını açtı.',
            'RandevuBurada'
        );

        $this->load->helper('tenant_master_crypto');
        $this->load->model('onboarding_sessions_model');

        // Check if an onboarding session already exists for this lead
        $session = $this->db
            ->where('id_leads', $lead['id'])
            ->where('status !=', 'completed')
            ->order_by('created_at', 'desc')
            ->limit(1)
            ->get('onboarding_sessions')
            ->row_array();

        if (!$session || strtotime($session['expires_at']) <= time()) {
            $tenant = $this->db->get_where('tenants', ['id_leads' => $lead['id']])->row_array();

            if (!$tenant) {
                // Generate clean, unique subdomain
                $base_sub = strtolower(preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lead['name'] ?? '')));
                if (empty($base_sub)) {
                    $base_sub = 'isletme' . $lead['id'];
                }
                $subdomain = $base_sub;
                $counter = 1;
                while ($this->db->get_where('tenants', ['subdomain' => $subdomain])->num_rows() > 0) {
                    $counter++;
                    $subdomain = $base_sub . $counter;
                }

                $now = date('Y-m-d H:i:s');
                $db_host = $this->db->hostname ?: 'db';
                $db_username = $this->db->username ?: 'ki_reservation_master';
                $db_password_plain = $this->db->password ?: '';
                $db_name = 'ki_tenant_' . $subdomain;

                $this->db->insert('tenants', [
                    'subdomain' => $subdomain,
                    'db_host' => $db_host,
                    'db_name' => $db_name,
                    'db_username' => $db_username,
                    'db_password' => function_exists('tenant_master_encrypt') ? tenant_master_encrypt($db_password_plain) : $db_password_plain,
                    'pii_enc_key' => function_exists('tenant_master_encrypt') ? tenant_master_encrypt(base64_encode(random_bytes(32))) : base64_encode(random_bytes(32)),
                    'pii_hash_key' => function_exists('tenant_master_encrypt') ? tenant_master_encrypt(base64_encode(random_bytes(32))) : base64_encode(random_bytes(32)),
                    'status' => 'active',
                    'plan' => 'Professional',
                    'business_type' => $lead['sector'] ?? 'Güzellik Salonu',
                    'billing_cycle' => 'monthly',
                    'mrr_amount' => 2450.00,
                    'currency' => 'TRY',
                    'company_name' => $lead['name'] ?? '',
                    'phone_number' => $lead['phone'] ?? '',
                    'address' => $lead['address'] ?? '',
                    'id_leads' => $lead['id'],
                    'acquisition_source' => 'RandevuBurada Claim',
                    'sales_owner' => 'RandevuBurada Organic',
                    'onboarding_status' => 'pending',
                    'trial_ends_at' => date('Y-m-d 23:59:59', strtotime('+7 days')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $tenant_id = (int) $this->db->insert_id();
            } else {
                $tenant_id = (int) $tenant['id'];
            }

            $session = $this->onboarding_sessions_model->create_session($tenant_id, (int) $lead['id']);
        }

        $onboarding_url = $this->onboarding_sessions_model->generate_onboarding_url($session['token']);

        // Redirect directly to the onboarding wizard
        if ($this->input->get('preview') !== '1') {
            redirect($onboarding_url);
            return;
        }

        // Preview mode: show claim landing page
        $display_name = $lead['name'] ?? 'İşletme';

        html_vars([
            'page_title' => $display_name . ' — Profilinizi Sahiplenin | RandevuBurada',
            'meta_description' => $display_name . ' profilini sahiplenin. BooKi ile randevu yönetimi, müşteri portföyü ve online rezervasyon.',
            'lead' => $lead,
            'display_name' => $display_name,
            'claim_token' => $token,
            'onboarding_url' => $onboarding_url,
        ]);

        $this->load->view('pages/marketplace_isletme');
    }

    /**
     * Chunked XML sitemap for lead directory pages (pSEO).
     *
     * /sitemap-places-{n}.xml — Each chunk contains up to 5000 URLs.
     */
    public function sitemap_places(int $chunk = 1): void
    {
        $per_chunk = 5000;
        $offset = ($chunk - 1) * $per_chunk;

        $leads = $this->leads_model->get_all_slugs($per_chunk, $offset);

        if (empty($leads) && $chunk > 1) {
            abort(404, 'Sitemap chunk not found');
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($leads as $l) {
            if (empty($l['slug'])) {
                continue;
            }
            $loc = randevuburada_url('isletme/' . urlencode($l['slug']));
            $lastmod = !empty($l['updated_at']) ? date('Y-m-d', strtotime($l['updated_at'])) : date('Y-m-d');
            $priority = ($l['enrichment_status'] ?? 'raw_lead') === 'enriched_lead' ? '0.8' : '0.6';

            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars($loc) . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
            echo '    <priority>' . $priority . '</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        echo '</urlset>';
        exit;
    }
}
