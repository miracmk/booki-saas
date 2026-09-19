<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Blueprint Service.
 *
 * Manages industry templates, modular configuration, and automated seeding
 * of sector-specific presets, categories, services, stations, staff, and demo data.
 */
class Blueprint_service
{
    /**
     * @var CI_Controller
     */
    protected CI_Controller $CI;

    /**
     * @var string
     */
    protected string $blueprints_path;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper('salonflora_crypto');

        $this->CI->load->model('services_model');
        $this->CI->load->model('service_categories_model');
        $this->CI->load->model('stations_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('customers_model');
        $this->CI->load->model('admins_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('settings_model');

        $this->blueprints_path = APPPATH . 'seeders/blueprints/';
    }

    /**
     * Get list of all available industry blueprints.
     *
     * @return array
     */
    public function get_all_blueprints(): array
    {
        $blueprints = [];
        $files = glob($this->blueprints_path . '*.json');

        if (!empty($files)) {
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);

                if ($data && isset($data['industry']['code'])) {
                    $blueprints[] = [
                        'code' => $data['industry']['code'],
                        'name' => $data['industry']['name'] ?? '',
                        'icon' => $data['industry']['icon'] ?? '🏢',
                        'service_type' => $data['industry']['service_type'] ?? 'duration',
                        'description' => $data['industry']['description'] ?? '',
                        'terminology' => $data['terminology'] ?? [],
                        'enabled_modules' => $data['enabled_modules'] ?? [],
                        'service_count' => count($data['services'] ?? []),
                        'category_count' => count($data['service_categories'] ?? []),
                        'station_count' => count($data['stations'] ?? []),
                        'provider_count' => count($data['demo']['providers'] ?? []),
                        'customer_count' => count($data['demo']['customers'] ?? []),
                        'appointment_count' => count($data['demo']['appointments'] ?? []),
                    ];
                }
            }
        }

        return $blueprints;
    }

    /**
     * Get a specific blueprint by industry code.
     *
     * @param string $code
     * @return array|null
     */
    public function get_blueprint(string $code): ?array
    {
        $file = $this->blueprints_path . $code . '.json';

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        return json_decode($content, true);
    }

    /**
     * Apply an industry blueprint to the current tenant/database.
     *
     * @param string $code Industry blueprint code
     * @param bool $seed_demo Whether to seed 10 customers, 3-4 providers, 15-20 appointments
     * @param array $options Additional configuration overrides
     * @return array Result summary
     */
    public function apply_blueprint(string $code, bool $seed_demo = true, array $options = []): array
    {
        $blueprint = $this->get_blueprint($code);

        if (!$blueprint) {
            throw new InvalidArgumentException("Blueprint not found for industry: {$code}");
        }

        $result = [
            'success' => true,
            'industry' => $blueprint['industry']['name'],
            'categories_created' => 0,
            'services_created' => 0,
            'stations_created' => 0,
            'providers_created' => 0,
            'customers_created' => 0,
            'appointments_created' => 0,
        ];

        // 1. Sync or record industry_blueprints table
        if ($this->CI->db->table_exists('industry_blueprints')) {
            $existing_bp = $this->CI->db->get_where('industry_blueprints', ['code' => $code])->row_array();
            $bp_data = [
                'code' => $code,
                'name' => $blueprint['industry']['name'],
                'icon' => $blueprint['industry']['icon'] ?? '🏢',
                'description' => $blueprint['industry']['description'] ?? '',
                'service_type' => $blueprint['industry']['service_type'] ?? 'duration',
                'enabled_modules' => json_encode($blueprint['enabled_modules'] ?? []),
                'terminology' => json_encode($blueprint['terminology'] ?? []),
                'default_settings' => json_encode($blueprint['default_settings'] ?? []),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing_bp) {
                $this->CI->db->update('industry_blueprints', $bp_data, ['id' => $existing_bp['id']]);
            } else {
                $bp_data['created_at'] = date('Y-m-d H:i:s');
                $bp_data['sort_order'] = 0;
                $bp_data['is_active'] = 1;
                $this->CI->db->insert('industry_blueprints', $bp_data);
            }
        }

        // 2. Update Settings & Feature Flags
        $this->update_settings($code, $blueprint, $options);

        // 3. Create Service Categories
        $category_map = []; // name => id
        if (!empty($blueprint['service_categories'])) {
            foreach ($blueprint['service_categories'] as $cat) {
                $existing_cat = $this->CI->db->get_where('service_categories', ['name' => $cat['name']])->row_array();
                if ($existing_cat) {
                    $category_map[$cat['name']] = (int) $existing_cat['id'];
                } else {
                    $cat_id = $this->CI->service_categories_model->save([
                        'name' => $cat['name'],
                        'description' => $cat['description'] ?? '',
                    ]);
                    $category_map[$cat['name']] = $cat_id;
                    $result['categories_created']++;
                }
            }
        }

        // 4. Create Services
        $service_map = []; // name => id
        if (!empty($blueprint['services'])) {
            foreach ($blueprint['services'] as $srv) {
                $cat_id = isset($srv['category']) && isset($category_map[$srv['category']]) ? $category_map[$srv['category']] : null;
                $existing_srv = $this->CI->db->get_where('services', ['name' => $srv['name']])->row_array();

                $srv_data = [
                    'name' => $srv['name'],
                    'duration' => $srv['duration'] ?? 30,
                    'price' => $srv['price'] ?? 0.00,
                    'currency' => $blueprint['default_settings']['currency_symbol'] ?? '₺',
                    'description' => $srv['description'] ?? '',
                    'color' => $srv['color'] ?? '#3b82f6',
                    'slot_interval' => $srv['slot_interval'] ?? 15,
                    'attendants_number' => $srv['attendants_number'] ?? 1,
                    'id_service_categories' => $cat_id,
                ];

                if ($existing_srv) {
                    $srv_data['id'] = $existing_srv['id'];
                    $srv_id = $this->CI->services_model->save($srv_data);
                } else {
                    $srv_id = $this->CI->services_model->save($srv_data);
                    $result['services_created']++;
                }
                $service_map[$srv['name']] = $srv_id;
            }
        }

        // 5. Create Stations
        $station_map = []; // name => id
        if (!empty($blueprint['stations']) && $this->CI->db->table_exists('stations')) {
            foreach ($blueprint['stations'] as $stn) {
                $existing_stn = $this->CI->db->get_where('stations', ['name' => $stn['name']])->row_array();
                if ($existing_stn) {
                    $station_map[$stn['name']] = (int) $existing_stn['id'];
                } else {
                    $stn_id = $this->CI->stations_model->save([
                        'name' => $stn['name'],
                        'notes' => $stn['notes'] ?? '',
                        'is_active' => 1,
                    ]);
                    $station_map[$stn['name']] = $stn_id;
                    $result['stations_created']++;
                }
            }
        }

        // 5b. Create Consumable Products & Service Recipes (Sarf Malzemeleri ve Reçeteler)
        $product_map = [];
        if (!empty($blueprint['consumables']) && $this->CI->db->table_exists('products')) {
            $result['consumables_created'] = 0;
            foreach ($blueprint['consumables'] as $csm) {
                $existing_prod = $this->CI->db->get_where('products', ['name' => $csm['name']])->row_array();
                $prod_data = [
                    'name' => $csm['name'],
                    'sku' => $csm['sku'] ?? null,
                    'cost_price' => (float) ($csm['cost_price'] ?? 0.00),
                    'sale_price' => (float) ($csm['sale_price'] ?? 0.00),
                    'stock_quantity' => (float) ($csm['stock_quantity'] ?? 100),
                    'low_stock_threshold' => (int) ($csm['low_stock_threshold'] ?? 10),
                    'unit' => $csm['unit'] ?? 'adet',
                    'is_consumable' => 1,
                    'is_active' => 1,
                ];
                if ($existing_prod) {
                    $prod_data['updated_at'] = date('Y-m-d H:i:s');
                    $this->CI->db->update('products', $prod_data, ['id' => $existing_prod['id']]);
                    $product_map[$csm['name']] = (int) $existing_prod['id'];
                } else {
                    $prod_data['created_at'] = date('Y-m-d H:i:s');
                    $this->CI->db->insert('products', $prod_data);
                    $product_map[$csm['name']] = $this->CI->db->insert_id();
                    $result['consumables_created']++;
                }
            }
        }

        if (!empty($blueprint['service_consumable_recipes']) && $this->CI->db->table_exists('service_consumables')) {
            $result['recipes_created'] = 0;
            foreach ($blueprint['service_consumable_recipes'] as $recipe) {
                $srv_name = $recipe['service_name'];
                $prod_name = $recipe['product_name'];
                if (isset($service_map[$srv_name]) && isset($product_map[$prod_name])) {
                    $s_id = $service_map[$srv_name];
                    $p_id = $product_map[$prod_name];
                    $exists_rec = $this->CI->db->get_where('service_consumables', [
                        'id_services' => $s_id,
                        'id_products' => $p_id,
                    ])->row_array();
                    $rec_data = [
                        'id_services' => $s_id,
                        'id_products' => $p_id,
                        'quantity_used' => (float) ($recipe['quantity_used'] ?? 1.00),
                        'unit' => $recipe['unit'] ?? 'adet',
                        'notes' => $recipe['notes'] ?? 'Standart Reçete',
                    ];
                    if ($exists_rec) {
                        $this->CI->db->update('service_consumables', $rec_data, ['id' => $exists_rec['id']]);
                    } else {
                        $rec_data['created_at'] = date('Y-m-d H:i:s');
                        $this->CI->db->insert('service_consumables', $rec_data);
                        $result['recipes_created']++;
                    }
                }
            }
        }

        // 6. Seed Demo Data (Admin, Providers, Customers, Appointments)
        if ($seed_demo && !empty($blueprint['demo'])) {
            $demo_ids = $this->seed_demo_data($code, $blueprint, $service_map, $station_map);
            $result['providers_created'] = $demo_ids['providers_count'];
            $result['customers_created'] = $demo_ids['customers_count'];
            $result['appointments_created'] = $demo_ids['appointments_count'];
        }

        return $result;
    }

    /**
     * Update settings and feature toggles for the given industry.
     */
    protected function update_settings(string $code, array $blueprint, array $options): void
    {
        $enabled_modules = $blueprint['enabled_modules'] ?? [];
        $features_map = [];

        // All supported features in BooKi
        $all_features = [
            'appointments', 'calendar', 'stations', 'packages', 'memberships',
            'adisyon', 'pos', 'finance', 'expenses', 'inventory',
            'staff_commissions', 'marketing', 'reviews', 'loyalty',
            'client_portal', 'checkin', 'invoices',
            'restaurant_floor_plan', 'restaurant_reservations', 'restaurant_experiences'
        ];

        foreach ($all_features as $feat) {
            $features_map[$feat] = in_array($feat, $enabled_modules, true);
        }

        // Settings to persist
        $settings = [
            'industry_code' => $code,
            'business_type' => $blueprint['default_settings']['business_type'] ?? 'wellness',
            'features_enabled_json' => json_encode($features_map),
            'industry_custom_terminology' => json_encode($blueprint['terminology'] ?? []),
            'slot_interval' => (string) ($blueprint['default_settings']['slot_interval'] ?? '15'),
            'future_booking_limit' => (string) ($blueprint['default_settings']['future_booking_limit'] ?? '30'),
            'require_phone_number' => !empty($blueprint['default_settings']['require_phone_number']) ? '1' : '0',
            'currency_symbol' => $blueprint['default_settings']['currency_symbol'] ?? '₺',
            'currency_code' => $blueprint['default_settings']['currency_code'] ?? 'TRY',
            'onboarding_completed' => '1',
        ];

        if (!empty($options['company_name'])) {
            $settings['company_name'] = $options['company_name'];
        }

        foreach ($settings as $k => $v) {
            $exists = $this->CI->db->get_where('settings', ['name' => $k])->row_array();
            if ($exists) {
                $this->CI->db->update('settings', ['value' => $v], ['name' => $k]);
            } else {
                $this->CI->db->insert('settings', ['name' => $k, 'value' => $v]);
            }
        }
    }

    /**
     * Seed Demo Users, Staff, Customers, and Appointments.
     */
    protected function seed_demo_data(string $code, array $blueprint, array $service_map, array $station_map): array
    {
        $demo = $blueprint['demo'];
        $created_providers = []; // provider_idx => id
        $created_customers = []; // customer_idx => id
        $created_appointments_count = 0;

        $default_password = 'BooKiDemo2026!';
        $role_provider_id = $this->CI->providers_model->get_provider_role_id();
        $role_customer_id = $this->CI->customers_model->get_customer_role_id();

        // 1. Seed Demo Providers (Staff)
        if (!empty($demo['providers'])) {
            foreach ($demo['providers'] as $idx => $prov) {
                $email = $prov['email'] ?? "demo-{$code}-uzman" . ($idx + 1) . "@kibusiness.co";
                $phone = $prov['phone_number'] ?? '05432137000';

                // Check if user already exists
                $existing = $this->CI->db
                    ->from('users')
                    ->where('id_roles', $role_provider_id)
                    ->where('email_hash', sf_pii_hash($email))
                    ->get()
                    ->row_array();

                // Map services and stations
                $assigned_service_ids = [];
                if (!empty($prov['services'])) {
                    foreach ($prov['services'] as $s_name) {
                        if (isset($service_map[$s_name])) {
                            $assigned_service_ids[] = $service_map[$s_name];
                        }
                    }
                }
                if (empty($assigned_service_ids)) {
                    $assigned_service_ids = array_values($service_map);
                }

                $assigned_station_ids = [];
                if (!empty($prov['stations'])) {
                    foreach ($prov['stations'] as $st_name) {
                        if (isset($station_map[$st_name])) {
                            $assigned_station_ids[] = $station_map[$st_name];
                        }
                    }
                }

                $working_plan = [
                    'monday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
                    'tuesday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
                    'wednesday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
                    'thursday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
                    'friday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
                    'saturday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
                    'sunday' => null,
                ];

                $provider_data = [
                    'first_name' => $prov['first_name'],
                    'last_name' => $prov['last_name'],
                    'email' => $email,
                    'mobile_number' => $phone,
                    'phone_number' => $phone,
                    'address' => 'Merkez Mah.',
                    'city' => 'Bursa',
                    'zip_code' => '16000',
                    'notes' => $prov['notes'] ?? '',
                    'services' => $assigned_service_ids,
                    'stations' => $assigned_station_ids,
                    'settings' => [
                        'username' => 'demo_' . $code . '_staff_' . ($idx + 1),
                        'password' => $default_password,
                        'working_plan' => json_encode($working_plan),
                        'notifications' => 1,
                        'calendar_view' => 'default',
                    ],
                ];

                if (!empty($prov['commission_type'])) {
                    $provider_data['commission_type'] = $prov['commission_type'];
                    $provider_data['commission_value'] = (float) ($prov['commission_value'] ?? 0);
                }

                if ($existing) {
                    $provider_data['id'] = $existing['id'];
                    unset($provider_data['settings']['password']); // keep existing password
                    $prov_id = $this->CI->providers_model->save($provider_data);
                } else {
                    $prov_id = $this->CI->providers_model->save($provider_data);
                }

                $created_providers[$idx] = $prov_id;
            }
        }

        // 2. Seed Demo Customers (10 distinct customers)
        if (!empty($demo['customers'])) {
            foreach ($demo['customers'] as $idx => $cust) {
                $email = $cust['email'] ?? "demo-{$code}-musteri" . ($idx + 1) . "@kibusiness.co";
                $phone = $cust['phone_number'] ?? '05062505562';

                $existing = $this->CI->db
                    ->from('users')
                    ->where('id_roles', $role_customer_id)
                    ->where('email_hash', sf_pii_hash($email))
                    ->get()
                    ->row_array();

                $cust_data = [
                    'first_name' => $cust['first_name'],
                    'last_name' => $cust['last_name'],
                    'email' => $email,
                    'phone_number' => $phone,
                    'address' => 'Nilüfer Mah.',
                    'city' => $cust['city'] ?? 'Bursa',
                    'zip_code' => '16110',
                    'notes' => $cust['notes'] ?? '',
                ];

                if ($existing) {
                    $cust_data['id'] = $existing['id'];
                    $cust_id = $this->CI->customers_model->save($cust_data);
                } else {
                    $cust_id = $this->CI->customers_model->save($cust_data);
                }

                $created_customers[$idx] = $cust_id;
            }
        }

        // 3. Seed Demo Appointments (15-20 appointments)
        if (!empty($demo['appointments']) && !empty($created_providers) && !empty($created_customers)) {
            $today = new DateTime('today');

            foreach ($demo['appointments'] as $app) {
                $p_idx = $app['provider_idx'] ?? 0;
                $c_idx = $app['customer_idx'] ?? 0;

                $provider_id = $created_providers[$p_idx] ?? reset($created_providers);
                $customer_id = $created_customers[$c_idx] ?? reset($created_customers);

                $service_id = isset($service_map[$app['service_name']]) ? $service_map[$app['service_name']] : reset($service_map);
                $service_record = $this->CI->services_model->find($service_id);
                $duration = $service_record ? (int) $service_record['duration'] : 30;

                // Calculate appointment date/time
                $day_offset = (int) ($app['day_offset'] ?? 0);
                $app_date = (clone $today)->modify("{$day_offset} days");
                $time_str = $app['time'] ?? '10:00';
                $start_datetime = $app_date->format('Y-m-d') . ' ' . $time_str . ':00';
                $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " +{$duration} minutes"));

                // Resolve Station
                $station_id = null;
                if (isset($app['station_idx']) && !empty($blueprint['stations'][$app['station_idx']])) {
                    $st_name = $blueprint['stations'][$app['station_idx']]['name'];
                    $station_id = $station_map[$st_name] ?? null;
                }

                $appointment_data = [
                    'start_datetime' => $start_datetime,
                    'end_datetime' => $end_datetime,
                    'book_datetime' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'notes' => 'Demo Rezervasyon (' . $blueprint['industry']['name'] . ')',
                    'status' => $app['status'] ?? 'confirmed',
                    'id_users_provider' => $provider_id,
                    'id_users_customer' => $customer_id,
                    'id_services' => $service_id,
                    'payment_status' => $app['payment_status'] ?? 'pending',
                    'payment_method' => $app['payment_method'] ?? 'cash',
                    'payment_amount' => $service_record ? (float) $service_record['price'] : 0.00,
                ];

                if ($station_id && $this->CI->db->field_exists('id_stations', 'appointments')) {
                    $appointment_data['id_stations'] = $station_id;
                }

                if ($appointment_data['status'] === 'completed') {
                    $appointment_data['actual_start_datetime'] = $start_datetime;
                    $appointment_data['actual_end_datetime'] = $end_datetime;
                    $appointment_data['is_invoiced'] = 1;
                }

                $this->CI->appointments_model->save($appointment_data);
                $created_appointments_count++;
            }
        }

        return [
            'providers_count' => count($created_providers),
            'customers_count' => count($created_customers),
            'appointments_count' => $created_appointments_count,
        ];
    }
}

