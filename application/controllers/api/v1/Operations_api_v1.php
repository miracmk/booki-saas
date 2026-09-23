<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Live Operations, QR Check-in & Floor Plan API v1
 * ---------------------------------------------------------------------------- */

class Operations_api_v1 extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        $this->load->model('stations_model');
        $this->load->model('checkin_model');

        $this->load->library('api');
        $this->load->library('webhooks_client');
        $this->load->library('notifications');

        $this->api->auth();
    }

    /**
     * GET /api/v1/operations/live
     * Get real-time operational dashboard metrics, upcoming appointment countdowns, and active table timers.
     */
    public function live(): void
    {
        try {
            $today = date('Y-m-d');
            $now = new DateTime();
            $now_ts = $now->getTimestamp();

            // Fetch today's appointments
            $appointments = $this->db
                ->from('appointments')
                ->where('DATE(start_datetime)', $today)
                ->where_not_in('status', ['Cancelled', 'Draft'])
                ->order_by('start_datetime', 'ASC')
                ->get()
                ->result_array();

            $total = count($appointments);
            $pending = 0;
            $completed = 0;
            $active_tables_count = 0;

            $upcoming_countdowns = [];
            $active_table_timers = [];

            // Stations lookup
            $stations_raw = $this->stations_model->get();
            $stations_by_id = [];
            foreach ($stations_raw as $st) {
                $stations_by_id[(int)$st['id']] = $st;
            }

            foreach ($appointments as $appt) {
                $status = strtolower($appt['status'] ?? 'reserved');
                if (in_array($status, ['reserved', 'booked', 'beklemede', 'confirmed', 'onaylandı'], true)) {
                    $pending++;
                } elseif (in_array($status, ['completed', 'tamamlandı', 'closed'], true)) {
                    $completed++;
                }

                $start_dt = new DateTime($appt['start_datetime']);
                $end_dt = new DateTime($appt['end_datetime']);
                $start_ts = $start_dt->getTimestamp();
                $end_ts = $end_dt->getTimestamp();
                $duration_minutes = max(15, (int)(($end_ts - $start_ts) / 60));

                $service = $this->services_model->find($appt['id_services']);
                $customer = $this->customers_model->find($appt['id_users_customer']);
                $provider = $this->providers_model->find($appt['id_users_provider']);
                $station_id = (int)($appt['id_stations'] ?? 0);
                $station_name = $stations_by_id[$station_id]['name'] ?? ($appt['location'] ?? 'Masa Belirtilmemiş');

                $appt_dto = [
                    'id' => (int)$appt['id'],
                    'hash' => $appt['hash'] ?? (string)$appt['id'],
                    'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                    'customer_phone' => $customer['phone_number'] ?? '',
                    'customer_id' => (int)$appt['id_users_customer'],
                    'service_name' => $service['name'] ?? 'Hizmet',
                    'service_duration' => $duration_minutes,
                    'provider_name' => trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
                    'station_id' => $station_id,
                    'station_name' => $station_name,
                    'start_datetime' => $appt['start_datetime'],
                    'end_datetime' => $appt['end_datetime'],
                    'status' => $status,
                ];

                // Check for upcoming countdown (appointments starting in the future or within 10 min window)
                if ($start_ts > $now_ts && in_array($status, ['reserved', 'confirmed', 'booked'], true)) {
                    $remaining = $start_ts - $now_ts;
                    $appt_dto['remaining_seconds'] = $remaining;
                    $upcoming_countdowns[] = $appt_dto;
                }

                // Check for active session/table timer (status arrived, in_progress, checked-in)
                if (in_array($status, ['arrived', 'in_progress', 'geldi', 'başladı'], true)) {
                    $active_tables_count++;
                    $elapsed = max(0, $now_ts - $start_ts);
                    $appt_dto['elapsed_seconds'] = $elapsed;
                    $appt_dto['is_overdue'] = $elapsed > ($duration_minutes * 60);
                    $appt_dto['target_duration_seconds'] = $duration_minutes * 60;
                    $active_table_timers[] = $appt_dto;
                }
            }

            // Sort upcoming countdowns by closest first
            usort($upcoming_countdowns, fn($a, $b) => $a['remaining_seconds'] <=> $b['remaining_seconds']);

            $total_stations = count($stations_raw);
            $occupancy_rate = $total_stations > 0 ? round(($active_tables_count / $total_stations) * 100) : ($total > 0 ? round(($pending / max(1, $total)) * 100) : 0);

            // WhatsApp status check
            $whatsapp_active = !empty(setting('whatsapp_access_token')) || !empty(setting('whatsapp_phone_number_id'));

            $response = [
                'metrics' => [
                    'total_appointments' => $total,
                    'occupancy_rate' => $occupancy_rate,
                    'active_tables_count' => $active_tables_count,
                    'pending_count' => $pending,
                    'completed_count' => $completed,
                    'total_stations' => $total_stations,
                    'whatsapp_status' => $whatsapp_active,
                    'server_time' => $now->format('Y-m-d H:i:s'),
                ],
                'upcoming_countdowns' => $upcoming_countdowns,
                'active_table_timers' => $active_table_timers,
            ];

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST /api/v1/operations/status
     * Quick status transition with optimistic UI support and undo history.
     */
    public function update_status(): void
    {
        try {
            $appointment_id = (int)$this->input->post('appointment_id');
            $new_status = trim((string)$this->input->post('status'));

            if ($appointment_id <= 0 || empty($new_status)) {
                $payload = json_decode($this->input->raw_input_stream, true);
                if (is_array($payload)) {
                    $appointment_id = (int)($payload['appointment_id'] ?? 0);
                    $new_status = trim((string)($payload['status'] ?? ''));
                }
            }

            if ($appointment_id <= 0 || empty($new_status)) {
                json_response(['error' => 'Geçersiz parametreler (appointment_id ve status gereklidir)'], 400);
                return;
            }

            $appt = $this->appointments_model->find($appointment_id);
            if (!$appt) {
                json_response(['error' => 'Randevu bulunamadı'], 404);
                return;
            }

            $old_status = $appt['status'] ?? 'reserved';

            // Normalize status
            $canonical_status = match (strtolower($new_status)) {
                'arrived', 'geldi', 'checked-in', 'checked_in' => 'Arrived',
                'in_progress', 'in progress', 'başladı' => 'In_Progress',
                'completed', 'tamamlandı', 'closed' => 'Completed',
                'no_show', 'no-show', 'gelmedi' => 'No-Show',
                'cancelled', 'canceled', 'iptal' => 'Cancelled',
                'confirmed', 'onaylandı' => 'Confirmed',
                default => ucfirst($new_status),
            };

            // Update status in appointments table
            $this->db->where('id', $appointment_id)->update('appointments', [
                'status' => $canonical_status,
            ]);

            $updated = $this->appointments_model->find($appointment_id);

            // Trigger webhooks or notifications if needed
            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $updated);

            json_response([
                'success' => true,
                'appointment_id' => $appointment_id,
                'previous_status' => $old_status,
                'new_status' => $canonical_status,
                'updated_at' => date('Y-m-d H:i:s'),
                'message' => 'Durum başarıyla güncellendi: ' . $canonical_status,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST /api/v1/operations/checkin
     * Validate QR code/token and execute 1-click Check-in, starting the table/session timer.
     */
    public function checkin(): void
    {
        try {
            $token = trim((string)$this->input->post('qr_token'));
            if (empty($token)) {
                $payload = json_decode($this->input->raw_input_stream, true);
                if (is_array($payload)) {
                    $token = trim((string)($payload['qr_token'] ?? $payload['identifier'] ?? ''));
                }
            }

            if (empty($token)) {
                json_response(['error' => 'QR kod veya doğrulama belirteci gereklidir.'], 400);
                return;
            }

            // Resolve identifier via checkin model
            $resolved = $this->checkin_model->resolve_identifier($token);
            $appointment_id = $resolved['appointment_id'] ?? null;
            $customer_id = $resolved['customer_id'] ?? null;

            // Direct search if token is UUID or hash
            if (!$appointment_id) {
                $appt_row = $this->db->get_where('appointments', ['hash' => $token])->row_array();
                if ($appt_row) {
                    $appointment_id = (int)$appt_row['id'];
                    $customer_id = (int)$appt_row['id_users_customer'];
                }
            }

            if (!$appointment_id && is_numeric($token)) {
                $appt_row = $this->db->get_where('appointments', ['id' => (int)$token])->row_array();
                if ($appt_row) {
                    $appointment_id = (int)$appt_row['id'];
                    $customer_id = (int)$appt_row['id_users_customer'];
                }
            }

            if (!$appointment_id && $customer_id) {
                // Find today's nearest appointment for this customer
                $today = date('Y-m-d');
                $appt_row = $this->db
                    ->from('appointments')
                    ->where('id_users_customer', $customer_id)
                    ->where('DATE(start_datetime)', $today)
                    ->where_not_in('status', ['Cancelled'])
                    ->order_by('start_datetime', 'ASC')
                    ->get()
                    ->row_array();

                if ($appt_row) {
                    $appointment_id = (int)$appt_row['id'];
                }
            }

            if (!$appointment_id) {
                json_response([
                    'success' => false,
                    'error' => 'Geçersiz veya süresi dolmuş QR Kod!',
                    'message' => 'Sistemde bu QR koda ait aktif randevu kaydı bulunamadı.',
                ], 404);
                return;
            }

            $appt = $this->appointments_model->find($appointment_id);
            $current_status = strtolower($appt['status'] ?? '');

            // Duplicate check
            if (in_array($current_status, ['arrived', 'in_progress', 'completed'], true)) {
                $customer = $this->customers_model->find($appt['id_users_customer']);
                $service = $this->services_model->find($appt['id_services']);
                json_response([
                    'success' => true,
                    'already_checked_in' => true,
                    'message' => 'Bu randevu zaten giriş yapmış veya tamamlanmış durumda.',
                    'appointment_id' => $appointment_id,
                    'status' => $appt['status'],
                    'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                    'service_name' => $service['name'] ?? 'Hizmet',
                ]);
                return;
            }

            // Perform Check-in: set status to Arrived
            $this->db->where('id', $appointment_id)->update('appointments', [
                'status' => 'Arrived',
            ]);

            $customer = $this->customers_model->find($appt['id_users_customer']);
            $service = $this->services_model->find($appt['id_services']);
            $provider = $this->providers_model->find($appt['id_users_provider']);

            $now_dt = new DateTime();
            $start_dt = new DateTime($appt['start_datetime']);
            $end_dt = new DateTime($appt['end_datetime']);
            $duration_mins = max(15, (int)(($end_dt->getTimestamp() - $start_dt->getTimestamp()) / 60));

            json_response([
                'success' => true,
                'already_checked_in' => false,
                'appointment_id' => $appointment_id,
                'status' => 'Arrived',
                'customer_id' => (int)$appt['id_users_customer'],
                'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                'customer_phone' => $customer['phone_number'] ?? '',
                'service_name' => $service['name'] ?? 'Hizmet',
                'provider_name' => trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
                'start_datetime' => $appt['start_datetime'],
                'duration_minutes' => $duration_mins,
                'checkin_time' => $now_dt->format('Y-m-d H:i:s'),
                'message' => 'Giriş onaylandı! Seans/Masa süresi başlatıldı.',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * GET /api/v1/operations/floor-plan
     * Get interactive table/station floor map statuses with occupancy and active duration.
     */
    public function floor_plan(): void
    {
        try {
            $stations = $this->stations_model->get();
            $today = date('Y-m-d');
            $now = new DateTime();
            $now_ts = $now->getTimestamp();

            // Fetch today's appointments with stations
            $appointments = $this->db
                ->from('appointments')
                ->where('DATE(start_datetime)', $today)
                ->where_not_in('status', ['Cancelled'])
                ->get()
                ->result_array();

            $stations_result = [];

            foreach ($stations as $st) {
                $st_id = (int)$st['id'];
                $table_status = 'empty'; // empty (yeşil), reserved (mavi), occupied (kırmızı), overdue (turuncu)
                $active_appt = null;
                $active_guest = null;
                $elapsed_seconds = 0;
                $is_overdue = false;
                $remaining_to_reservation = null;

                // Match with station appointments
                foreach ($appointments as $appt) {
                    $appt_st_id = (int)($appt['id_stations'] ?? 0);
                    if ($appt_st_id !== $st_id) {
                        continue;
                    }

                    $status = strtolower($appt['status'] ?? '');
                    $start_dt = new DateTime($appt['start_datetime']);
                    $end_dt = new DateTime($appt['end_datetime']);
                    $start_ts = $start_dt->getTimestamp();
                    $end_ts = $end_dt->getTimestamp();
                    $planned_duration = max(15, (int)(($end_ts - $start_ts)));

                    if (in_array($status, ['arrived', 'in_progress', 'geldi', 'başladı'], true)) {
                        $table_status = 'occupied';
                        $elapsed_seconds = max(0, $now_ts - $start_ts);
                        if ($elapsed_seconds > $planned_duration) {
                            $table_status = 'overdue';
                            $is_overdue = true;
                        }

                        $cust = $this->customers_model->find($appt['id_users_customer']);
                        $active_guest = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''));
                        $active_appt = [
                            'id' => (int)$appt['id'],
                            'guest_name' => $active_guest,
                            'start_datetime' => $appt['start_datetime'],
                            'duration_minutes' => (int)($planned_duration / 60),
                        ];
                        break;
                    } elseif ($table_status === 'empty' && in_array($status, ['reserved', 'confirmed'], true)) {
                        if ($start_ts >= $now_ts && ($start_ts - $now_ts) <= 3600) {
                            $table_status = 'reserved';
                            $remaining_to_reservation = $start_ts - $now_ts;
                            $cust = $this->customers_model->find($appt['id_users_customer']);
                            $active_guest = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''));
                            $active_appt = [
                                'id' => (int)$appt['id'],
                                'guest_name' => $active_guest,
                                'start_datetime' => $appt['start_datetime'],
                            ];
                        }
                    }
                }

                $stations_result[] = [
                    'id' => $st_id,
                    'name' => $st['name'] ?? ('Masa ' . $st_id),
                    'capacity' => (int)($st['capacity'] ?? 4),
                    'is_active' => (bool)($st['is_active'] ?? true),
                    'status' => $table_status,
                    'active_guest' => $active_guest,
                    'elapsed_seconds' => $elapsed_seconds,
                    'is_overdue' => $is_overdue,
                    'remaining_to_reservation' => $remaining_to_reservation,
                    'active_appointment' => $active_appt,
                ];
            }

            json_response([
                'stations' => $stations_result,
                'count' => count($stations_result),
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST /api/v1/operations/validate-conflict
     * Validates if a proposed time slot collides with an existing reservation for provider/station.
     */
    public function validate_conflict(): void
    {
        try {
            $provider_id = (int)$this->input->post('provider_id');
            $station_id = (int)$this->input->post('station_id');
            $start_dt = $this->input->post('start_datetime');
            $end_dt = $this->input->post('end_datetime');
            $exclude_id = (int)$this->input->post('exclude_appointment_id');

            if (empty($start_dt) || empty($end_dt)) {
                $payload = json_decode($this->input->raw_input_stream, true);
                if (is_array($payload)) {
                    $provider_id = (int)($payload['provider_id'] ?? 0);
                    $station_id = (int)($payload['station_id'] ?? 0);
                    $start_dt = (string)($payload['start_datetime'] ?? '');
                    $end_dt = (string)($payload['end_datetime'] ?? '');
                    $exclude_id = (int)($payload['exclude_appointment_id'] ?? 0);
                }
            }

            if (empty($start_dt) || empty($end_dt)) {
                json_response(['error' => 'start_datetime ve end_datetime zorunludur.'], 400);
                return;
            }

            $query = $this->db->from('appointments')
                ->where('start_datetime <', $end_dt)
                ->where('end_datetime >', $start_dt)
                ->where_not_in('status', ['Cancelled']);

            if ($exclude_id > 0) {
                $query->where('id !=', $exclude_id);
            }

            if ($provider_id > 0 && $station_id > 0) {
                $query->group_start()
                    ->where('id_users_provider', $provider_id)
                    ->or_where('id_stations', $station_id)
                    ->group_end();
            } elseif ($provider_id > 0) {
                $query->where('id_users_provider', $provider_id);
            } elseif ($station_id > 0) {
                $query->where('id_stations', $station_id);
            }

            $conflict = $query->get()->row_array();

            if ($conflict) {
                $cust = $this->customers_model->find($conflict['id_users_customer']);
                json_response([
                    'has_conflict' => true,
                    'message' => 'Seçilen zaman diliminde çakışan bir randevu bulunmaktadır!',
                    'conflicting_appointment' => [
                        'id' => (int)$conflict['id'],
                        'start_datetime' => $conflict['start_datetime'],
                        'end_datetime' => $conflict['end_datetime'],
                        'guest_name' => trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? '')),
                    ],
                    'suggested_waitlist' => true,
                ]);
            } else {
                json_response([
                    'has_conflict' => false,
                    'message' => 'Seçilen zaman aralığı tamamen uygundur.',
                ]);
            }
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}

