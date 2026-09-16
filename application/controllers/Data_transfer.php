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
 * "Veriler" ayar sekmesi (2026-08-26) - içe/dışa aktarma sihirbazı. Dışa aktarma, bu tenant'ın
 * hizmet/istasyon/sağlayıcı/müşteri/randevu verisini kendi JSON formatında indirir (modellerin get()
 * metotları zaten PII'yi çözülmüş döndürüyor). İçe aktarma AYNI formatı bekler - başka bir Ki
 * Reservation kiracısından (ör. gelecekte Salon Flora'nın verisi) veya bu tenant'ın kendi önceki
 * yedeğinden geri yüklemek için. Müşteri tekilleştirme mantığı (telefon-hash → tam isim eşleşmesi +
 * unvan ayıklama) Console::migrate_salonflora_live_data()'da kurulup doğrulanan algoritmanın aynısı.
 */
class Data_transfer extends EA_Controller
{
    private array $honorifics = ['bey', 'hanım', 'hanim', 'hoca', 'usta', 'abi', 'abla', 'beyefendi', 'hanımefendi', 'hanimefendi'];

    public function __construct()
    {
        parent::__construct();

        $this->load->model('services_model');
        $this->load->model('service_categories_model');
        $this->load->model('stations_model');
        $this->load->model('providers_model');
        $this->load->model('customers_model');
        $this->load->model('appointments_model');
        $this->load->library('accounts');
    }

    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        html_vars([
            'page_title' => 'Veriler',
            'csrf_token' => $this->security->get_csrf_hash(),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name(session('user_id')),
        ]);

        $this->load->view('pages/data_transfer');
    }

    /**
     * Dışa aktarma - tarayıcıya doğrudan bir JSON dosyası indirtir.
     */
    public function export(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $categories = $this->service_categories_model->get();
        $services = $this->services_model->get();
        $stations = $this->stations_model->get();
        $providers = $this->providers_model->get();
        $customers = $this->customers_model->get();
        $appointments = $this->appointments_model->get();

        // Şifre hash'i/salt'ı asla dışa aktarılmıyor - başka bir kiracıya taşındığında zaten
        // anlamsız (farklı context), sadece gereksiz hassas veri taşımış oluruz.
        foreach ($providers as &$provider) {
            unset($provider['settings']['password'], $provider['settings']['salt']);
        }

        unset($provider);

        $payload = [
            'exported_at' => date('c'),
            'format' => 'ki_reservation_export_v1',
            'categories' => $categories,
            'services' => $services,
            'stations' => $stations,
            'providers' => $providers,
            'customers' => $customers,
            'appointments' => $appointments,
        ];

        $filename = 'ki-reservation-export-' . date('Y-m-d') . '.json';

        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * İçe aktarma önizlemesi - hiçbir şey yazmadan, dosyayı okuyup dedup sonrası beklenen sayıları
     * döndürür (kullanıcı "içe aktar"a basmadan önce görsün diye).
     */
    public function import_preview(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            $payload = $this->read_uploaded_payload();
            $customer_groups = $this->dedupe_customers($payload['customers'] ?? []);

            json_response([
                'success' => true,
                'categories' => count($payload['categories'] ?? []),
                'services' => count($payload['services'] ?? []),
                'stations' => count($payload['stations'] ?? []),
                'providers' => count($payload['providers'] ?? []),
                'raw_customers' => count($payload['customers'] ?? []),
                'unique_customers' => count($customer_groups),
                'appointments' => count($payload['appointments'] ?? []),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Gerçek içe aktarma - önizlemedeki AYNI dosyayı tekrar okuyup bu sefer commit eder.
     */
    public function import_commit(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            $payload = $this->read_uploaded_payload();

            // BooKi (2026-08-26) - imported (historical) customers don't all have every
            // field a fresh tenant's public-booking-form requirements (require_last_name/email/
            // phone_number, '1' by default) demand - same relaxation Console::migrate_salonflora_live_data()
            // applies, restored right after.
            $original_require_email = setting('require_email');
            $original_require_phone_number = setting('require_phone_number');
            $original_require_last_name = setting('require_last_name');

            setting(['require_email' => '0', 'require_phone_number' => '0', 'require_last_name' => '0']);

            $category_id_map = [];

            foreach ($payload['categories'] ?? [] as $category) {
                $category_id_map[$category['id']] = $this->service_categories_model->save([
                    'name' => $category['name'],
                    'description' => $category['description'] ?? null,
                ]);
            }

            $service_id_map = [];

            foreach ($payload['services'] ?? [] as $service) {
                $service_id_map[$service['id']] = $this->services_model->save([
                    'name' => $service['name'],
                    'duration' => $service['duration'],
                    'price' => $service['price'],
                    'currency' => $service['currency'],
                    'description' => $service['description'] ?? null,
                    'slot_interval' => $service['slot_interval'] ?? 15,
                    'color' => $service['color'] ?? null,
                    'location' => $service['location'] ?? null,
                    'attendants_number' => $service['attendants_number'] ?? 1,
                    'is_private' => $service['is_private'] ?? false,
                    'id_service_categories' => isset($service['service_category_id'])
                        ? ($category_id_map[$service['service_category_id']] ?? null)
                        : null,
                ]);
            }

            $station_id_map = [];

            foreach ($payload['stations'] ?? [] as $station) {
                $station_id_map[$station['id']] = $this->stations_model->save([
                    'name' => $station['name'],
                    'notes' => $station['notes'] ?? null,
                    'is_active' => $station['is_active'] ?? true,
                    'services' => [],
                ]);
            }

            $provider_id_map = [];

            foreach ($payload['providers'] ?? [] as $provider) {
                try {
                    $new_service_ids = array_values(array_filter(array_map(
                        fn($id) => $service_id_map[$id] ?? null,
                        $provider['services'] ?? [],
                    )));

                    $new_station_ids = array_values(array_filter(array_map(
                        fn($id) => $station_id_map[$id] ?? null,
                        $provider['stations'] ?? [],
                    )));

                    $provider_id_map[$provider['id']] = $this->providers_model->save([
                        'first_name' => $provider['first_name'],
                        'last_name' => $provider['last_name'] ?? '',
                        'email' => $provider['email'] ?? null,
                        'phone_number' => $provider['phone_number'] ?? null,
                        'address' => $provider['address'] ?? null,
                        'city' => $provider['city'] ?? null,
                        'timezone' => $provider['timezone'] ?? 'UTC',
                        'language' => $provider['language'] ?? 'turkish',
                        'services' => $new_service_ids,
                        'stations' => $new_station_ids,
                        'settings' => [
                            'username' => $provider['settings']['username'] ?? ('provider' . random_int(1000, 9999)),
                            // İçe aktarılan sağlayıcılar için de geçici şifre - şifre/hash asla dışa
                            // aktarılmıyor (bkz. export()), o yüzden burada yeniden üretmek zorundayız.
                            'password' => 'DegistirBu2026!',
                            'working_plan' => $provider['settings']['working_plan'] ?? null,
                            'calendar_view' => $provider['settings']['calendar_view'] ?? 'default',
                            'notifications' => (bool) ($provider['settings']['notifications'] ?? true),
                        ],
                    ]);
                } catch (Throwable $e) {
                    log_message('error', 'Data_transfer::import_commit() provider atlandı: ' . $e->getMessage());
                }
            }

            $customer_groups = $this->dedupe_customers($payload['customers'] ?? []);
            $customer_id_map = [];
            $imported_customers = 0;

            foreach ($customer_groups as $group) {
                $base = end($group);
                reset($group);

                $pick = function (string $field) use ($group, $base) {
                    if (!empty($base[$field])) {
                        return $base[$field];
                    }

                    foreach ($group as $row) {
                        if (!empty($row[$field])) {
                            return $row[$field];
                        }
                    }

                    return null;
                };

                $norm = $this->normalize_name((string) $base['first_name'], (string) $pick('last_name'));

                try {
                    $new_id = $this->customers_model->save([
                        'first_name' => $norm['first_name'] !== '' ? $norm['first_name'] : $base['first_name'],
                        'last_name' => $norm['last_name'],
                        'email' => $pick('email'),
                        'phone_number' => $pick('phone_number'),
                        'address' => $pick('address'),
                        'city' => $pick('city'),
                        'timezone' => $pick('timezone') ?: 'UTC',
                        'language' => $pick('language') ?: 'turkish',
                    ]);

                    $imported_customers++;
                } catch (Throwable $e) {
                    log_message('error', 'Data_transfer::import_commit() müşteri atlandı (id ' . $base['id'] . '): ' . $e->getMessage());
                    continue;
                }

                foreach ($group as $row) {
                    $customer_id_map[$row['id']] = $new_id;
                }
            }

            $imported_appointments = 0;
            $skipped_appointments = 0;

            foreach ($payload['appointments'] ?? [] as $appointment) {
                $new_provider_id = $provider_id_map[$appointment['id_users_provider']] ?? null;
                $new_customer_id = $customer_id_map[$appointment['id_users_customer']] ?? null;
                $new_service_id = $service_id_map[$appointment['id_services']] ?? null;

                if (!$new_provider_id || !$new_customer_id || !$new_service_id) {
                    $skipped_appointments++;
                    continue;
                }

                try {
                    $this->appointments_model->save([
                        'start_datetime' => $appointment['start_datetime'],
                        'end_datetime' => $appointment['end_datetime'],
                        'is_unavailability' => false,
                        'id_users_provider' => $new_provider_id,
                        'id_users_customer' => $new_customer_id,
                        'id_services' => $new_service_id,
                        'notes' => $appointment['notes'] ?? null,
                        'status' => $appointment['status'] ?? null,
                        'payment_status' => $appointment['payment_status'] ?? 'pending',
                    ]);

                    $imported_appointments++;
                } catch (Throwable $e) {
                    $skipped_appointments++;
                }
            }

            setting([
                'require_email' => $original_require_email,
                'require_phone_number' => $original_require_phone_number,
                'require_last_name' => $original_require_last_name,
            ]);

            audit_log('data_transfer.import', 'settings', null);

            json_response([
                'success' => true,
                'categories' => count($category_id_map),
                'services' => count($service_id_map),
                'stations' => count($station_id_map),
                'providers' => count($provider_id_map),
                'customers' => $imported_customers,
                'appointments' => $imported_appointments,
                'skipped_appointments' => $skipped_appointments,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function read_uploaded_payload(): array
    {
        if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            throw new InvalidArgumentException('Bir dosya yüklenmedi.');
        }

        $content = file_get_contents($_FILES['file']['tmp_name']);
        $payload = json_decode($content, true);

        if (!is_array($payload) || ($payload['format'] ?? null) !== 'ki_reservation_export_v1') {
            throw new InvalidArgumentException('Geçersiz dosya formatı - bu BooKi dışa aktarma dosyası değil.');
        }

        return $payload;
    }

    /**
     * Console::migrate_salonflora_live_data()'daki aynı tekilleştirme algoritması: önce
     * phone_number_hash (aynı telefon = aynı kişi), telefonu olmayanlar için tam normalize isim
     * eşleşmesi.
     */
    private function dedupe_customers(array $customers): array
    {
        $phone_groups = [];
        $no_phone = [];

        foreach ($customers as $row) {
            $phone_key = !empty($row['phone_number']) ? preg_replace('/\D/', '', $row['phone_number']) : null;

            if ($phone_key) {
                $phone_groups[$phone_key][] = $row;
            } else {
                $no_phone[] = $row;
            }
        }

        $name_groups = [];

        foreach ($no_phone as $row) {
            $norm = $this->normalize_name((string) ($row['first_name'] ?? ''), (string) ($row['last_name'] ?? ''));
            $key = mb_strtolower(trim($norm['first_name'] . '|' . $norm['last_name']), 'UTF-8');
            $name_groups[$key][] = $row;
        }

        return array_merge(array_values($phone_groups), array_values($name_groups));
    }

    private function normalize_name(string $first, string $last): array
    {
        $strip = function (string $value) {
            $tokens = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

            return array_values(
                array_filter($tokens, fn($t) => !in_array(mb_strtolower($t, 'UTF-8'), $this->honorifics, true)),
            );
        };

        return ['first_name' => implode(' ', $strip($first)), 'last_name' => implode(' ', $strip($last))];
    }
}
