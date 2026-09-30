<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Adisyons Controller (Service Tickets, Orders & Checkout Operations)
 * ---------------------------------------------------------------------------- */

class Adisyons extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('adisyons_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('products_model');
        $this->load->model('packages_model');
        $this->load->model('customer_memberships_model');
        $this->load->model('roles_model');
        $this->load->library('accounts');
    }

    /**
     * Adisyons listing page.
     */
    public function index(): void
    {
        session(['dest_url' => site_url('adisyons')]);
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $role_slug = session('role_slug');
        if ($role_slug === DB_SLUG_CUSTOMER) {
            redirect('customer_portal');
            return;
        }

        $status = $this->input->get('status') ?: 'all';
        $payment_status = $this->input->get('payment_status') ?: 'all';

        $raw_open_id = $this->input->get('open_id') ?: null;
        $raw_apt_id = $this->input->get('appointment_id') ?: null;
        if (!empty($raw_apt_id) && is_numeric($raw_apt_id)) {
            try {
                $apt_adisyon = $this->adisyons_model->get_or_create_for_appointment((int) $raw_apt_id);
                if (!empty($apt_adisyon['id'])) {
                    $raw_open_id = $apt_adisyon['id'];
                }
            } catch (Throwable $e) {
                log_message('error', 'Could not open adisyon for appointment: ' . $e->getMessage());
            }
        }
        $open_id = ($raw_open_id !== null && is_numeric($raw_open_id)) ? (int) $raw_open_id : null;

        $this->db
            ->select('a.*, 
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone,
                      u.first_name as staff_first_name, u.last_name as staff_last_name,
                      rt.table_number, rt.name as table_name')
            ->from('adisyons a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('users u', 'u.id = a.id_users_staff', 'left')
            ->join('restaurant_tables rt', 'rt.id = a.id_restaurant_tables', 'left');

        if ($status !== 'all') {
            $this->db->where('a.status', $status);
        }
        if ($payment_status !== 'all') {
            $this->db->where('a.payment_status', $payment_status);
        }

        $adisyons = $this->db
            ->order_by('a.id DESC')
            ->limit(100)
            ->get()
            ->result_array();

        foreach ($adisyons as &$ad) {
            if (!empty($ad['customer_phone']) && function_exists('sf_pii_is_encrypted') && sf_pii_is_encrypted($ad['customer_phone'])) {
                $ad['customer_phone'] = sf_pii_decrypt($ad['customer_phone']);
            }
        }
        unset($ad);

        $open_count = (int) $this->db->where('status', 'open')->count_all_results('adisyons');
        $unpaid_row = $this->db->select('SUM(total_amount - paid_amount) as unpaid', false)
            ->where('payment_status !=', 'paid')
            ->where('status !=', 'cancelled')
            ->get('adisyons')
            ->row();
        $unpaid_total = (float) ($unpaid_row && $unpaid_row->unpaid !== null ? $unpaid_row->unpaid : 0.0);

        $today_row = $this->db->select_sum('amount', 'today_rev')
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->get('adisyon_payments')
            ->row();
        $today_revenue = (float) ($today_row && $today_row->today_rev !== null ? $today_row->today_rev : 0.0);

        // Fetch active recent appointments for quick adisyon opening
        $active_appointments = $this->db
            ->select('a.id, a.start_datetime, a.end_datetime, a.id_users_customer, a.id_users_provider, a.id_services,
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone,
                      s.name as service_name, s.price as service_price,
                      p.first_name as provider_first_name, p.last_name as provider_last_name')
            ->from('appointments a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('users p', 'p.id = a.id_users_provider', 'left')
            ->join('services s', 's.id = a.id_services', 'left')
            ->where('a.start_datetime >=', date('Y-m-d 00:00:00', strtotime('-3 days')))
            ->order_by('a.start_datetime DESC')
            ->limit(50)
            ->get()
            ->result_array();

        foreach ($active_appointments as &$apt) {
            if (!empty($apt['customer_phone']) && function_exists('sf_pii_is_encrypted') && sf_pii_is_encrypted($apt['customer_phone'])) {
                $apt['customer_phone'] = sf_pii_decrypt($apt['customer_phone']);
            }
        }
        unset($apt);

        $this->load->library('accounting/erp_manager');
        $connected_erp_providers = $this->erp_manager->get_connected_providers();
        $active_erp_provider = setting('active_erp_provider', !empty($connected_erp_providers) ? array_key_first($connected_erp_providers) : '');

        html_vars([
            'page_title' => 'Adisyon & Hesap Yönetimi',
            'active_menu' => 'adisyons',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
        ]);

        $bank_accounts = $this->db
            ->where('is_active', 1)
            ->where('account_type', 'bank')
            ->order_by('is_default_iban DESC, id ASC')
            ->get('bank_accounts')
            ->result_array();
        $okc_terminals = $this->get_connected_okc_terminals();

        $view_data = [
            'active_menu' => 'adisyons',
            'adisyons' => $adisyons,
            'status_filter' => $status,
            'payment_filter' => $payment_status,
            'open_count' => $open_count,
            'unpaid_total' => $unpaid_total,
            'today_revenue' => $today_revenue,
            'available_services' => $this->services_model->get_available_services(),
            'available_products' => $this->products_model->get(['is_active' => 1]),
            'customers' => $this->customers_model->get(),
            'staff_members' => $this->db->get_where('users', ['id_roles' => 2])->result_array(),
            'active_appointments' => $active_appointments,
            'erp_providers' => Erp_manager::PROVIDERS,
            'connected_erp_providers' => $connected_erp_providers,
            'active_erp_provider' => $active_erp_provider,
            'open_id' => $open_id,
            'bank_accounts' => $bank_accounts,
            'okc_terminals' => $okc_terminals,
            'is_okc_connected' => !empty($okc_terminals),
        ];

        $this->load->view('pages/adisyons', $view_data);
    }

    /**
     * Get single adisyon details JSON (with items and payments).
     */
    public function get_details(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        try {
            $adisyon = $this->adisyons_model->find($adisyon_id);

            // Fetch customer packages & memberships for quick checkout deduction
            $packages = [];
            $memberships = [];
            if (!empty($adisyon['id_users_customer'])) {
                $packages = $this->packages_model->get_for_customer((int) $adisyon['id_users_customer']);
                $memberships = $this->customer_memberships_model->get_for_customer((int) $adisyon['id_users_customer']);
            }

            $bank_accounts = $this->db
                ->where('is_active', 1)
                ->where('account_type', 'bank')
                ->order_by('is_default_iban DESC, id ASC')
                ->get('bank_accounts')
                ->result_array();
            $okc_terminals = $this->get_connected_okc_terminals();

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'adisyon' => $adisyon,
                    'customer_packages' => $packages,
                    'customer_memberships' => $memberships,
                    'bank_accounts' => $bank_accounts,
                    'okc_terminals' => $okc_terminals,
                    'is_okc_connected' => !empty($okc_terminals),
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Create fresh manual adisyon.
     */
    public function create(): void
    {
        $this->ensure_authenticated();
        $customer_id = $this->input->post('id_users_customer') ? (int) $this->input->post('id_users_customer') : null;
        $staff_id = $this->input->post('id_users_staff') ? (int) $this->input->post('id_users_staff') : null;
        $table_id = $this->input->post('id_restaurant_tables') ? (int) $this->input->post('id_restaurant_tables') : null;

        try {
            if ($table_id) {
                $adisyon = $this->adisyons_model->get_or_create_for_table($table_id, $customer_id, $staff_id);
            } else {
                $now = date('Y-m-d H:i:s');
                $prefix = 'AD-' . date('Ym') . '-';
                $last = $this->db->select('adisyon_number')->like('adisyon_number', $prefix, 'after')->order_by('id', 'DESC')->limit(1)->get('adisyons')->row_array();
                $num = $last ? (int) substr($last['adisyon_number'], strlen($prefix)) + 1 : 1;
                $adisyon_number = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);

                $this->db->insert('adisyons', [
                    'adisyon_number' => $adisyon_number,
                    'id_users_customer' => $customer_id,
                    'id_users_staff' => $staff_id,
                    'status' => 'open',
                    'payment_status' => 'unpaid',
                    'invoice_status' => 'uninvoiced',
                    'subtotal' => 0.00,
                    'discount_amount' => 0.00,
                    'discount_percent' => 0.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 0.00,
                    'paid_amount' => 0.00,
                    'tip_amount' => 0.00,
                    'opened_at' => $now,
                    'created_at' => $now,
                ]);
                $adisyon_id = $this->db->insert_id();
                $adisyon = $this->adisyons_model->find($adisyon_id);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Add line item to adisyon.
     */
    public function add_item(): void
    {
        $this->ensure_authenticated();
        $adisyon_id = (int) $this->input->post('id_adisyons');
        $name = trim((string) $this->input->post('name'));
        if (empty($name)) {
            $name = 'Ürün / Hizmet';
        }

        $item = [
            'item_type' => $this->input->post('item_type') ?: 'product',
            'id_services' => $this->input->post('id_services') ?: null,
            'id_products' => $this->input->post('id_products') ?: null,
            'name' => $name,
            'unit_price' => (float) $this->input->post('unit_price'),
            'quantity' => max(0.01, (float) ($this->input->post('quantity') ?: 1)),
            'discount_amount' => (float) ($this->input->post('discount_amount') ?: 0),
            'tax_rate' => (float) ($this->input->post('tax_rate') ?: 20),
            'id_users_staff' => $this->input->post('id_users_staff') ?: null,
            'notes' => $this->input->post('notes') ?: null,
        ];

        try {
            $item_id = $this->adisyons_model->add_item($adisyon_id, $item);
            $adisyon = $this->adisyons_model->find($adisyon_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'message' => 'Kalem adisyona eklendi.',
                    'item_id' => $item_id,
                    'adisyon' => $adisyon
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Update line item (quantity, price, discount, staff, name).
     */
    public function update_item(): void
    {
        $this->ensure_authenticated();
        $raw = file_get_contents('php://input');
        $post = !empty($raw) ? json_decode($raw, true) : $this->input->post();
        if (!is_array($post)) {
            $post = $this->input->post() ?: [];
        }

        $item_id = (int) ($post['id'] ?? $post['item_id'] ?? 0);
        try {
            if ($item_id <= 0) {
                throw new InvalidArgumentException('Geçersiz kalem ID.');
            }

            $item = $this->db->get_where('adisyon_items', ['id' => $item_id])->row_array();
            if (!$item) {
                throw new InvalidArgumentException('Adisyon kalemi bulunamadı.');
            }

            $adisyon_id = (int) $item['id_adisyons'];
            $this->adisyons_model->update_item($item_id, $post);
            $updated_adisyon = $this->adisyons_model->find($adisyon_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'message' => 'Kalem başarıyla güncellendi.',
                    'adisyon' => $updated_adisyon,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Delete line item.
     */
    public function remove_item(int $item_id): void
    {
        $this->ensure_authenticated();
        try {
            $item = $this->db->get_where('adisyon_items', ['id' => $item_id])->row_array();
            $adisyon_id = $item ? (int) $item['id_adisyons'] : 0;

            $this->adisyons_model->remove_item($item_id);
            $adisyon = $adisyon_id ? $this->adisyons_model->find($adisyon_id) : null;

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'message' => 'Kalem silindi.',
                    'adisyon' => $adisyon
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Record payment against an adisyon (Cash, Card, Transfer, Package, Membership, Gift Card).
     */
    public function add_payment(): void
    {
        $this->ensure_authenticated();

        $raw = file_get_contents('php://input');
        $post = !empty($raw) ? json_decode($raw, true) : $this->input->post();
        if (!is_array($post)) {
            $post = $this->input->post() ?: [];
        }

        $adisyon_id = (int) ($post['id_adisyons'] ?? $post['adisyon_id'] ?? 0);
        $amount = (float) ($post['amount'] ?? 0.00);
        $payment_method = trim((string) ($post['payment_method'] ?? 'cash'));
        $id_customer_memberships = !empty($post['id_customer_memberships']) ? (int) $post['id_customer_memberships'] : null;
        $id_customer_packages = !empty($post['id_customer_packages']) ? (int) $post['id_customer_packages'] : null;
        $gift_card_code = trim((string) ($post['gift_card_code'] ?? $post['code'] ?? ''));
        $notes = $post['notes'] ?? $post['note'] ?? null;
        $received_by = $this->session->userdata('user_id');

        try {
            if ($adisyon_id <= 0) {
                throw new InvalidArgumentException('Geçersiz adisyon ID.');
            }
            if ($amount <= 0) {
                throw new InvalidArgumentException('Ödeme tutarı 0\'dan büyük olmalıdır.');
            }

            $adisyon = $this->adisyons_model->find($adisyon_id);
            if (!$adisyon) {
                throw new InvalidArgumentException('Adisyon bulunamadı: ' . $adisyon_id);
            }

            if (($adisyon['status'] ?? 'open') !== 'open') {
                throw new InvalidArgumentException('Kapatılmış adisyona tahsilat eklenemez. Önce adisyonu yeniden açın.');
            }

            $customer_id = !empty($adisyon['id_users_customer']) ? (int) $adisyon['id_users_customer'] : null;
            $appointment_id = !empty($adisyon['id_appointments']) ? (int) $adisyon['id_appointments'] : null;
            $now = date('Y-m-d H:i:s');

            $this->db->trans_start();

            // 1. Membership / Package with id_customer_memberships provided
            if (in_array($payment_method, ['membership', 'package'], true) && $id_customer_memberships) {
                if (empty($customer_id)) {
                    throw new InvalidArgumentException('Üyelik veya paket ile ödeme yapabilmek için adisyona kayıtlı bir müşteri atanmalıdır.');
                }
                $cm = $this->db->get_where('customer_memberships', ['id' => $id_customer_memberships])->row_array();
                if ($cm) {
                    // Verify the membership belongs to the customer
                    if ((int) $cm['id_users_customer'] !== $customer_id) {
                        throw new InvalidArgumentException('Seçilen üyelik bu müşteriye ait değil.');
                    }
                    if ($cm['status'] !== 'active') {
                        throw new InvalidArgumentException('Üyelik aktif değil (Durum: ' . $cm['status'] . ').');
                    }
                    if (!empty($cm['current_period_end']) && strtotime($cm['current_period_end']) < time()) {
                        throw new InvalidArgumentException('Üyelik süresi dolmuştur.');
                    }

                    // Verify available remaining credits/sessions
                    $plan = $this->db->get_where('membership_plans', ['id' => $cm['id_membership_plans']])->row_array();
                    if ($plan && $plan['sessions_per_period'] !== null && empty($plan['is_unlimited'])) {
                        $sessions_used = (int) $cm['sessions_used_this_period'];
                        $sessions_total = (int) $plan['sessions_per_period'];
                        if ($sessions_used >= $sessions_total) {
                            throw new InvalidArgumentException('Bu dönem için üyelik seans hakkı tükenmiştir.');
                        }
                    }

                    // Deduct credit/session from customer_memberships
                    $this->db->set('sessions_used_this_period', 'sessions_used_this_period + 1', false);
                    $this->db->where('id', $id_customer_memberships);
                    $this->db->update('customer_memberships');

                    if ($appointment_id) {
                        $existing_s = $this->db->get_where('customer_membership_sessions', ['id_appointments' => $appointment_id])->num_rows();
                        if ($existing_s === 0) {
                            $this->db->insert('customer_membership_sessions', [
                                'id_customer_memberships' => $id_customer_memberships,
                                'id_appointments' => $appointment_id,
                                'consumed_at' => $now,
                            ]);
                        }
                    }
                } else {
                    // If not found in customer_memberships, check customer_packages
                    $id_customer_packages = $id_customer_memberships;
                }
            }

            // Package deduction if customer_packages is used
            if ($payment_method === 'package' && $id_customer_packages) {
                if (empty($customer_id)) {
                    throw new InvalidArgumentException('Paket ile ödeme yapabilmek için adisyona kayıtlı bir müşteri atanmalıdır.');
                }
                $pkg = $this->db->get_where('customer_packages', ['id' => $id_customer_packages])->row_array();
                if (!$pkg) {
                    throw new InvalidArgumentException('Geçersiz paket ID: ' . $id_customer_packages);
                }
                if ((int) $pkg['id_users_customer'] !== $customer_id) {
                    throw new InvalidArgumentException('Seçilen paket bu müşteriye ait değil.');
                }
                if ($pkg['status'] !== 'active') {
                    throw new InvalidArgumentException('Paket aktif değil (Durum: ' . $pkg['status'] . ').');
                }
                if (!empty($pkg['expires_at']) && strtotime($pkg['expires_at']) < time()) {
                    throw new InvalidArgumentException('Paketin kullanım süresi dolmuştur.');
                }
                if ((int) $pkg['used_sessions'] >= (int) $pkg['total_sessions']) {
                    throw new InvalidArgumentException('Paket seans hakkı tükenmiştir.');
                }

                $this->db->set('used_sessions', 'used_sessions + 1', false);
                $this->db->where('id', $id_customer_packages);
                $this->db->update('customer_packages');

                if ($appointment_id) {
                    $existing_ps = $this->db->get_where('customer_package_sessions', ['id_appointments' => $appointment_id])->num_rows();
                    if ($existing_ps === 0) {
                        $this->db->insert('customer_package_sessions', [
                            'id_customer_packages' => $id_customer_packages,
                            'id_appointments' => $appointment_id,
                            'consumed_at' => $now,
                        ]);
                    }
                }

                if ($this->db->table_exists('package_usage_logs')) {
                    $this->db->insert('package_usage_logs', [
                        'id_customer_packages' => $id_customer_packages,
                        'id_appointments' => $appointment_id,
                        'sessions_deducted' => 1,
                        'remaining_after' => max(0, (int) $pkg['total_sessions'] - (int) $pkg['used_sessions'] - 1),
                        'action' => 'deducted',
                        'performed_by' => $received_by,
                        'notes' => $notes ?: ('Adisyon #' . $adisyon_id . ' ödemesi'),
                        'created_at' => $now,
                    ]);
                }
            }

            // 2. Gift Card validation & deduction
            if ($payment_method === 'gift_card') {
                if (empty($gift_card_code)) {
                    throw new InvalidArgumentException('Hediye kartı kodu gereklidir.');
                }
                $card = $this->db->get_where('gift_cards', ['code' => strtoupper($gift_card_code)])->row_array();
                if (!$card) {
                    throw new InvalidArgumentException('Geçersiz hediye kartı kodu: ' . $gift_card_code);
                }
                if (!empty($card['expires_at']) && $card['expires_at'] < date('Y-m-d')) {
                    $this->db->where('id', $card['id'])->update('gift_cards', [
                        'status' => 'expired',
                        'updated_at' => $now,
                    ]);
                    throw new InvalidArgumentException('Hediye kartının kullanım süresi dolmuştur.');
                }
                if ($card['status'] !== 'active') {
                    throw new InvalidArgumentException('Hediye kartı aktif değil (Durum: ' . $card['status'] . ').');
                }
                $card_balance = (float) $card['current_balance'];
                if ($card_balance < $amount) {
                    throw new InvalidArgumentException('Hediye kartında yetersiz bakiye. Mevcut: ₺' . number_format($card_balance, 2));
                }

                $new_card_balance = round($card_balance - $amount, 2);
                $new_card_status = ($new_card_balance <= 0.001) ? 'depleted' : 'active';

                $this->db->where('id', $card['id'])->update('gift_cards', [
                    'current_balance' => $new_card_balance,
                    'status' => $new_card_status,
                    'updated_at' => $now,
                ]);

                if ($this->db->table_exists('gift_card_redemptions')) {
                    $redemption_data = [
                        'id_gift_cards' => $card['id'],
                        'id_appointments' => $appointment_id,
                        'id_adisyons' => $adisyon_id,
                        'redeemed_amount' => $amount,
                        'redeemed_at' => $now,
                    ];
                    if ($this->db->field_exists('notes', 'gift_card_redemptions')) {
                        $redemption_data['notes'] = $notes ?: ('Adisyon #' . $adisyon_id . ' ödemesi (' . $card['code'] . ')');
                    }
                    $this->db->insert('gift_card_redemptions', $redemption_data);
                }

                if ($this->db->table_exists('gift_card_transactions')) {
                    $this->db->insert('gift_card_transactions', [
                        'id_gift_cards' => $card['id'],
                        'amount' => $amount,
                        'notes' => $notes ?: ('Adisyon #' . $adisyon_id . ' ödemesi (' . $card['code'] . ')'),
                        'created_at' => $now,
                    ]);
                }

                $notes = ($notes ? ($notes . ' | ') : '') . 'Hediye Kartı: ' . $card['code'];
            }

            // Handle Bank Transfer / IBAN selection
            if ($payment_method === 'bank_transfer') {
                $bank_id = (int) ($post['bank_account_id'] ?? 0);
                if ($bank_id > 0) {
                    $ba = $this->db->get_where('bank_accounts', ['id' => $bank_id])->row_array();
                    if ($ba) {
                        $bank_desc = 'Havale/EFT: ' . $ba['bank_name'] . ' (IBAN: ' . $ba['iban'] . ') - Alıcı: ' . $ba['account_name'];
                        $notes = ($notes ? ($notes . ' | ') : '') . $bank_desc;
                    }
                }
            }

            // Check ÖKC connection and handle POS receipt
            $okc_receipt = null;
            $okc_warning = null;
            $connected_terminals = $this->get_connected_okc_terminals();

            if (!empty($post['send_to_pos']) || $payment_method === 'okc_pos') {
                if (empty($connected_terminals)) {
                    // No ÖKC connected! Warn and do NOT generate fake receipt
                    $okc_warning = 'Sistemde bağlı veya tanımlı bir ÖKC cihazı bulunamadı. Tahsilat fiziki kart ödemesi olarak kaydedildi ancak mali fiş cihazdan basılamadı.';
                    $notes = ($notes ? ($notes . ' | ') : '') . 'Kredi Kartı (Bağlı ÖKC yok, manuel tahsilat)';
                } else {
                    $chosen_terminal = $connected_terminals[0];
                    $selected_terminal_id = $post['pos_terminal_id'] ?? 'default';
                    foreach ($connected_terminals as $t) {
                        if ($t['id'] === $selected_terminal_id || $t['terminal_id'] === $selected_terminal_id) {
                            $chosen_terminal = $t;
                            break;
                        }
                    }
                    $terminal_label = $chosen_terminal['name'] . ' (ID: ' . $chosen_terminal['terminal_id'] . ')';
                    $okc_receipt_no = 'ÖKC-' . date('ymd') . '-' . mt_rand(1000, 9999);
                    $okc_z_no = 'Z-' . date('Ym') . '-' . sprintf('%03d', mt_rand(1, 150));
                    $notes = ($notes ? ($notes . ' | ') : '') . 'POS/ÖKC: ' . $terminal_label . ' - Fiş: ' . $okc_receipt_no . ' (' . $okc_z_no . ')';
                    $okc_receipt = [
                        'receipt_no' => $okc_receipt_no,
                        'z_no' => $okc_z_no,
                        'terminal' => $chosen_terminal['name'],
                        'terminal_id' => $chosen_terminal['terminal_id'],
                        'amount' => $amount,
                        'time' => date('H:i:s'),
                    ];
                }
            }

            // 3. Insert payment row
            $payment_row = [
                'id_adisyons' => $adisyon_id,
                'payment_method' => ($payment_method === 'okc_pos') ? 'card' : $payment_method,
                'amount' => $amount,
                'id_customer_packages' => $id_customer_packages,
                'id_customer_memberships' => $id_customer_memberships,
                'notes' => $notes,
                'received_by' => $received_by,
                'created_at' => $now,
            ];
            $this->db->insert('adisyon_payments', $payment_row);
            $payment_id = $this->db->insert_id();

            // Cash register if cash
            if ($payment_method === 'cash') {
                $open_register = $this->db->get_where('cash_registers', ['status' => 'open'])->row_array();
                if ($open_register) {
                    $this->db->set('current_balance', 'current_balance + ' . $amount, false);
                    $this->db->set('total_cash_in', 'total_cash_in + ' . $amount, false);
                    $this->db->where('id', $open_register['id']);
                    $this->db->update('cash_registers');
                }
            }

            // Recalculate adisyon paid_amount and payment_status
            $total_paid = (float) $this->db
                ->select_sum('amount')
                ->where('id_adisyons', $adisyon_id)
                ->get('adisyon_payments')
                ->row()->amount;

            $status = ($total_paid >= (float) $adisyon['total_amount']) ? 'paid' : 'partially_paid';

            $this->db->update('adisyons', [
                'paid_amount' => $total_paid,
                'payment_status' => $status,
                'updated_at' => $now,
            ], ['id' => $adisyon_id]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                throw new RuntimeException('Ödeme kaydedilirken veritabanı hatası oluştu.');
            }

            $updated_adisyon = $this->adisyons_model->find($adisyon_id);
            $remaining = max(0.00, round((float)$updated_adisyon['total_amount'] - (float)$updated_adisyon['paid_amount'], 2));

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'payment_id' => $payment_id,
                    'okc_receipt' => $okc_receipt,
                    'okc_warning' => $okc_warning,
                    'remaining_amount' => $remaining,
                    'adisyon' => $updated_adisyon,
                ]));
        } catch (Throwable $e) {
            if ($this->db->trans_status() === false || $this->db->trans_enabled) {
                $this->db->trans_rollback();
            }
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Delete an individual payment record and revert deduction/paid amounts.
     */
    public function remove_payment(int $payment_id): void
    {
        $this->ensure_authenticated();
        try {
            $payment = $this->db->get_where('adisyon_payments', ['id' => $payment_id])->row_array();
            if (!$payment) {
                throw new InvalidArgumentException('Ödeme kaydı bulunamadı.');
            }

            $adisyon_id = (int) $payment['id_adisyons'];
            $this->db->trans_start();

            // Revert package session if applicable
            if (!empty($payment['id_customer_packages'])) {
                $this->db->set('used_sessions', 'GREATEST(0, used_sessions - 1)', false);
                $this->db->where('id', (int) $payment['id_customer_packages']);
                $this->db->update('customer_packages');
            }

            // Revert membership session if applicable
            if (!empty($payment['id_customer_memberships'])) {
                $this->db->set('sessions_used_this_period', 'GREATEST(0, sessions_used_this_period - 1)', false);
                $this->db->where('id', (int) $payment['id_customer_memberships']);
                $this->db->update('customer_memberships');
            }

            // Delete payment
            $this->db->delete('adisyon_payments', ['id' => $payment_id]);

            // Recalculate
            $total_paid = (float) $this->db
                ->select_sum('amount')
                ->where('id_adisyons', $adisyon_id)
                ->get('adisyon_payments')
                ->row()->amount;

            $adisyon = $this->adisyons_model->find($adisyon_id);
            $new_status = 'unpaid';
            if ($total_paid >= (float) $adisyon['total_amount'] && (float) $adisyon['total_amount'] > 0) {
                $new_status = 'paid';
            } elseif ($total_paid > 0) {
                $new_status = 'partially_paid';
            }

            $this->db->where('id', $adisyon_id)->update('adisyons', [
                'paid_amount' => $total_paid,
                'payment_status' => $new_status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->trans_complete();

            $updated_adisyon = $this->adisyons_model->find($adisyon_id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'message' => 'Tahsilat kaydı silindi.',
                    'adisyon' => $updated_adisyon,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Record payment alias.
     */
    public function pay(): void
    {
        $this->add_payment();
    }

    /**
     * Close adisyon & deduct inventory/consumables.
     */
    public function close(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        try {
            $this->adisyons_model->close($adisyon_id);
            $adisyon = $this->adisyons_model->find($adisyon_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Create or open adisyon for an appointment and redirect to adisyons page.
     */
    public function create_for_appointment(int $appointment_id): void
    {
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        try {
            $adisyon = $this->adisyons_model->get_or_create_for_appointment($appointment_id);
            redirect('adisyons?open_id=' . $adisyon['id']);
        } catch (Throwable $e) {
            $this->session->set_flashdata('error_message', $e->getMessage());
            redirect('adisyons');
        }
    }

    /**
     * Get or create adisyon for an appointment (API - returns JSON).
     */
    public function get_for_appointment(int $appointment_id): void
    {
        $this->ensure_authenticated();
        try {
            $adisyon = $this->adisyons_model->get_or_create_for_appointment($appointment_id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Convert adisyon to Invoice with optional ERP sync.
     */
    public function create_invoice(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        $send_to_erp = (bool) ($this->input->post('send_to_erp') ?: $this->input->get('send_to_erp'));
        $erp_provider = $this->input->post('erp_provider') ?: $this->input->get('erp_provider') ?: null;

        try {
            $result = $this->adisyons_model->convert_to_invoice($adisyon_id, $send_to_erp, $erp_provider);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'invoice_id' => $result['invoice_id'],
                    'adisyon_id' => $result['adisyon_id'],
                    'erp_synced' => $result['erp_synced'],
                    'erp_message' => $result['erp_message'],
                    'message' => 'Fatura başarıyla oluşturuldu.' . ($result['erp_synced'] ? ' (' . $result['erp_message'] . ')' : ''),
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Bulk convert adisyons to Invoices with optional ERP sync.
     */
    public function bulk_create_invoices(): void
    {
        $this->ensure_authenticated();
        $raw_ids = $this->input->post('adisyon_ids');
        $adisyon_ids = is_array($raw_ids) ? $raw_ids : (!empty($raw_ids) ? explode(',', (string) $raw_ids) : []);
        $send_to_erp = (bool) $this->input->post('send_to_erp');
        $erp_provider = $this->input->post('erp_provider') ?: null;

        if (empty($adisyon_ids)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Lütfen en az bir adisyon seçin.']));
            return;
        }

        try {
            $result = $this->adisyons_model->bulk_convert_to_invoices($adisyon_ids, $send_to_erp, $erp_provider);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'total_processed' => $result['total_processed'],
                    'created_count' => $result['created_count'],
                    'erp_synced_count' => $result['erp_synced_count'],
                    'results' => $result['results'],
                    'errors' => $result['errors'],
                    'message' => $result['created_count'] . ' adet adisyon başarıyla faturalandırıldı.' . ($result['erp_synced_count'] > 0 ? ' (' . $result['erp_synced_count'] . ' adedi ERP ile eşitlendi)' : ''),
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Thermal / POS slip print view.
     */
    public function print_slip(?int $adisyon_id = null): void
    {
        $this->ensure_authenticated();
        if (!$adisyon_id || $adisyon_id <= 0) {
            show_error('Geçersiz veya eksik adisyon numarası.', 400);
            return;
        }

        try {
            $adisyon = $this->adisyons_model->find($adisyon_id);
            $format = $this->input->get('format') ?: '80mm';
            $width = $this->input->get('width') ?: ($format === '58mm' ? '58' : '80');
            $this->load->view('pages/adisyon_print_slip', [
                'adisyon' => $adisyon,
                'format' => $format,
                'width' => $width,
                'company_name' => setting('company_name') ?: 'İşletme',
                'company_phone' => setting('company_phone') ?: null,
                'company_address' => setting('company_address') ?: null,
            ]);
        } catch (Throwable $e) {
            show_error('Adisyon fişi bulunamadı: ' . $e->getMessage(), 404);
        }
    }

    /**
     * Reopen a closed adisyon for further edits or payments.
     */
    public function reopen(?int $adisyon_id = null): void
    {
        $this->ensure_authenticated();
        $id = $adisyon_id ?: (int) $this->input->post('id_adisyons');
        try {
            if (!$id || $id <= 0) {
                throw new InvalidArgumentException('Geçersiz adisyon ID.');
            }
            $this->adisyons_model->reopen($id);
            $adisyon = $this->adisyons_model->find($id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    
    public function split_payments(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        try {
            $this->load->model('Split_payments_model', 'split_payments_model');

            $payments = $this->split_payments_model->get_payments('adisyon', $adisyon_id);
            $totals = $this->split_payments_model->get_totals('adisyon', $adisyon_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'payments' => $payments,
                    'totals' => $totals
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]));
        }
    }

    public function add_split_payment(): void
    {
        $this->ensure_authenticated();
        $this->load->model('Split_payments_model', 'split_payments_model');

        $post = $this->input->post();
        if (empty($post)) {
            $post = json_decode($this->input->raw_input_stream, true) ?? [];
        }

        $data = [
            'entity_type' => $post['entity_type'] ?? 'adisyon',
            'entity_id' => (int) ($post['entity_id'] ?? $post['adisyon_id'] ?? 0),
            'payment_type' => $post['payment_type'] ?? 'cash',
            'amount' => (float) ($post['amount'] ?? 0.00),
            'discount_percent' => $post['discount_percent'] ?? null,
            'coupon_code' => $post['coupon_code'] ?? null,
            'notes' => $post['notes'] ?? $post['note'] ?? null,
        ];

        $payment_id = $this->split_payments_model->add_payment($data);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'payment_id' => $payment_id
            ]));
    }

    public function remove_split_payment(int $payment_id): void
    {
        $this->ensure_authenticated();
        $this->load->model('Split_payments_model', 'split_payments_model');

        $success = $this->split_payments_model->remove_payment($payment_id);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => $success ? 'success' : 'error'
            ]));
    }

    public function finalize_split_payment(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        $this->load->model('Split_payments_model', 'split_payments_model');
        $this->load->model('adisyons_model');

        $adisyon = $this->adisyons_model->find($adisyon_id);
        if (!$adisyon) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Adisyon not found']));
            return;
        }

        $raw = file_get_contents('php://input');
        $payload = !empty($raw) ? json_decode($raw, true) : $this->input->post();
        if (!is_array($payload)) {
            $payload = [];
        }

        $totals = $this->split_payments_model->get_totals('adisyon', $adisyon_id);
        $payments = $this->split_payments_model->get_payments('adisyon', $adisyon_id);
        
        $total_amount = (float) ($adisyon['total_amount'] ?? 0);

        $this->db->trans_start();
        
        // Sync to adisyon_payments table
        $this->db->where('id_adisyons', $adisyon_id)->delete('adisyon_payments');

        $total_paid = 0.0;
        foreach ($payments as $payment) {
            $p_type = $payment['payment_type'];
            if (in_array($p_type, ['cash', 'card', 'credit_card', 'transfer', 'gift_card', 'membership'])) {
                $mapped_type = $p_type;
                if ($p_type === 'credit_card') $mapped_type = 'card';
                if ($p_type === 'gift_card') $mapped_type = 'cash';
                if ($p_type === 'membership') $mapped_type = 'cash';
                
                $this->db->insert('adisyon_payments', [
                    'id_adisyons' => $adisyon_id,
                    'payment_method' => $mapped_type,
                    'amount' => $payment['amount'],
                    'notes' => $payment['notes'],
                    'received_by' => $payment['received_by'],
                    'created_at' => $payment['created_at']
                ]);
                $total_paid += (float) $payment['amount'];
            }
        }

        $status = ($total_paid >= $total_amount) ? 'paid' : 'partially_paid';
        if ($total_paid <= 0) {
            $status = 'unpaid';
        }

        $this->db->where('id', $adisyon_id)->update('adisyons', [
            'payment_status' => $status,
            'paid_amount' => $total_paid,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $close_table = ($this->input->post('close_table') == '1' || ($payload['close_table'] ?? '') == '1' || ($payload['close_table'] ?? '') === true);
        $table_closed = false;
        if ($close_table && $status === 'paid' && $this->db->table_exists('restaurant_tables')) {
            $table = $this->db->get_where('restaurant_tables', ['current_id_adisyons' => $adisyon_id])->row_array();
            if ($table) {
                $this->db->where('id', (int) $table['id'])->update('restaurant_tables', [
                    'status' => 'cleaning',
                    'current_id_adisyons' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $table_closed = true;
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Database error']));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'payment_status' => $status,
                'paid_amount' => $total_paid,
                'table_closed' => $table_closed,
            ]));
    }

    /**
     * Get active/connected ÖKC terminals.
     */
    protected function get_connected_okc_terminals(): array
    {
        $terminals = [];

        // 1. From bank_accounts where pos_terminal_id is not empty
        if ($this->db->table_exists('bank_accounts')) {
            $pos_rows = $this->db
                ->where('is_active', 1)
                ->where('pos_terminal_id IS NOT NULL', null, false)
                ->get('bank_accounts')
                ->result_array();

            foreach ($pos_rows as $row) {
                $term_id = trim((string)($row['pos_terminal_id'] ?? ''));
                if ($term_id !== '') {
                    $terminals[] = [
                        'id' => 'pos_' . $row['id'],
                        'name' => ($row['bank_name'] ?: 'Fiziki POS') . ' (' . ($row['pos_provider'] ?: 'ÖKC') . ')',
                        'terminal_id' => $term_id,
                        'provider' => $row['pos_provider'] ?: 'ÖKC',
                        'type' => 'physical_pos'
                    ];
                }
            }
        }

        // 2. From payment_settings
        if ($this->db->table_exists('payment_settings')) {
            $ps = $this->db->get_where('payment_settings', ['id' => 1])->row_array();
            if ($ps) {
                $odeal = trim((string)($ps['odeal_terminal_id'] ?? ''));
                if ($odeal !== '') {
                    $terminals[] = [
                        'id' => 'odeal_' . $odeal,
                        'name' => 'ÖdeAl Yeni Nesil ÖKC Terminali',
                        'terminal_id' => $odeal,
                        'provider' => 'ÖdeAl ÖKC',
                        'type' => 'odeal'
                    ];
                }
                $garanti = trim((string)($ps['garanti_terminal_id'] ?? ''));
                if ($garanti !== '') {
                    $terminals[] = [
                        'id' => 'garanti_' . $garanti,
                        'name' => 'Garanti BBVA POS Terminali',
                        'terminal_id' => $garanti,
                        'provider' => 'Garanti',
                        'type' => 'garanti'
                    ];
                }
                $enpara = trim((string)($ps['enpara_terminal_id'] ?? ''));
                if ($enpara !== '') {
                    $terminals[] = [
                        'id' => 'enpara_' . $enpara,
                        'name' => 'Enpara POS Terminali',
                        'terminal_id' => $enpara,
                        'provider' => 'Enpara',
                        'type' => 'enpara'
                    ];
                }
            }
        }

        return $terminals;
    }

    protected function ensure_authenticated(): void
    {
        if (!session('user_id')) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
            exit;
        }

        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
            exit;
        }
    }
}
