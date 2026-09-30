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
 * Services controller.
 *
 * Handles the services related operations.
 *
 * @package Controllers
 */
class Services extends App_Controller
{
    public array $allowed_service_fields = [
        'id',
        'name',
        'duration',
        'price',
        'currency',
        'description',
        'color',
        'location',
        'slot_interval',
        'attendants_number',
        'is_private',
        'id_service_categories',
        'access_type',
        'service_nature',
        'tax_rate',
        'valid_hours_start',
        'valid_hours_end',
        'daily_capacity',
        'pass_validity_days',
        'total_passes',
        'provider_durations',
        'follow_up_required',
        'follow_up_category',
        'follow_up_priority',
        'follow_up_delay_override',
        'follow_up_message_override',
        'crm_follow_up_rules',
        'providers',
    ];
    public array $optional_service_fields = [
        'id_service_categories' => null,
        'access_type' => 'duration',
        'service_nature' => 'duration',
        'tax_rate' => 20.00,
        'valid_hours_start' => '09:00',
        'valid_hours_end' => '18:00',
        'daily_capacity' => null,
        'pass_validity_days' => 1,
        'total_passes' => 1,
        'provider_durations' => null,
        'follow_up_required' => 0,
        'follow_up_category' => null,
        'follow_up_priority' => 'optional',
        'follow_up_delay_override' => null,
        'follow_up_message_override' => null,
        'crm_follow_up_rules' => null,
    ];

    /**
     * Services constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
        $this->load->model('inventory_consumables_model');
        $this->load->model('branches_model');
        $this->load->model('digital_waivers_model');

        $this->load->library('accounts');
        $this->load->library('timezones');
        $this->load->library('webhooks_client');
    }

    /**
     * Render the backend services page.
     *
     * On this page admin users will be able to manage services, which are eventually selected by customers during the
     * booking process.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('services')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SERVICES)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $providers = $this->providers_model->get();
        $products = $this->db->table_exists('products') ? $this->db->order_by('name', 'ASC')->get('products')->result_array() : [];
        $branches = $this->db->table_exists('branches') ? $this->branches_model->get() : [];

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'event_minimum_duration' => EVENT_MINIMUM_DURATION,
            'providers' => filter_sensitive_users_data($providers),
            'products' => $products,
            'branches' => $branches,
        ]);

        html_vars([
            'page_title' => lang('services'),
            'active_menu' => PRIV_SERVICES,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'timezones' => $this->timezones->to_array(),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'providers' => filter_sensitive_users_data($providers),
            'products' => $products,
            'branches' => $branches,
        ]);

        $this->load->view('pages/services');
    }

    /**
     * Filter services by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('order_by', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');

            $order_by = request('order_by', 'update_datetime DESC');

            $limit = request('limit', 1000);

            $offset = (int) request('offset', '0');

            $services = $this->services_model->search($keyword, $limit, $offset, $order_by);

            // Fetch appointment counts for current month in a single batch
            $start_month = date('Y-m-01 00:00:00');
            $end_month = date('Y-m-t 23:59:59');
            $monthly_counts = [];
            if ($this->db->table_exists('appointments')) {
                $counts_query = $this->db->select('id_services, COUNT(*) as app_count')
                    ->where('start_datetime >=', $start_month)
                    ->where('start_datetime <=', $end_month)
                    ->group_by('id_services')
                    ->get('appointments')
                    ->result_array();
                foreach ($counts_query as $row) {
                    $monthly_counts[$row['id_services']] = (int) $row['app_count'];
                }
            }

            // Categories map
            $categories_by_id = [];
            if ($this->db->table_exists('service_categories')) {
                $cats = $this->db->get('service_categories')->result_array();
                foreach ($cats as $cat) {
                    $categories_by_id[$cat['id']] = $cat['name'];
                }
            }

            // Include provider IDs and statistics for each service
            foreach ($services as &$service) {
                $service['providers'] = $this->services_model->get_provider_ids($service['id']);
                $service['providers_count'] = count($service['providers']);
                $service['monthly_count'] = $monthly_counts[$service['id']] ?? 0;
                $service['category_name'] = !empty($service['id_service_categories']) ? ($categories_by_id[$service['id_service_categories']] ?? '') : '';

                if (!empty($service['provider_durations']) && is_string($service['provider_durations'])) {
                    $service['provider_durations'] = json_decode($service['provider_durations'], true) ?: [];
                }
                if (!empty($service['crm_follow_up_rules']) && is_string($service['crm_follow_up_rules'])) {
                    $service['crm_follow_up_rules'] = json_decode($service['crm_follow_up_rules'], true) ?: [];
                } elseif (empty($service['crm_follow_up_rules'])) {
                    $service['crm_follow_up_rules'] = [];
                }
            }

            json_response($services);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new service.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service', 'array');

            $service = request('service');

            if (isset($service['provider_durations']) && is_array($service['provider_durations'])) {
                $service['provider_durations'] = json_encode($service['provider_durations'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($service['crm_follow_up_rules']) && is_array($service['crm_follow_up_rules'])) {
                $service['crm_follow_up_rules'] = json_encode($service['crm_follow_up_rules'], JSON_UNESCAPED_UNICODE);
            }

            $this->services_model->only($service, $this->allowed_service_fields);

            $this->services_model->optional($service, $this->optional_service_fields);

            $service_id = $this->services_model->save($service);

            $service = $this->services_model->find($service_id);

            $this->webhooks_client->trigger(WEBHOOK_SERVICE_SAVE, $service);

            json_response([
                'success' => true,
                'id' => $service_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find a service.
     */
    public function find(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service_id', 'numeric');

            $service_id = request('service_id');

            // Validate service_id is a positive integer
            if (empty($service_id) || !filter_var($service_id, FILTER_VALIDATE_INT) || $service_id <= 0) {
                throw new InvalidArgumentException('Invalid service ID provided.');
            }

            $service = $this->services_model->find($service_id);
            if (!empty($service['crm_follow_up_rules']) && is_string($service['crm_follow_up_rules'])) {
                $service['crm_follow_up_rules'] = json_decode($service['crm_follow_up_rules'], true) ?: [];
            } elseif (empty($service['crm_follow_up_rules'])) {
                $service['crm_follow_up_rules'] = [];
            }

            json_response($service);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a service.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service', 'array');

            $service = request('service');

            if (isset($service['provider_durations']) && is_array($service['provider_durations'])) {
                $service['provider_durations'] = json_encode($service['provider_durations'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($service['crm_follow_up_rules']) && is_array($service['crm_follow_up_rules'])) {
                $service['crm_follow_up_rules'] = json_encode($service['crm_follow_up_rules'], JSON_UNESCAPED_UNICODE);
            }

            $this->services_model->only($service, $this->allowed_service_fields);

            $this->services_model->optional($service, $this->optional_service_fields);

            $service_id = $this->services_model->save($service);

            $service = $this->services_model->find($service_id);

            $this->webhooks_client->trigger(WEBHOOK_SERVICE_SAVE, $service);

            json_response([
                'success' => true,
                'id' => $service_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a service.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service_id', 'numeric');

            $service_id = request('service_id');

            // Validate service_id is a positive integer
            if (empty($service_id) || !filter_var($service_id, FILTER_VALIDATE_INT) || $service_id <= 0) {
                throw new InvalidArgumentException('Invalid service ID provided.');
            }

            $service = $this->services_model->find($service_id);

            $this->services_model->delete($service_id);

            $this->webhooks_client->trigger(WEBHOOK_SERVICE_DELETE, $service);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get add-ons for a service.
     */
    public function get_addons(int $service_id): void
    {
        try {
            method('get');
            $addons = $this->services_model->get_addons($service_id);
            json_response($addons);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save an add-on.
     */
    public function save_addon(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            unset($data['csrf_token']);
            $id = $this->services_model->save_addon($data);
            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete an add-on.
     */
    public function delete_addon(int $addon_id): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $this->services_model->delete_addon($addon_id);
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get consumable recipes for a service.
     */
    public function get_consumables(int $service_id): void
    {
        try {
            method('get');
            $recipes = $this->inventory_consumables_model->get_recipes_for_service($service_id);
            $summary = $this->inventory_consumables_model->get_service_recipe_summary($service_id);
            json_response([
                'recipes' => $recipes,
                'summary' => $summary,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save consumable recipe for a service.
     */
    public function save_consumable(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            unset($data['csrf_token']);
            $id = $this->inventory_consumables_model->save_recipe($data);
            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete consumable recipe.
     */
    public function delete_consumable(int $recipe_id): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $this->inventory_consumables_model->delete_recipe($recipe_id);
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get required resources for a service.
     */
    public function get_resources(int $service_id): void
    {
        try {
            method('get');
            $resources = $this->services_model->get_required_resources($service_id);
            json_response($resources);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save required resource for a service.
     */
    public function save_resource(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            unset($data['csrf_token']);
            $id = $this->services_model->save_required_resource($data);
            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete required resource.
     */
    public function delete_resource(int $resource_id): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $this->services_model->delete_required_resource($resource_id);
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get digital waivers / contracts for a service.
     */
    public function get_contracts(int $service_id): void
    {
        try {
            method('get');
            if (cannot('view', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            $this->load->library('legal_catalog');
            $this->legal_catalog->ensure_seeded_templates();

            $service = $this->services_model->find($service_id);
            $service_name = $service['name'] ?? '';
            $category_name = '';
            if (!empty($service['id_service_categories'])) {
                $category = $this->service_categories_model->find((int)$service['id_service_categories']);
                $category_name = $category['name'] ?? '';
            }

            $waivers = $this->db->table_exists('digital_waivers') 
                ? $this->db->order_by('id', 'ASC')->get('digital_waivers')->result_array() 
                : [];

            $linked_count = 0;
            foreach ($waivers as &$waiver) {
                $service_ids_str = (string) ($waiver['applicable_service_ids'] ?? '');
                $service_ids = array_filter(array_map('trim', explode(',', $service_ids_str)));
                $waiver['is_linked'] = in_array((string) $service_id, $service_ids, true);
                if ($waiver['is_linked']) {
                    $linked_count++;
                }
            }

            // If no contracts are linked yet, auto-link suggested ones or provide suggestions
            $suggested = $this->legal_catalog->get_suggested_templates_for_service($service_name, $category_name);

            // Auto-link priority matches if 0 contracts linked
            if ($linked_count === 0 && !empty($waivers)) {
                foreach ($waivers as &$w) {
                    $w_title_lower = mb_strtolower($w['title'], 'UTF-8');
                    $s_name_lower = mb_strtolower($service_name, 'UTF-8');
                    $c_name_lower = mb_strtolower($category_name, 'UTF-8');
                    
                    if ((strpos($s_name_lower, 'cilt') !== false || strpos($c_name_lower, 'cilt') !== false) && strpos($w_title_lower, 'cilt') !== false) {
                        $w['is_linked'] = true;
                        $linked_count++;
                        $this->link_contract_internal((int)$w['id'], $service_id);
                    } elseif ((strpos($s_name_lower, 'lazer') !== false || strpos($c_name_lower, 'lazer') !== false) && strpos($w_title_lower, 'lazer') !== false) {
                        $w['is_linked'] = true;
                        $linked_count++;
                        $this->link_contract_internal((int)$w['id'], $service_id);
                    } elseif ((strpos($s_name_lower, 'botoks') !== false || strpos($s_name_lower, 'dolgu') !== false) && strpos($w_title_lower, 'botoks') !== false) {
                        $w['is_linked'] = true;
                        $linked_count++;
                        $this->link_contract_internal((int)$w['id'], $service_id);
                    } elseif ((strpos($s_name_lower, 'makyaj') !== false || strpos($s_name_lower, 'microblading') !== false) && strpos($w_title_lower, 'makyaj') !== false) {
                        $w['is_linked'] = true;
                        $linked_count++;
                        $this->link_contract_internal((int)$w['id'], $service_id);
                    }
                }
            }

            json_response([
                'success' => true,
                'contracts' => $waivers,
                'suggested_codes' => array_keys($suggested),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Internal helper to link contract
     */
    private function link_contract_internal(int $contract_id, int $service_id): void
    {
        $waiver = $this->db->get_where('digital_waivers', ['id' => $contract_id])->row_array();
        if ($waiver) {
            $service_ids_str = (string) ($waiver['applicable_service_ids'] ?? '');
            $service_ids = array_filter(array_map('trim', explode(',', $service_ids_str)));
            if (!in_array((string)$service_id, $service_ids, true)) {
                $service_ids[] = (string)$service_id;
                $this->db->update('digital_waivers', [
                    'applicable_service_ids' => implode(',', array_values($service_ids)),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $contract_id]);
            }
        }
    }

    /**
     * Toggle contract association with a service.
     */
    public function toggle_contract_link(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $service_id = (int) ($data['service_id'] ?? 0);
            $contract_id = (int) ($data['contract_id'] ?? 0);
            $link = !empty($data['link']);

            if (!$service_id || !$contract_id) {
                throw new InvalidArgumentException('Geçersiz hizmet veya sözleşme ID.');
            }

            $waiver = $this->db->get_where('digital_waivers', ['id' => $contract_id])->row_array();
            if (!$waiver) {
                throw new InvalidArgumentException('Sözleşme şablonu bulunamadı.');
            }

            $service_ids_str = (string) ($waiver['applicable_service_ids'] ?? '');
            $service_ids = array_filter(array_map('trim', explode(',', $service_ids_str)));

            if ($link) {
                if (!in_array((string) $service_id, $service_ids, true)) {
                    $service_ids[] = (string) $service_id;
                }
            } else {
                $service_ids = array_diff($service_ids, [(string) $service_id]);
            }

            $this->db->update('digital_waivers', [
                'applicable_service_ids' => implode(',', array_values($service_ids)),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $contract_id]);

            json_response(['success' => true, 'linked' => $link]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Quick save/create new contract template.
     */
    public function save_contract(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $service_id = (int) ($data['service_id'] ?? 0);
            $title = trim($data['title'] ?? '');
            $content_html = trim($data['content_html'] ?? '');
            $is_mandatory = !empty($data['is_mandatory']) ? 1 : 0;

            if (empty($title)) {
                throw new InvalidArgumentException('Sözleşme / Onam başlığı zorunludur.');
            }

            $now = date('Y-m-d H:i:s');
            $record = [
                'title' => $title,
                'content_html' => $content_html ?: '<p>' . htmlspecialchars($title) . ' kapsamında onay metnidir.</p>',
                'is_mandatory' => $is_mandatory,
                'applicable_service_ids' => $service_id > 0 ? (string) $service_id : '',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->db->insert('digital_waivers', $record);
            $contract_id = $this->db->insert_id();

            json_response(['success' => true, 'contract_id' => $contract_id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get pre-built sector legal catalog.
     */
    public function get_legal_catalog(): void
    {
        try {
            method('get');
            if (cannot('view', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            $this->load->library('legal_catalog');
            $catalog = $this->legal_catalog->get_catalog();

            json_response(['success' => true, 'catalog' => array_values($catalog)]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Import a template from legal catalog and link to service.
     */
    public function import_catalog_template(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $template_code = trim($data['code'] ?? '');
            $service_id = (int) ($data['service_id'] ?? 0);

            $this->load->library('legal_catalog');
            $catalog = $this->legal_catalog->get_catalog();

            if (!isset($catalog[$template_code])) {
                throw new InvalidArgumentException('Seçilen şablon katalogda bulunamadı.');
            }

            $tpl = $catalog[$template_code];
            $now = date('Y-m-d H:i:s');

            // Check if title exists
            $existing = $this->db->get_where('digital_waivers', ['title' => $tpl['title']])->row_array();
            if ($existing) {
                $contract_id = (int)$existing['id'];
                if ($service_id > 0) {
                    $this->link_contract_internal($contract_id, $service_id);
                }
            } else {
                $record = [
                    'title' => $tpl['title'],
                    'content_html' => $tpl['content_html'],
                    'is_mandatory' => $tpl['is_mandatory'],
                    'applicable_service_ids' => $service_id > 0 ? (string)$service_id : '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $this->db->insert('digital_waivers', $record);
                $contract_id = $this->db->insert_id();
            }

            json_response(['success' => true, 'contract_id' => $contract_id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Preview a contract template with sample or personalized placeholders.
     */
    public function preview_contract(int $contract_id, int $service_id = 0): void
    {
        try {
            method('get');
            if (cannot('view', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            $waiver = $this->db->get_where('digital_waivers', ['id' => $contract_id])->row_array();
            if (!$waiver) {
                throw new InvalidArgumentException('Sözleşme şablonu bulunamadı.');
            }

            $this->load->library('legal_catalog');

            $service_name = 'Medikal Klasik Cilt Bakımı';
            $category_name = 'Cilt Bakımı & Yenileme';
            $price = 1500.00;

            if ($service_id > 0) {
                $service = $this->services_model->find($service_id);
                if ($service) {
                    $service_name = $service['name'];
                    $price = (float)$service['price'];
                    if (!empty($service['id_service_categories'])) {
                        $cat = $this->service_categories_model->find((int)$service['id_service_categories']);
                        if ($cat) $category_name = $cat['name'];
                    }
                }
            }

            $sample_context = [
                'customer_full_name' => 'Ayşe Yılmaz',
                'customer_phone' => '0532 987 65 43',
                'customer_email' => 'ayse.yilmaz@example.com',
                'customer_tckn' => '12345678901',
                'service_name' => $service_name,
                'service_category' => $category_name,
                'service_price' => $price,
                'provider_name' => 'Uzm. Estetisyen Zeynep Kaya',
                'appointment_date' => date('d.m.Y', strtotime('+1 day')),
                'appointment_time' => '14:30',
                'appointment_datetime' => date('d.m.Y', strtotime('+1 day')) . ' 14:30',
                'tenant_name' => setting('company_name') ?: 'BooKi Güzellik ve Yaşam Merkezi',
                'tenant_legal_name' => setting('company_name') ?: 'BooKi Güzellik ve Sağlık Hizmetleri Tic. Ltd. Şti.',
            ];

            $rendered_html = $this->legal_catalog->compile($waiver['content_html'], $sample_context);

            json_response([
                'success' => true,
                'contract' => $waiver,
                'raw_content' => $waiver['content_html'],
                'rendered_html' => $rendered_html,
                'sample_context' => $sample_context,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}

