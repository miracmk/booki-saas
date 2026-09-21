<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Customer Self-Service Onboarding Controller
 *
 * Provides a secure, tokenized 10-step onboarding wizard for newly converted
 * tenants. Stays on the master DB to validate tokens, and writes configuration
 * directly into the provisioned tenant database.
 * -------------------------------------------------------------------------- */

class Customer_onboarding extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('onboarding_sessions_model');
        $this->load->model('master_audit_model');
        $this->load->library('spreadsheet_importer');
    }

    /**
     * Display the 10-step customer onboarding wizard.
     */
    public function index(string $token = ''): void
    {
        method('get');

        $token = trim($token ?: (string) $this->input->get('token'));

        if ($token === '') {
            $this->render_error_page('Geçersiz Onboarding Bağlantısı', 'Onboarding bağlantı belirteci (token) eksik.');
            return;
        }

        $session = $this->onboarding_sessions_model->get_session_by_token($token);

        if (!$session) {
            $this->render_error_page('Oturum Bulunamadı', 'Bu onboarding bağlantısı geçersizdir veya sistemde bulunamadı.');
            return;
        }

        if ($session['is_expired']) {
            $this->render_error_page('Bağlantı Süresi Doldu', 'Bu onboarding davet bağlantısının geçerlilik süresi dolmuştur. Lütfen yeni bir bağlantı için destek ekibiyle iletişime geçin.');
            return;
        }

        if ($session['status'] === 'completed') {
            $tenant = $session['tenant'];
            $portal_url = "https://{$tenant['subdomain']}.bookiapp.kibusiness.co/backend";
            $this->load->view('pages/customer_onboarding_success', [
                'tenant' => $tenant,
                'portal_url' => $portal_url,
            ]);
            return;
        }

        // Available sector templates
        $sector_defaults = [
            'beauty' => [
                'label' => 'Güzellik & Tırnak Salonu',
                'resource_type' => 'Koltuk / Oda / İstasyon',
                'services' => [
                    ['name' => 'Klasik Cilt Bakımı', 'duration' => 60, 'price' => 750],
                    ['name' => 'Protez Tırnak & Nail Art', 'duration' => 90, 'price' => 650],
                    ['name' => 'Kalıcı Makyaj & Microblading', 'duration' => 120, 'price' => 1800],
                    ['name' => 'Lazer Epilasyon Tüm Vücut', 'duration' => 45, 'price' => 1200],
                ],
                'resources' => [
                    ['name' => 'Kabin 1 (Cilt Bakımı)', 'type' => 'Cabin', 'capacity' => 1],
                    ['name' => 'Tırnak Masası 1', 'type' => 'Station', 'capacity' => 1],
                    ['name' => 'Tırnak Masası 2', 'type' => 'Station', 'capacity' => 1],
                ],
            ],
            'massage' => [
                'label' => 'Masaj & SPA Merkezi',
                'resource_type' => 'Oda / Kabin',
                'services' => [
                    ['name' => 'Aromaterapi Masajı', 'duration' => 50, 'price' => 850],
                    ['name' => 'Medikal Sırt & Boyun Masajı', 'duration' => 45, 'price' => 750],
                    ['name' => 'VIP Çift Masajı & Jakuzi', 'duration' => 90, 'price' => 2200],
                ],
                'resources' => [
                    ['name' => 'VIP Masaj Odası 1', 'type' => 'Room', 'capacity' => 2],
                    ['name' => 'Tekli Terapi Odası 2', 'type' => 'Room', 'capacity' => 1],
                ],
            ],
            'fitness' => [
                'label' => 'Personal Training & Pilates',
                'resource_type' => 'Stüdyo / İstasyon / Reformer',
                'services' => [
                    ['name' => 'Birebir Aletli Pilates (Reformer)', 'duration' => 50, 'price' => 800],
                    ['name' => 'Personal Training Bireysel Seans', 'duration' => 60, 'price' => 700],
                    ['name' => 'Düet Pilates Dersi', 'duration' => 50, 'price' => 1100],
                ],
                'resources' => [
                    ['name' => 'Reformer İstasyonu 1', 'type' => 'Station', 'capacity' => 1],
                    ['name' => 'Reformer İstasyonu 2', 'type' => 'Station', 'capacity' => 1],
                    ['name' => 'Serbest Ağırlık Alanı', 'type' => 'Studio', 'capacity' => 4],
                ],
            ],
            'clinic' => [
                'label' => 'Klinik & Muayenehane',
                'resource_type' => 'Muayene Odası',
                'services' => [
                    ['name' => 'Uzman Hekim İlk Muayene', 'duration' => 30, 'price' => 1500],
                    ['name' => 'Kontrol Muayenesi', 'duration' => 20, 'price' => 0],
                    ['name' => 'Dermatolojik Cilt Analizi', 'duration' => 45, 'price' => 950],
                ],
                'resources' => [
                    ['name' => 'Muayene Odası 1', 'type' => 'Room', 'capacity' => 1],
                    ['name' => 'Girişim / Tedavi Odası', 'type' => 'Room', 'capacity' => 1],
                ],
            ],
            'restaurant' => [
                'label' => 'Restoran & Rezervasyon',
                'resource_type' => 'Masa / Bölüm',
                'services' => [
                    ['name' => 'Akşam Yemeği Rezervasyonu', 'duration' => 120, 'price' => 0],
                    ['name' => 'Özel Kutlama / Doğum Günü Masası', 'duration' => 180, 'price' => 0],
                ],
                'resources' => [
                    ['name' => 'Masa 1 (Bahçe)', 'type' => 'Table', 'capacity' => 4],
                    ['name' => 'Masa 2 (İç Salon)', 'type' => 'Table', 'capacity' => 6],
                    ['name' => 'VIP Oda Masası', 'type' => 'Table', 'capacity' => 10],
                ],
            ],
            'hotel' => [
                'label' => 'Butik Otel & Konaklama',
                'resource_type' => 'Oda',
                'services' => [
                    ['name' => 'Standart Oda Konaklama', 'duration' => 1440, 'price' => 2500],
                    ['name' => 'Deluxe Suite Konaklama', 'duration' => 1440, 'price' => 4200],
                ],
                'resources' => [
                    ['name' => 'Oda 101 (Deluxe)', 'type' => 'Room', 'capacity' => 2],
                    ['name' => 'Oda 102 (Standart)', 'type' => 'Room', 'capacity' => 2],
                ],
            ],
        ];

        $view_data = [
            'token' => $token,
            'session' => $session,
            'tenant' => $session['tenant'],
            'lead' => $session['lead'],
            'current_data' => $session['data'],
            'current_step' => $session['current_step'],
            'total_steps' => $session['total_steps'],
            'progress_percent' => $session['progress_percent'],
            'sector_defaults' => $sector_defaults,
        ];

        $this->load->view('pages/customer_onboarding', $view_data);
    }

    /**
     * AJAX endpoint to save an individual step.
     */
    public function save_step(): void
    {
        try {
            method('post');

            $token = trim((string) $this->input->post('token'));
            $step = (int) $this->input->post('step');
            $data_json = (string) $this->input->post('data');

            if ($token === '' || $step < 1 || $step > 10) {
                throw new InvalidArgumentException('Geçersiz parametreler.');
            }

            $step_data = json_decode($data_json, true);
            if (!is_array($step_data)) {
                $step_data = [];
            }

            $result = $this->onboarding_sessions_model->save_step($token, $step, $step_data);
            json_response($result);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * AJAX endpoint to complete onboarding and activate tenant.
     */
    public function complete(): void
    {
        try {
            method('post');

            $token = trim((string) $this->input->post('token'));
            if ($token === '') {
                throw new InvalidArgumentException('Token eksik.');
            }

            $result = $this->onboarding_sessions_model->complete_onboarding($token);
            json_response($result);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Upload and parse customer/service/resource CSV file during onboarding.
     */
    public function parse_import_file(): void
    {
        try {
            method('post');

            if (empty($_FILES['file']['tmp_name'])) {
                throw new InvalidArgumentException('Lütfen bir dosya seçin.');
            }

            $file_path = $_FILES['file']['tmp_name'];
            $file_name = $_FILES['file']['name'];

            $parsed = $this->spreadsheet_importer->parse_file($file_path, $file_name);
            json_response($parsed);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Download template for onboarding data.
     */
    public function download_template(string $type = 'customers'): void
    {
        method('get');
        $csv = $this->spreadsheet_importer->get_template_csv($type);

        $filename = "BooKi_Sablon_{$type}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $csv;
        exit();
    }

    private function render_error_page(string $title, string $message): void
    {
        $this->load->view('pages/customer_onboarding_error', [
            'title' => $title,
            'message' => $message,
        ]);
    }
}

