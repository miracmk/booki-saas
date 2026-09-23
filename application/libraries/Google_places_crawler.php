<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Google Places API (New) Prospect & Lead Crawler Library
 *
 * Implements Pro-tier Text Search discovery and Enterprise-tier Place Details
 * enrichment per Google Places API (New) specifications.
 * Supports district viewports and pin + radius circle restrictions.
 * -------------------------------------------------------------------------- */

class Google_places_crawler
{
    protected $CI;

    // FieldMask for Pro SKU discovery — NO phone, website, rating or reviews here!
    public const DISCOVERY_FIELD_MASK = 'places.id,places.displayName,places.primaryType,places.types,places.formattedAddress,places.location,places.businessStatus,places.googleMapsUri,nextPageToken';

    // FieldMask for on-demand Place Details enrichment
    public const ENRICHMENT_FIELD_MASK = 'id,displayName,nationalPhoneNumber,internationalPhoneNumber,websiteUri,rating,userRatingCount,regularOpeningHours,currentOpeningHours,priceLevel,photos,reviews,reservable,paymentOptions';

    // 17 Bursa Districts with actual bounding boxes (SouthWest / NorthEast)
    public const BURSA_REGIONS = [
        'nilufer' => [
            'id' => 'nilufer',
            'name' => 'Nilüfer',
            'center' => ['lat' => 40.2185, 'lng' => 28.9345],
            'viewport' => [
                'low' => ['latitude' => 40.1600, 'longitude' => 28.8200],
                'high' => ['latitude' => 40.2800, 'longitude' => 29.0200],
            ],
        ],
        'osmangazi' => [
            'id' => 'osmangazi',
            'name' => 'Osmangazi',
            'center' => ['lat' => 40.1983, 'lng' => 29.0560],
            'viewport' => [
                'low' => ['latitude' => 40.1500, 'longitude' => 28.9800],
                'high' => ['latitude' => 40.2900, 'longitude' => 29.1200],
            ],
        ],
        'yildirim' => [
            'id' => 'yildirim',
            'name' => 'Yıldırım',
            'center' => ['lat' => 40.1850, 'lng' => 29.1120],
            'viewport' => [
                'low' => ['latitude' => 40.1400, 'longitude' => 29.0600],
                'high' => ['latitude' => 40.2300, 'longitude' => 29.1900],
            ],
        ],
        'mudanya' => [
            'id' => 'mudanya',
            'name' => 'Mudanya',
            'center' => ['lat' => 40.3750, 'lng' => 28.8820],
            'viewport' => [
                'low' => ['latitude' => 40.3200, 'longitude' => 28.7800],
                'high' => ['latitude' => 40.4200, 'longitude' => 28.9800],
            ],
        ],
        'gemlik' => [
            'id' => 'gemlik',
            'name' => 'Gemlik',
            'center' => ['lat' => 40.4320, 'lng' => 29.1580],
            'viewport' => [
                'low' => ['latitude' => 40.3800, 'longitude' => 29.0500],
                'high' => ['latitude' => 40.5200, 'longitude' => 29.2800],
            ],
        ],
        'inegol' => [
            'id' => 'inegol',
            'name' => 'İnegöl',
            'center' => ['lat' => 40.0780, 'lng' => 29.5130],
            'viewport' => [
                'low' => ['latitude' => 40.0000, 'longitude' => 29.4000],
                'high' => ['latitude' => 40.1600, 'longitude' => 29.6200],
            ],
        ],
        'gursu' => [
            'id' => 'gursu',
            'name' => 'Gürsu',
            'center' => ['lat' => 40.2030, 'lng' => 29.1950],
            'viewport' => [
                'low' => ['latitude' => 40.1700, 'longitude' => 29.1500],
                'high' => ['latitude' => 40.2600, 'longitude' => 29.2700],
            ],
        ],
        'kestel' => [
            'id' => 'kestel',
            'name' => 'Kestel',
            'center' => ['lat' => 40.1950, 'lng' => 29.2150],
            'viewport' => [
                'low' => ['latitude' => 40.1500, 'longitude' => 29.1800],
                'high' => ['latitude' => 40.2500, 'longitude' => 29.3200],
            ],
        ],
        'orhangazi' => [
            'id' => 'orhangazi',
            'name' => 'Orhangazi',
            'center' => ['lat' => 40.4900, 'lng' => 29.3100],
            'viewport' => [
                'low' => ['latitude' => 40.4200, 'longitude' => 29.2000],
                'high' => ['latitude' => 40.5600, 'longitude' => 29.4200],
            ],
        ],
        'iznik' => [
            'id' => 'iznik',
            'name' => 'İznik',
            'center' => ['lat' => 40.4280, 'lng' => 29.7210],
            'viewport' => [
                'low' => ['latitude' => 40.3500, 'longitude' => 29.6000],
                'high' => ['latitude' => 40.5000, 'longitude' => 29.8500],
            ],
        ],
        'karacabey' => [
            'id' => 'karacabey',
            'name' => 'Karacabey',
            'center' => ['lat' => 40.2150, 'lng' => 28.3600],
            'viewport' => [
                'low' => ['latitude' => 40.1200, 'longitude' => 28.2500],
                'high' => ['latitude' => 40.3200, 'longitude' => 28.4800],
            ],
        ],
        'mkpasa' => [
            'id' => 'mkpasa',
            'name' => 'Mustafakemalpaşa',
            'center' => ['lat' => 40.0350, 'lng' => 28.4100],
            'viewport' => [
                'low' => ['latitude' => 39.9500, 'longitude' => 28.3000],
                'high' => ['latitude' => 40.1200, 'longitude' => 28.5200],
            ],
        ],
        'yenisehir' => [
            'id' => 'yenisehir',
            'name' => 'Yenişehir',
            'center' => ['lat' => 40.2630, 'lng' => 29.6530],
            'viewport' => [
                'low' => ['latitude' => 40.1800, 'longitude' => 29.5500],
                'high' => ['latitude' => 40.3500, 'longitude' => 29.7800],
            ],
        ],
    ];

    // Comprehensive BooKi Taxonomy with Query Variations & Google Table A Types
    public const TAXONOMY = [
        'beauty_salon' => [
            'slug' => 'beauty_salon',
            'label' => 'Güzellik Salonu & Estetik',
            'icon' => '💄',
            'sector' => 'Güzellik Salonu',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
            'googleIncludedType' => 'beauty_salon',
            'queries' => ['güzellik salonu', 'güzellik merkezi', 'cilt bakımı ve estetik', 'beauty salon'],
        ],
        'hair_salon' => [
            'slug' => 'hair_salon',
            'label' => 'Bayan Kuaförü & Saç Tasarım',
            'icon' => '💇‍♀️',
            'sector' => 'Kuaför',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
            'googleIncludedType' => 'hair_salon',
            'queries' => ['bayan kuaförü', 'kadın kuaför', 'saç tasarım salonu', 'hair salon'],
        ],
        'barber_shop' => [
            'slug' => 'barber_shop',
            'label' => 'Erkek Kuaförü & Berber',
            'icon' => '💈',
            'sector' => 'Berber',
            'package' => 'Starter',
            'potential_mrr' => 1999.00,
            'googleIncludedType' => 'barber_shop',
            'queries' => ['erkek kuaförü', 'berber', 'men hair salon', 'barber'],
        ],
        'nail_salon' => [
            'slug' => 'nail_salon',
            'label' => 'Tırnak Stüdyosu & Nail Art',
            'icon' => '💅',
            'sector' => 'Nail Studio',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
            'googleIncludedType' => 'nail_salon',
            'queries' => ['nail art stüdyo', 'tırnak bakım merkezi', 'protez tırnak', 'nail salon'],
        ],
        'spa_massage' => [
            'slug' => 'spa_massage',
            'label' => 'Masaj & Spa & Terapi',
            'icon' => '💆‍♀️',
            'sector' => 'Masaj Salonu',
            'package' => 'Enterprise',
            'potential_mrr' => 4499.00,
            'googleIncludedType' => 'spa',
            'queries' => ['masaj salonu', 'spa merkezi', 'masaj ve terapi', 'wellness spa'],
        ],
        'dental_clinic' => [
            'slug' => 'dental_clinic',
            'label' => 'Diş Kliniği & Hekimi',
            'icon' => '🦷',
            'sector' => 'Klinik',
            'package' => 'Enterprise',
            'potential_mrr' => 4499.00,
            'googleIncludedType' => 'dental_clinic',
            'queries' => ['diş kliniği', 'diş hekimi muayenehanesi', 'ağız ve diş sağlığı polikliniği'],
        ],
        'medical_clinic' => [
            'slug' => 'medical_clinic',
            'label' => 'Özel Klinik & Poliklinik',
            'icon' => '🏥',
            'sector' => 'Klinik',
            'package' => 'Enterprise',
            'potential_mrr' => 4499.00,
            'googleIncludedType' => 'medical_clinic',
            'queries' => ['doktor muayenehanesi', 'özel poliklinik', 'dermatoloji kliniği', 'estetik cerrahi kliniği'],
        ],
        'physiotherapy' => [
            'slug' => 'physiotherapy',
            'label' => 'Fizyoterapi & Manuel Terapi',
            'icon' => '🦴',
            'sector' => 'Klinik',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
            'googleIncludedType' => 'physiotherapist',
            'queries' => ['fizyoterapi merkezi', 'manuel terapi', 'fizik tedavi kliniği'],
        ],
        'dietitian' => [
            'slug' => 'dietitian',
            'label' => 'Diyetisyen & Beslenme',
            'icon' => '🥗',
            'sector' => 'Diyetisyen',
            'package' => 'Starter',
            'potential_mrr' => 1999.00,
            'googleIncludedType' => null,
            'queries' => ['diyetisyen', 'beslenme ve diyet danışmanlığı', 'sağlıklı beslenme merkezi'],
        ],
        'psychologist' => [
            'slug' => 'psychologist',
            'label' => 'Psikolog & Danışmanlık',
            'icon' => '🧠',
            'sector' => 'Danışmanlık',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
            'googleIncludedType' => null,
            'queries' => ['psikolojik danışmanlık merkezi', 'klinik psikolog', 'aile ve evlilik danışmanı'],
        ],
        'pilates_yoga' => [
            'slug' => 'pilates_yoga',
            'label' => 'Pilates & Yoga & Fitness',
            'icon' => '🧘',
            'sector' => 'Pilates/Yoga',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
            'googleIncludedType' => 'gym',
            'queries' => ['reformer pilates stüdyosu', 'yoga merkezi', 'aletli pilates', 'pilates studio'],
        ],
        'tattoo_piercing' => [
            'slug' => 'tattoo_piercing',
            'label' => 'Dövme & Piercing',
            'icon' => '🎨',
            'sector' => 'Dövme Stüdyosu',
            'package' => 'Starter',
            'potential_mrr' => 1999.00,
            'googleIncludedType' => null,
            'queries' => ['dövme stüdyosu', 'tattoo shop', 'piercing stüdyo'],
        ],
        'veterinary' => [
            'slug' => 'veterinary',
            'label' => 'Veteriner & Pet Kuaför',
            'icon' => '🐾',
            'sector' => 'Veteriner',
            'package' => 'Professional',
            'potential_mrr' => 2199.00,
            'googleIncludedType' => 'veterinary_care',
            'queries' => ['veteriner kliniği', 'pet kuaför', 'hayvan hastanesi'],
        ],
        'photography' => [
            'slug' => 'photography',
            'label' => 'Fotoğraf & Çekim Stüdyosu',
            'icon' => '📸',
            'sector' => 'Fotoğraf Stüdyosu',
            'package' => 'Starter',
            'potential_mrr' => 1999.00,
            'googleIncludedType' => null,
            'queries' => ['fotoğraf stüdyosu', 'düğün fotoğrafçısı', 'bebek ve doğum fotoğraf stüdyosu'],
        ],
        'restaurant_cafe' => [
            'slug' => 'restaurant_cafe',
            'label' => 'Restoran & Bistro & Cafe',
            'icon' => '🍽️',
            'sector' => 'Restoran/Cafe',
            'package' => 'Enterprise',
            'potential_mrr' => 4499.00,
            'googleIncludedType' => 'restaurant',
            'queries' => ['restoran', 'bistro & cafe', 'özel yemek restoranı', 'et lokantası'],
        ],
    ];

    /**
     * Intelligent Turkish Business Name Normalization
     */
    public static function normalize_business_name(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        // Clean redundant multiple whitespace
        $name = preg_replace('/\s+/u', ' ', $name);

        // Check if string is shout-case (all upper) or all lower
        $is_all_upper = (mb_strtoupper($name, 'UTF-8') === $name);
        $is_all_lower = (mb_strtolower($name, 'UTF-8') === $name);

        if ($is_all_upper || $is_all_lower) {
            // Convert to Turkish lowercase first
            $lower_str = mb_strtolower(strtr($name, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');

            // Split by word boundaries
            $words = preg_split('/([\s\-\/\&\(\)\.\,]+)/u', $lower_str, -1, PREG_SPLIT_DELIM_CAPTURE);
            $result = '';
            foreach ($words as $word) {
                if ($word === '') continue;
                if (preg_match('/^[\s\-\/\&\(\)\.\,]+$/u', $word)) {
                    $result .= $word;
                    continue;
                }

                $first = mb_substr($word, 0, 1, 'UTF-8');
                $rest = mb_substr($word, 1, null, 'UTF-8');

                if ($first === 'i') {
                    $first_upper = 'İ';
                } elseif ($first === 'ı') {
                    $first_upper = 'I';
                } else {
                    $first_upper = mb_strtoupper($first, 'UTF-8');
                }

                $result .= $first_upper . $rest;
            }
            $name = $result;
        }

        // Handle known acronyms and small joining words in Turkish
        $replacements = [
            '/\bVip\b/u' => 'VIP',
            '/\bSpa\b/u' => 'SPA',
            '/\bAvm\b/u' => 'AVM',
            '/\bPt\b/u' => 'PT',
            '/\bDr\.\b/u' => 'Dr.',
            '/\bDt\.\b/u' => 'Dt.',
            '/\bG\&n\b/u' => 'G&N',
            '/\bG\&N\b/u' => 'G&N',
            '/\bVe\b/u' => 've',
            '/\bIle\b/u' => 'ile',
        ];
        foreach ($replacements as $pattern => $replacement) {
            $name = preg_replace($pattern, $replacement, $name);
        }

        return trim($name);
    }

    public function __construct()
    {
        if (function_exists('get_instance')) {
            $this->CI =& get_instance();
            $this->CI->load->database();
        }
    }

    /**
     * Get configured Google Maps / Places API Key.
     */
    public function get_api_key(): string
    {
        $key = '';
        if (function_exists('master_setting')) {
            $key = (string) master_setting('google_maps_key');
        }
        if (empty($key)) {
            $key = (string) (getenv('GOOGLE_PLACES_API_KEY') ?: 'AIzaSyAscIARfxTG_KzedaskCabzuRSTj-0bulA');
        }
        return $key;
    }

    /**
     * Preview queries and scope for a discovery run.
     */
    public function preview_crawl(array $category_slugs, string $region_mode = 'districts', array $region_data = [], string $mode = 'standard'): array
    {
        $queries = [];
        $categories = [];

        foreach ($category_slugs as $slug) {
            if (isset(self::TAXONOMY[$slug])) {
                $tax = self::TAXONOMY[$slug];
                $categories[] = $tax;
                $cat_queries = $mode === 'deep' ? $tax['queries'] : array_slice($tax['queries'], 0, 2);
                foreach ($cat_queries as $q) {
                    $queries[] = [
                        'category_slug' => $slug,
                        'category_name' => $tax['label'],
                        'query' => $q,
                        'included_type' => $tax['googleIncludedType'],
                    ];
                }
            }
        }

        $region_count = 1;
        $region_label = 'Tüm Bursa';

        if ($region_mode === 'districts' && !empty($region_data['districts'])) {
            $region_count = count($region_data['districts']);
            $names = [];
            foreach ($region_data['districts'] as $d) {
                if (isset(self::BURSA_REGIONS[$d])) {
                    $names[] = self::BURSA_REGIONS[$d]['name'];
                }
            }
            $region_label = implode(', ', $names);
        } elseif ($region_mode === 'pin_radius') {
            $radius_km = round(($region_data['radius_meters'] ?? 5000) / 1000, 1);
            $region_label = "Özel Pin Çevresi ({$radius_km} km)";
        }

        $total_searches = count($queries) * $region_count;
        $estimated_prospects = $total_searches * 15; // Realistic estimated average per query

        return [
            'total_categories' => count($categories),
            'total_queries' => count($queries),
            'region_count' => $region_count,
            'region_label' => $region_label,
            'total_api_calls' => $total_searches,
            'estimated_prospects' => $estimated_prospects,
            'queries' => $queries,
            'sku_tier' => 'Pro (Text Search)',
        ];
    }

    /**
     * Execute one Text Search (New) request.
     */
    public function search_text(string $query, array $location_restriction = [], ?string $included_type = null, ?string $page_token = null, bool $use_bias = false): array
    {
        $api_key = $this->get_api_key();
        $url = 'https://places.googleapis.com/v1/places:searchText';

        $payload = [
            'textQuery' => $query,
            'languageCode' => 'tr',
            'regionCode' => 'TR',
            'pageSize' => 20,
        ];

        if ($use_bias) {
            if (!empty($location_restriction['rectangle'])) {
                $payload['locationBias'] = ['rectangle' => $location_restriction['rectangle']];
            } elseif (!empty($location_restriction['circle'])) {
                $payload['locationBias'] = ['circle' => $location_restriction['circle']];
            }
        } else {
            if (!empty($location_restriction['rectangle'])) {
                $payload['locationRestriction'] = ['rectangle' => $location_restriction['rectangle']];
            } elseif (!empty($location_restriction['circle'])) {
                $payload['locationRestriction'] = ['circle' => $location_restriction['circle']];
            }
        }

        if (!empty($included_type)) {
            $payload['includedType'] = $included_type;
            $payload['strictTypeFiltering'] = false;
        }

        if (!empty($page_token)) {
            $payload['pageToken'] = $page_token;
        }

        $headers = [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $api_key,
            'X-Goog-FieldMask: ' . self::DISCOVERY_FIELD_MASK,
        ];

        $start_time = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $exec_time = (int) round((microtime(true) - $start_time) * 1000);
        curl_close($ch);

        $json = json_decode($response ?: '', true) ?: [];

        // Log usage
        $this->log_api_usage('https://places.googleapis.com/v1/places:searchText', 'text_search', $http_code, $exec_time, 'Pro', [
            'query' => $query,
            'results_count' => count($json['places'] ?? []),
            'has_next_page' => !empty($json['nextPageToken']),
        ]);

        $is_success = ($http_code >= 200 && $http_code < 300);
        $err_msg = null;
        if (!$is_success) {
            $err_msg = isset($json['error']['message'])
                ? $json['error']['message']
                : (is_string($json['error'] ?? null) ? $json['error'] : "Google Places API Hatası (HTTP {$http_code})");
        }

        return [
            'success' => $is_success,
            'http_code' => $http_code,
            'places' => $json['places'] ?? [],
            'next_page_token' => $json['nextPageToken'] ?? null,
            'error' => $err_msg,
        ];
    }

    /**
     * Process a crawl job synchronously or step-by-step.
     */
    public function execute_crawl_step(int $job_id, int $max_queries_per_step = 2): array
    {
        $job = $this->CI->db->get_where('crawl_jobs', ['id' => $job_id])->row_array();
        if (!$job || in_array($job['status'], ['COMPLETED', 'FAILED', 'CANCELLED'], true)) {
            return ['done' => true, 'job' => $job];
        }

        if ($job['status'] === 'QUEUED') {
            $this->CI->db->where('id', $job_id)->update('crawl_jobs', [
                'status' => 'RUNNING',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $queries_list = json_decode($job['search_queries_json'] ?: '[]', true);
        $region_mode = $job['region_mode'];
        $region_data = json_decode($job['region_data_json'] ?: '[]', true);

        // Build target regions list
        $regions = [];
        if ($region_mode === 'districts' && !empty($region_data['districts'])) {
            foreach ($region_data['districts'] as $d_slug) {
                if (isset(self::BURSA_REGIONS[$d_slug])) {
                    $regions[] = self::BURSA_REGIONS[$d_slug];
                }
            }
        } elseif ($region_mode === 'pin_radius' && !empty($region_data['center'])) {
            $regions[] = [
                'id' => 'custom_circle',
                'name' => $region_data['name'] ?? 'Özel Pin Bölgesi',
                'circle' => [
                    'center' => [
                        'latitude' => (float) $region_data['center']['lat'],
                        'longitude' => (float) $region_data['center']['lng'],
                    ],
                    'radius' => (float) ($region_data['radius_meters'] ?? 5000),
                ],
            ];
        } else {
            // Default to central Nilufer + Osmangazi
            $regions[] = self::BURSA_REGIONS['nilufer'];
            $regions[] = self::BURSA_REGIONS['osmangazi'];
        }

        // Build execution matrix: query x region
        $execution_matrix = [];
        foreach ($queries_list as $q) {
            foreach ($regions as $r) {
                $execution_matrix[] = [
                    'query' => $q['query'],
                    'category_slug' => $q['category_slug'],
                    'category_name' => $q['category_name'],
                    'included_type' => $q['included_type'],
                    'region' => $r,
                ];
            }
        }

        $completed_idx = (int) $job['completed_queries'];
        $total_matrix_count = count($execution_matrix);

        if ($completed_idx >= $total_matrix_count) {
            $this->CI->db->where('id', $job_id)->update('crawl_jobs', [
                'status' => 'COMPLETED',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            $job['status'] = 'COMPLETED';
            return ['done' => true, 'job' => $job];
        }

        $new_leads = (int) $job['new_leads'];
        $updated_leads = (int) $job['updated_leads'];
        $filtered_closed = (int) $job['filtered_closed'];
        $results_found = (int) $job['results_found'];
        $pages_requested = (int) $job['pages_requested'];

        $queries_run = 0;
        while ($completed_idx < $total_matrix_count && $queries_run < $max_queries_per_step) {
            $item = $execution_matrix[$completed_idx];
            $location_restriction = [];
            if (!empty($item['region']['viewport'])) {
                $location_restriction['rectangle'] = $item['region']['viewport'];
            } elseif (!empty($item['region']['circle'])) {
                $location_restriction['circle'] = $item['region']['circle'];
            }

            $page_token = null;
            $page_num = 0;

            // Follow up to 3 pages (max 60 results) per query per guidelines
            do {
                $page_num++;
                $pages_requested++;

                $res = $this->search_text(
                    $item['query'],
                    $location_restriction,
                    $item['included_type'],
                    $page_token
                );

                if (!empty($res['places'])) {
                    foreach ($res['places'] as $place) {
                        $results_found++;
                        $proc = $this->upsert_place_lead($place, $item['category_slug'], $item['query'], $item['region']['name']);
                        if ($proc['action'] === 'new') {
                            $new_leads++;
                        } elseif ($proc['action'] === 'updated') {
                            $updated_leads++;
                        } elseif ($proc['action'] === 'closed') {
                            $filtered_closed++;
                        }
                    }
                }

                $page_token = $res['next_page_token'] ?? null;
                if ($page_token) {
                    usleep(100000); // 100ms throttle between pages
                }
            } while ($page_token && $page_num < 3);

            $completed_idx++;
            $queries_run++;
        }

        $is_done = ($completed_idx >= $total_matrix_count);

        $this->CI->db->where('id', $job_id)->update('crawl_jobs', [
            'status' => $is_done ? 'COMPLETED' : 'RUNNING',
            'completed_queries' => $completed_idx,
            'pages_requested' => $pages_requested,
            'results_found' => $results_found,
            'new_leads' => $new_leads,
            'updated_leads' => $updated_leads,
            'filtered_closed' => $filtered_closed,
            'completed_at' => $is_done ? date('Y-m-d H:i:s') : null,
        ]);

        $job = $this->CI->db->get_where('crawl_jobs', ['id' => $job_id])->row_array();
        return ['done' => $is_done, 'job' => $job];
    }

    /**
     * Upsert a Place into the master `leads` table with deduplication on `place_id`.
     */
    public function upsert_place_lead(array $place, string $category_slug, string $query, string $region_name): array
    {
        $db = $this->CI->db;
        $place_id = $place['id'] ?? '';
        if (!$place_id) return ['action' => 'skip'];
        $raw_name = $place['displayName']['text'] ?? 'İsimsiz İşletme';
        $display_name = self::normalize_business_name($raw_name);
        $formatted_address = $place['formattedAddress'] ?? '';
        $primary_type = $place['primaryType'] ?? '';
        $types = $place['types'] ?? [];
        $business_status = $place['businessStatus'] ?? 'OPERATIONAL';
        $google_maps_uri = $place['googleMapsUri'] ?? '';
        $location = $place['location'] ?? [];
        $lat = isset($location['latitude']) ? (float) $location['latitude'] : null;
        $lng = isset($location['longitude']) ? (float) $location['longitude'] : null;

        $taxonomy_meta = self::TAXONOMY[$category_slug] ?? null;
        $sector = $taxonomy_meta['sector'] ?? 'Güzellik Salonu';
        $potential_mrr = $taxonomy_meta['potential_mrr'] ?? 2199.00;
        $package = $taxonomy_meta['package'] ?? 'Professional';

        // Deduplication check
        $existing = $db->get_where('leads', ['place_id' => $place_id])->row_array();

        if ($existing) {
            $cats = array_filter(explode(',', (string) $existing['matched_categories']));
            if (!in_array($category_slug, $cats, true)) $cats[] = $category_slug;

            $queries = array_filter(explode('|', (string) $existing['matched_queries']));
            if (!in_array($query, $queries, true)) $queries[] = $query;

            $regs = array_filter(explode(',', (string) $existing['matched_regions']));
            if (!in_array($region_name, $regs, true)) $regs[] = $region_name;

            $db->where('id', $existing['id'])->update('leads', [
                'business_status' => $business_status,
                'matched_categories' => implode(',', $cats),
                'matched_queries' => implode('|', $queries),
                'matched_regions' => implode(',', $regs),
                'last_seen_at' => date('Y-m-d H:i:s'),
                'last_crawled_at' => date('Y-m-d H:i:s'),
                'discovery_count' => (int) $existing['discovery_count'] + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return ['action' => ($business_status !== 'OPERATIONAL') ? 'closed' : 'updated', 'lead_id' => $existing['id']];
        }

        $district = $region_name;
        if ($district === 'Özel Pin Bölgesi' || empty($district) || $district === 'Bursa') {
            foreach (self::BURSA_REGIONS as $reg) {
                if (mb_stripos($formatted_address, $reg['name']) !== false) {
                    $district = $reg['name'];
                    break;
                }
            }
            if ($district === 'Özel Pin Bölgesi' || empty($district)) {
                $district = 'Bursa';
            }
        }

        // Insert new lead
        $db->insert('leads', [
            'place_id' => $place_id,
            'name' => $display_name,
            'sector' => $sector,
            'primary_type' => $primary_type,
            'types_json' => json_encode($types, JSON_UNESCAPED_UNICODE),
            'district' => $district,
            'address' => $formatted_address,
            'latitude' => $lat,
            'longitude' => $lng,
            'business_status' => $business_status,
            'discovery_state' => 'DISCOVERED',
            'stage' => 'New Lead',
            'lead_source' => 'Google Places (New)',
            'package' => $package,
            'potential_mrr' => $potential_mrr,
            'google_maps_uri' => $google_maps_uri,
            'matched_categories' => $category_slug,
            'matched_queries' => $query,
            'matched_regions' => $region_name,
            'first_seen_at' => date('Y-m-d H:i:s'),
            'last_seen_at' => date('Y-m-d H:i:s'),
            'last_crawled_at' => date('Y-m-d H:i:s'),
            'discovery_count' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $new_id = $db->insert_id();

        // Create initial discovery activity
        $db->insert('lead_activities', [
            'id_leads' => $new_id,
            'activity_type' => 'discovery',
            'title' => 'Google Places Discovery',
            'description' => "Google Places New üzerinden '{$query}' sorgusu ve '{$region_name}' bölgesi ile keşfedildi.",
            'performed_by' => 'Google Places Crawler',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['action' => ($business_status !== 'OPERATIONAL') ? 'closed' : 'new', 'lead_id' => $new_id];
    }

    /**
     * Enrich a single lead using Place Details (New).
     */
    public function enrich_lead(int $lead_id): array
    {
        $db = $this->CI->db;
        $lead = $db->get_where('leads', ['id' => $lead_id])->row_array();
        if (!$lead) {
            throw new InvalidArgumentException('Lead bulunamadı.');
        }

        $place_id = $lead['place_id'] ?? '';
        if (!$place_id) {
            throw new RuntimeException('Bu lead için Google Place ID tanımlı değil.');
        }

        $api_key = $this->get_api_key();
        $url = 'https://places.googleapis.com/v1/places/' . urlencode($place_id) . '?languageCode=tr&regionCode=TR';

        $headers = [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $api_key,
            'X-Goog-FieldMask: ' . self::ENRICHMENT_FIELD_MASK,
        ];

        $start_time = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $exec_time = (int) round((microtime(true) - $start_time) * 1000);
        curl_close($ch);

        $json = json_decode($response ?: '', true) ?: [];

        // Log usage
        $this->log_api_usage('https://places.googleapis.com/v1/places/{id}', 'place_details', $http_code, $exec_time, 'Enterprise', [
            'lead_id' => $lead_id,
            'place_id' => $place_id,
        ]);

        if ($http_code !== 200 || !empty($json['error'])) {
            $err_msg = $json['error']['message'] ?? 'Place Details API isteği başarısız oldu.';
            throw new RuntimeException($err_msg);
        }

        $update_data = [
            'discovery_state' => 'ENRICHED',
            'enriched_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($json['nationalPhoneNumber'])) {
            $update_data['phone'] = $json['nationalPhoneNumber'];
            $update_data['whatsapp'] = $json['nationalPhoneNumber'];
        }
        if (!empty($json['internationalPhoneNumber']) && empty($update_data['phone'])) {
            $update_data['phone'] = $json['internationalPhoneNumber'];
            $update_data['whatsapp'] = $json['internationalPhoneNumber'];
        }
        if (!empty($json['websiteUri'])) {
            $update_data['website'] = $json['websiteUri'];
        }
        if (isset($json['rating'])) {
            $update_data['rating'] = (float) $json['rating'];
        }
        if (isset($json['userRatingCount'])) {
            $update_data['user_rating_count'] = (int) $json['userRatingCount'];
        }
        if (!empty($json['priceLevel'])) {
            $update_data['price_level'] = $json['priceLevel'];
        }
        if (!empty($json['regularOpeningHours'])) {
            $update_data['opening_hours_json'] = json_encode($json['regularOpeningHours'], JSON_UNESCAPED_UNICODE);
        }
        if (!empty($json['photos'])) {
            $update_data['photos_json'] = json_encode(array_slice($json['photos'], 0, 5), JSON_UNESCAPED_UNICODE);
        }
        if (!empty($json['reviews'])) {
            $update_data['reviews_json'] = json_encode(array_slice($json['reviews'], 0, 5), JSON_UNESCAPED_UNICODE);
        }

        $db->where('id', $lead_id)->update('leads', $update_data);

        // Activity log
        $db->insert('lead_activities', [
            'id_leads' => $lead_id,
            'activity_type' => 'enrichment',
            'title' => 'Google Places Detay Zenginleştirme',
            'description' => "Telefon: " . ($update_data['phone'] ?? '—') . " | Web: " . ($update_data['website'] ?? '—') . " | Puan: " . ($update_data['rating'] ?? '—') . " ({$update_data['user_rating_count']} yorum)",
            'performed_by' => 'Google Places Enrichment',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'lead' => $db->get_where('leads', ['id' => $lead_id])->row_array(),
        ];
    }

    /**
     * Log API usage into `places_api_usage`.
     */
    protected function log_api_usage(string $endpoint, string $operation, int $http_code, int $exec_time_ms, string $sku_tier, array $metadata = []): void
    {
        try {
            if ($this->CI && isset($this->CI->db) && $this->CI->db->table_exists('places_api_usage')) {
                $this->CI->db->insert('places_api_usage', [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'endpoint' => $endpoint,
                    'operation' => $operation,
                    'http_status' => $http_code,
                    'response_time_ms' => $exec_time_ms,
                    'sku_tier' => $sku_tier,
                    'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                ]);
            }
        } catch (Throwable $e) {
            if (function_exists('log_message')) {
                log_message('error', 'Places API usage log error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get summary usage statistics.
     */
    public function get_usage_stats(): array
    {
        $db = $this->CI->db;
        $today = date('Y-m-d 00:00:00');
        $this_month = date('Y-m-01 00:00:00');

        $today_calls = $db->table_exists('places_api_usage') ? $db->where('timestamp >=', $today)->count_all_results('places_api_usage') : 0;
        $month_calls = $db->table_exists('places_api_usage') ? $db->where('timestamp >=', $this_month)->count_all_results('places_api_usage') : 0;
        $total_calls = $db->table_exists('places_api_usage') ? $db->count_all_results('places_api_usage') : 0;

        $today_text = $db->table_exists('places_api_usage') ? $db->where('timestamp >=', $today)->where('operation', 'TEXT_SEARCH')->count_all_results('places_api_usage') : 0;
        $today_details = $db->table_exists('places_api_usage') ? $db->where('timestamp >=', $today)->where('operation', 'PLACE_DETAILS')->count_all_results('places_api_usage') : 0;

        $total_discovered = $db->where('place_id IS NOT NULL', null, false)->count_all_results('leads');
        $total_enriched = $db->where('discovery_state', 'ENRICHED')->count_all_results('leads');
        $total_operational = $db->where('business_status', 'OPERATIONAL')->count_all_results('leads');

        return [
            'total_discovered_leads' => (int) $total_discovered,
            'total_enriched_leads' => (int) $total_enriched,
            'total_operational_leads' => (int) $total_operational,
            'today_calls' => (int) $today_calls,
            'today_calls_text_search' => (int) $today_text,
            'today_calls_details' => (int) $today_details,
            'month_calls' => (int) $month_calls,
            'total_calls' => (int) $total_calls,
            'daily_limit' => 500,
            'monthly_quota_estimate_usd' => round(($month_calls * 0.035), 2),
        ];
    }

    /**
     * Direct live search by name or custom keyword with optional auto-enrichment.
     *
     * @param string $query Business name or query (e.g. 'Masterhair Kuaför', 'Nilüfer Dt. Ahmet')
     * @param string $district Optional district name or slug
     * @param string $category_slug Optional sector category slug
     * @param bool $auto_enrich If true, immediately calls Place Details (New) to fetch phone, hours, website
     * @return array
     */
    public function search_by_name(string $query, string $district = '', string $category_slug = 'guzellik_kuafor', bool $auto_enrich = false): array
    {
        $clean_query = trim($query);
        if ($clean_query === '') {
            throw new InvalidArgumentException('Arama terimi veya işletme adı boş olamaz.');
        }

        // Determine location bias / region
        $location_restriction = [];
        $region_name = 'Bursa';

        if (!empty($district)) {
            foreach (self::BURSA_REGIONS as $slug => $reg) {
                if (strcasecmp($district, $slug) === 0 || strcasecmp($district, $reg['name']) === 0) {
                    $location_restriction = ['rectangle' => $reg['viewport']];
                    $region_name = $reg['name'];
                    break;
                }
            }
        }

        if (empty($location_restriction)) {
            $location_restriction = [
                'rectangle' => [
                    'low' => ['latitude' => 40.05, 'longitude' => 28.75],
                    'high' => ['latitude' => 40.35, 'longitude' => 29.25],
                ]
            ];
        }

        // Append region to textQuery if not already present
        $full_query = $clean_query;
        if (!preg_match('/(bursa|nilüfer|osmangazi|yıldırım|gemlik|mudanya|inegöl)/ui', $full_query)) {
            $full_query .= ' ' . $region_name;
        }

        $included_type = null;
        if (!empty($category_slug) && isset(self::TAXONOMY[$category_slug]['googleIncludedType'])) {
            $included_type = self::TAXONOMY[$category_slug]['googleIncludedType'];
        }

        // 1. First attempt: Search with query, location bias, and optional included_type
        $search_result = $this->search_text($full_query, $location_restriction, $included_type, null, true);

        // 2. Fallback attempt: If no places found with included_type, search without included_type
        if ($search_result['success'] && empty($search_result['places']) && !empty($included_type)) {
            $search_result = $this->search_text($full_query, $location_restriction, null, null, true);
        }

        // 3. Fallback attempt: If still no places and full_query differs from clean_query, search clean_query with bias
        if ($search_result['success'] && empty($search_result['places']) && $full_query !== $clean_query) {
            $search_result = $this->search_text($clean_query, $location_restriction, null, null, true);
        }

        if (!$search_result['success']) {
            return [
                'success' => false,
                'message' => $search_result['error'] ?? 'Google Places araması başarısız oldu.',
                'leads' => [],
                'places_found' => 0,
            ];
        }

        $places = $search_result['places'] ?? [];
        $leads = [];
        $created_count = 0;
        $updated_count = 0;

        foreach ($places as $place) {
            $upsert = $this->upsert_place_lead($place, $category_slug, $clean_query, $region_name);
            $lead_id = $upsert['lead_id'] ?? 0;
            if (!$lead_id) continue;

            if ($upsert['action'] === 'new') {
                $created_count++;
            } else {
                $updated_count++;
            }

            if ($auto_enrich) {
                try {
                    $enrich_res = $this->enrich_lead($lead_id);
                    if (!empty($enrich_res['lead'])) {
                        $leads[] = $enrich_res['lead'];
                        continue;
                    }
                } catch (Throwable $e) {
                    // fallback to raw row
                }
            }

            $lead_row = $this->CI->db->get_where('leads', ['id' => $lead_id])->row_array();
            if ($lead_row) {
                $leads[] = $lead_row;
            }
        }

        // Also search existing local CRM database leads for matching keyword
        $existing_crm_leads = [];
        try {
            $db = $this->CI->db;
            $db->group_start();
            $db->like('name', $clean_query);
            $db->or_like('contact_person', $clean_query);
            $db->or_like('phone', $clean_query);
            $db->or_like('address', $clean_query);
            $db->group_end();
            $db->limit(10);
            $existing_crm_leads = $db->get('leads')->result_array();
        } catch (Throwable $e) {
            $existing_crm_leads = [];
        }

        return [
            'success' => true,
            'query' => $clean_query,
            'full_query' => $full_query,
            'mode' => $auto_enrich ? 'enriched' : 'basic',
            'places_found' => count($places),
            'leads_created' => $created_count,
            'leads_updated' => $updated_count,
            'leads' => $leads,
            'existing_crm_leads' => $existing_crm_leads,
        ];
    }
}
