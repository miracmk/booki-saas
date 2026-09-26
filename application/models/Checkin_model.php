<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Check-in / Check-out Model (Operational Entry/Exit, Kiosk & Occupancy)
 * ---------------------------------------------------------------------------- */

class Checkin_model extends App_Model
{
    /**
     * Unified Identifier Resolver (resolves phone, QR token, appointment hash, membership token, customer ID, or URL).
     *
     * @param string $identifier
     * @return array ['customer_id' => ?int, 'appointment_id' => ?int, 'membership_id' => ?int]
     */
    public function resolve_identifier(string $identifier): array
    {
        $raw = trim($identifier);
        if (empty($raw)) {
            return ['customer_id' => null, 'appointment_id' => null, 'membership_id' => null];
        }

        $this->load->model('customers_model');
        $customer_id = null;
        $appointment_id = null;
        $membership_id = null;

        // 1. If identifier is a URL, extract potential hash/token from URL path
        if (filter_var($raw, FILTER_VALIDATE_URL) || strpos($raw, 'http://') === 0 || strpos($raw, 'https://') === 0) {
            $path = parse_url($raw, PHP_URL_PATH);
            $parts = array_values(array_filter(explode('/', trim((string) $path, '/'))));
            $possible_token = end($parts);
            if (!empty($possible_token)) {
                $raw = $possible_token;
            }
        }

        // 2. Formatted prefixes (e.g. CUST:123, APPT:456, MEMB:789)
        if (preg_match('/^(?:CUST|CUSTOMER):(\d+)$/i', $raw, $m)) {
            $customer_id = (int) $m[1];
        } elseif (preg_match('/^(?:APPT|APPOINTMENT):(\d+)$/i', $raw, $m)) {
            $appt = $this->db->get_where('appointments', ['id' => (int) $m[1]])->row_array();
            if ($appt) {
                $appointment_id = (int) $appt['id'];
                $customer_id = (int) $appt['id_users_customer'];
            }
        } elseif (preg_match('/^(?:MEMB|MEMBERSHIP):(\d+)$/i', $raw, $m)) {
            $memb = $this->db->get_where('customer_memberships', ['id' => (int) $m[1]])->row_array();
            if ($memb) {
                $membership_id = (int) $memb['id'];
                $customer_id = (int) $memb['id_users_customer'];
            }
        }

        // 3. Check appointment hash (e.g. standard appointment confirmation QR)
        if (!$customer_id) {
            $appt = $this->db->get_where('appointments', ['hash' => $raw])->row_array();
            if ($appt) {
                $appointment_id = (int) $appt['id'];
                $customer_id = (int) $appt['id_users_customer'];
            }
        }

        // 4. Check customer membership qr_code_token
        if (!$customer_id) {
            $memb = $this->db->get_where('customer_memberships', ['qr_code_token' => $raw])->row_array();
            if ($memb) {
                $membership_id = (int) $memb['id'];
                $customer_id = (int) $memb['id_users_customer'];
            }
        }

        // 5. Check numeric customer ID (if short 1-6 digits and not a phone number)
        if (!$customer_id && is_numeric($raw) && strlen($raw) <= 6 && (int)$raw > 0) {
            $cust_check = $this->db->get_where('users', ['id' => (int) $raw, 'id_roles' => 3])->row_array();
            if ($cust_check) {
                $customer_id = (int) $cust_check['id'];
            }
        }

        // 6. Check phone number (Phone numbers with PII hash matching or decrypted search)
        if (!$customer_id) {
            $clean_digits = preg_replace('/[^\d]/', '', $raw);
            $last10 = substr($clean_digits, -10);

            if (!empty($last10)) {
                $candidates = array_unique(array_filter([
                    $raw,
                    $clean_digits,
                    $last10,
                    '0' . $last10,
                    '+90' . $last10,
                    '90' . $last10,
                    '0090' . $last10,
                    (strlen($last10) === 10) ? sprintf('0%s %s %s %s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 2), substr($last10, 8, 2)) : null,
                    (strlen($last10) === 10) ? sprintf('%s %s %s %s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 2), substr($last10, 8, 2)) : null,
                    (strlen($last10) === 10) ? sprintf('0%s %s %s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 4)) : null,
                    (strlen($last10) === 10) ? sprintf('(%s) %s %s %s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 2), substr($last10, 8, 2)) : null,
                    (strlen($last10) === 10) ? sprintf('(%s) %s %s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 4)) : null,
                    (strlen($last10) === 10) ? sprintf('0%s-%s-%s-%s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 2), substr($last10, 8, 2)) : null,
                    (strlen($last10) === 10) ? sprintf('%s-%s-%s-%s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 2), substr($last10, 8, 2)) : null,
                ]));

                // A. PII Hash matching
                $hashes = [];
                if (function_exists('sf_pii_hash')) {
                    foreach ($candidates as $cand) {
                        $h = sf_pii_hash($cand);
                        if ($h) {
                            $hashes[] = $h;
                        }
                    }
                    $hashes = array_unique($hashes);
                }

                $customer_row = null;
                if (!empty($hashes)) {
                    $customer_row = $this->db
                        ->where_in('phone_number_hash', $hashes)
                        ->get('users')
                        ->row_array();
                }

                // B. Plaintext match
                if (!$customer_row && !empty($candidates)) {
                    $customer_row = $this->db
                        ->group_start()
                        ->where_in('phone_number', $candidates)
                        ->or_where_in('mobile_number', $candidates)
                        ->group_end()
                        ->get('users')
                        ->row_array();
                }

                // C. Decrypted customer scan fallback
                if (!$customer_row && !empty($last10)) {
                    $all_customers = $this->customers_model->get();
                    foreach ($all_customers as $c) {
                        $c_phone = is_array($c) ? ($c['phone_number'] ?? '') : ($c->phone_number ?? '');
                        $cust_clean = preg_replace('/[^\d]/', '', $c_phone);
                        if (!empty($cust_clean) && substr($cust_clean, -10) === $last10) {
                            $customer_row = is_array($c) ? $c : (array) $c;
                            break;
                        }
                    }
                }

                if ($customer_row) {
                    $customer_id = (int) $customer_row['id'];
                }
            }
        }

        return [
            'customer_id' => $customer_id,
            'appointment_id' => $appointment_id,
            'membership_id' => $membership_id,
        ];
    }

    /**
     * Find customer by phone number (helper for compatibility).
     */
    public function find_customer_by_phone(string $phone)
    {
        $res = $this->resolve_identifier($phone);
        if (!empty($res['customer_id'])) {
            $this->load->model('customers_model');
            try {
                $cust = $this->customers_model->find((int) $res['customer_id']);
                return is_array($cust) ? (object) $cust : $cust;
            } catch (Throwable $e) {
                return $this->db->get_where('users', ['id' => (int) $res['customer_id']])->row();
            }
        }
        return null;
    }

    /**
     * Unified Kiosk handler for Check-in & Check-out by phone, QR or customer ID.
     */
    public function kiosk_process(string $identifier, string $action = 'auto', string $method = 'kiosk'): array
    {
        $identifier = trim($identifier);
        if (empty($identifier)) {
            throw new InvalidArgumentException('Lütfen geçerli bir telefon numarası veya QR kod okutun.');
        }

        $resolved = $this->resolve_identifier($identifier);
        $customer_id = $resolved['customer_id'] ?? null;
        $appointment_id = $resolved['appointment_id'] ?? null;
        $membership_id = $resolved['membership_id'] ?? null;

        if (!$customer_id) {
            throw new InvalidArgumentException('Müşteri veya randevu kaydı bulunamadı. Lütfen bilgilerinizi kontrol ediniz.');
        }

        // Fetch decrypted customer profile
        $this->load->model('customers_model');
        try {
            $customer = $this->customers_model->find($customer_id);
        } catch (Throwable $e) {
            $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();
        }

        if (!$customer) {
            throw new InvalidArgumentException('Müşteri kaydı bulunamadı.');
        }

        $cust_name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Değerli Müşterimiz';
        $safe_customer = [
            'id' => (int) $customer_id,
            'first_name' => (string) ($customer['first_name'] ?? ''),
            'last_name' => (string) ($customer['last_name'] ?? ''),
        ];

        // Check active inside status
        $active_log = $this->db
            ->get_where('checkin_logs', [
                'id_users_customer' => $customer_id,
                'status' => 'inside',
            ])
            ->row_array();

        // 1. Explicit CHECK-OUT requested
        if ($action === 'checkout') {
            if (!$active_log) {
                return [
                    'status' => 'not_inside',
                    'customer' => $safe_customer,
                    'message' => "Sayın {$cust_name}, şu an aktif bir giriş kaydınız bulunmuyor.",
                ];
            }
            $checkout_res = $this->check_out((int) $active_log['id']);
            return [
                'status' => 'success',
                'action' => 'checkout',
                'checkin_id' => $active_log['id'],
                'customer' => $safe_customer,
                'duration_minutes' => $checkout_res['duration_minutes'],
                'exit_time' => $checkout_res['exit_time'],
                'message' => "✓ Güle güle Sayın {$cust_name}! İçeride geçirilen süre: {$checkout_res['duration_minutes']} dakika. İyi günler dileriz.",
            ];
        }

        // 2. Explicit CHECK-IN requested
        if ($action === 'checkin') {
            if ($active_log) {
                $entry_time = date('H:i', strtotime($active_log['entry_timestamp']));
                return [
                    'status' => 'already_inside',
                    'action' => 'checkin',
                    'checkin_id' => (int) $active_log['id'],
                    'customer' => $safe_customer,
                    'entry_time' => $entry_time,
                    'message' => "Sayın {$cust_name}, zaten içeridesiniz (Giriş: {$entry_time}).",
                ];
            }
            $checkin_res = $this->check_in([
                'id_users_customer' => $customer_id,
                'id_appointments' => $appointment_id,
                'id_customer_memberships' => $membership_id,
                'checkin_method' => $method,
            ]);
            $checkin_res['action'] = 'checkin';
            $checkin_res['customer'] = $safe_customer;
            $checkin_res['message'] = "✓ Hoş geldiniz Sayın {$cust_name}! Girişiniz başarıyla yapıldı.";
            return $checkin_res;
        }

        // 3. AUTO (Toggle) Mode
        if ($active_log) {
            $checkout_res = $this->check_out((int) $active_log['id']);
            return [
                'status' => 'success',
                'action' => 'checkout',
                'checkin_id' => $active_log['id'],
                'customer' => $safe_customer,
                'duration_minutes' => $checkout_res['duration_minutes'],
                'exit_time' => $checkout_res['exit_time'],
                'message' => "✓ Güle güle Sayın {$cust_name}! Çıkışınız yapıldı (Süre: {$checkout_res['duration_minutes']} dk).",
            ];
        } else {
            $checkin_res = $this->check_in([
                'id_users_customer' => $customer_id,
                'id_appointments' => $appointment_id,
                'id_customer_memberships' => $membership_id,
                'checkin_method' => $method,
            ]);
            $checkin_res['action'] = 'checkin';
            $checkin_res['customer'] = $safe_customer;
            $checkin_res['message'] = "✓ Hoş geldiniz Sayın {$cust_name}! Girişiniz başarıyla yapıldı.";
            return $checkin_res;
        }
    }

    /**
     * Perform check-in by customer ID, phone, QR code or appointment ID.
     */
    public function check_in(array $params): array
    {
        $customer_id = !empty($params['id_users_customer']) ? (int) $params['id_users_customer'] : null;
        $phone = !empty($params['phone']) ? trim($params['phone']) : null;
        $qr_token = !empty($params['qr_token']) ? trim($params['qr_token']) : null;
        $appointment_id = !empty($params['id_appointments']) ? (int) $params['id_appointments'] : null;
        $method = $params['checkin_method'] ?? 'manual';
        $now = date('Y-m-d H:i:s');

        // Locate customer if not directly provided
        if (!$customer_id) {
            $identifier = $qr_token ?: $phone;
            if ($identifier) {
                $resolved = $this->resolve_identifier($identifier);
                $customer_id = $resolved['customer_id'];
                if (!empty($resolved['appointment_id']) && !$appointment_id) {
                    $appointment_id = $resolved['appointment_id'];
                }
                if (!empty($resolved['membership_id']) && empty($params['id_customer_memberships'])) {
                    $params['id_customer_memberships'] = $resolved['membership_id'];
                }
            } elseif ($appointment_id) {
                $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
                if ($appt) {
                    $customer_id = (int) $appt['id_users_customer'];
                }
            }
        }

        if (!$customer_id) {
            throw new InvalidArgumentException('Müşteri bulunamadı. Lütfen geçerli bir telefon veya kayıtlı numaranızı girin.');
        }

        // Fetch decrypted customer profile
        $this->load->model('customers_model');
        try {
            $customer = $this->customers_model->find($customer_id);
        } catch (Throwable $e) {
            $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();
        }
        if (!$customer) {
            throw new InvalidArgumentException('Müşteri kaydı bulunamadı.');
        }

        // Auto-link today's active appointment for this customer if not explicitly passed
        if (!$appointment_id) {
            $today_start = date('Y-m-d 00:00:00');
            $today_end = date('Y-m-d 23:59:59');
            $today_appt = $this->db
                ->where('id_users_customer', $customer_id)
                ->where('start_datetime >=', $today_start)
                ->where('start_datetime <=', $today_end)
                ->order_by('start_datetime', 'ASC')
                ->get('appointments')
                ->row_array();
            if ($today_appt) {
                $appointment_id = (int) $today_appt['id'];
            }
        }

        // Check if already checked in
        $active_checkin = $this->db
            ->get_where('checkin_logs', [
                'id_users_customer' => $customer_id,
                'status' => 'inside',
            ])
            ->row_array();

        if ($active_checkin) {
            $cust_name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            return [
                'status' => 'already_inside',
                'message' => $cust_name . ' zaten içeride (Giriş: ' . date('H:i', strtotime($active_checkin['entry_timestamp'])) . ')',
                'checkin_log' => $active_checkin,
                'customer' => $customer,
            ];
        }

        // Find active membership
        $membership = $this->db
            ->select('cm.*, mp.name as plan_name, mp.sessions_per_period, mp.is_unlimited')
            ->from('customer_memberships cm')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->where('cm.id_users_customer', $customer_id)
            ->where('cm.status', 'active')
            ->get()
            ->row_array();

        // Find active packages
        $packages = $this->db
            ->select('cp.*, s.name as service_name')
            ->from('customer_packages cp')
            ->join('services s', 's.id = cp.id_services', 'left')
            ->where('cp.id_users_customer', $customer_id)
            ->where('cp.status', 'active')
            ->where('cp.used_sessions < cp.total_sessions', NULL, FALSE)
            ->get()
            ->result_array();

        $this->db->trans_start();
        $this->db->insert('checkin_logs', [
            'id_users_customer' => $customer_id,
            'id_appointments' => $appointment_id,
            'id_customer_memberships' => $membership ? (int) $membership['id'] : null,
            'id_customer_packages' => !empty($params['id_customer_packages']) ? (int) $params['id_customer_packages'] : null,
            'id_restaurant_tables' => !empty($params['id_restaurant_tables']) ? (int) $params['id_restaurant_tables'] : null,
            'checkin_method' => $method,
            'entry_timestamp' => $now,
            'status' => 'inside',
            'checked_in_by' => !empty($params['checked_in_by']) ? (int) $params['checked_in_by'] : null,
            'notes' => $params['notes'] ?? null,
            'created_at' => $now,
        ]);
        $checkin_id = $this->db->insert_id();

        // If membership has session counter, increment this period
        if ($membership) {
            $this->db->set('sessions_used_this_period', 'sessions_used_this_period + 1', false);
            $this->db->where('id', $membership['id']);
            $this->db->update('customer_memberships');
        }

        // If appointment linked, mark check-in on appointment if field exists
        if ($appointment_id && $this->db->field_exists('is_checked_in', 'appointments')) {
            $this->db->update('appointments', ['is_checked_in' => 1], ['id' => $appointment_id]);
        }

        $this->db->trans_complete();

        return [
            'status' => 'success',
            'checkin_id' => $checkin_id,
            'customer' => $customer,
            'membership' => $membership,
            'packages' => $packages,
            'entry_time' => date('H:i', strtotime($now)),
        ];
    }

    /**
     * Perform check-out.
     */
    public function check_out(int $checkin_id): array
    {
        $checkin = $this->db->get_where('checkin_logs', ['id' => $checkin_id])->row_array();
        if (!$checkin) {
            throw new InvalidArgumentException('Giriş kaydı bulunamadı.');
        }

        $now = date('Y-m-d H:i:s');
        $duration = max(1, round((strtotime($now) - strtotime($checkin['entry_timestamp'])) / 60));

        $this->db->update('checkin_logs', [
            'exit_timestamp' => $now,
            'duration_minutes' => $duration,
            'status' => 'departed',
        ], ['id' => $checkin_id]);

        return [
            'status' => 'success',
            'checkin_id' => $checkin_id,
            'duration_minutes' => $duration,
            'exit_time' => date('H:i', strtotime($now)),
        ];
    }

    /**
     * Get live occupancy status (currently inside count, capacity, recent check-ins).
     */
    public function get_live_occupancy(): array
    {
        $result_inside = $this->db
            ->select('cl.*, 
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone,
                      mp.name as membership_plan_name,
                      rt.table_number, rt.name as table_name')
            ->from('checkin_logs cl')
            ->join('users c', 'c.id = cl.id_users_customer', 'left')
            ->join('customer_memberships cm', 'cm.id = cl.id_customer_memberships', 'left')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->join('restaurant_tables rt', 'rt.id = cl.id_restaurant_tables', 'left')
            ->where('cl.status', 'inside')
            ->order_by('cl.entry_timestamp DESC')
            ->get()
            ->result_array();

        if (function_exists('sf_pii_decrypt')) {
            foreach ($result_inside as &$guest) {
                if (!empty($guest['customer_phone'])) {
                    $guest['customer_phone'] = sf_pii_decrypt($guest['customer_phone']);
                }
            }
        }

        $capacity = (int) ($this->db->get_where('settings', ['name' => 'max_capacity'])->row()->value ?? 50);
        $current_count = count($result_inside);
        $occupancy_rate = $capacity > 0 ? min(100, round(($current_count / $capacity) * 100)) : 0;

        return [
            'current_count' => $current_count,
            'max_capacity' => $capacity,
            'occupancy_rate' => $occupancy_rate,
            'active_guests' => $result_inside,
        ];
    }

    /**
     * Get visit history with filtering.
     */
    public function get_history(?int $customer_id = null, ?string $date = null, int $limit = 50): array
    {
        $this->db
            ->select('cl.*, 
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone,
                      mp.name as membership_plan_name,
                      s.name as service_name')
            ->from('checkin_logs cl')
            ->join('users c', 'c.id = cl.id_users_customer', 'left')
            ->join('customer_memberships cm', 'cm.id = cl.id_customer_memberships', 'left')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->join('appointments a', 'a.id = cl.id_appointments', 'left')
            ->join('services s', 's.id = a.id_services', 'left');

        if ($customer_id) {
            $this->db->where('cl.id_users_customer', $customer_id);
        }

        if ($date) {
            $this->db->where('DATE(cl.entry_timestamp)', $date);
        }

        $logs = $this->db
            ->order_by('cl.entry_timestamp DESC')
            ->limit($limit)
            ->get()
            ->result_array();

        if (function_exists('sf_pii_decrypt')) {
            foreach ($logs as &$log) {
                if (!empty($log['customer_phone'])) {
                    $log['customer_phone'] = sf_pii_decrypt($log['customer_phone']);
                }
            }
        }

        return $logs;
    }
}
