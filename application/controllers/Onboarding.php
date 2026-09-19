<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Onboarding & Industry Setup Controller.
 *
 * Provides a smart interactive onboarding wizard for selecting an industry vertical,
 * toggling sector-specific modules, previewing service structures, and seeding full demo datasets.
 */
class Onboarding extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('accounts');
        $this->load->library('blueprint_service');
        $this->load->model('settings_model');
        $this->load->helper('general');
    }

    /**
     * Display the modern onboarding wizard page.
     */
    public function index(): void
    {
        method('get');

        $user_id = session('user_id');

        if (!$user_id) {
            redirect('login');
            return;
        }

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
            return;
        }

        $blueprints = $this->blueprint_service->get_all_blueprints();
        $current_industry = setting('industry_code') ?: 'general';
        $current_terminology = setting('industry_custom_terminology');
        if ($current_terminology && is_string($current_terminology)) {
            $current_terminology = json_decode($current_terminology, true);
        }

        $view_data = [
            'blueprints' => $blueprints,
            'current_industry' => $current_industry,
            'current_terminology' => $current_terminology,
            'company_name' => setting('company_name') ?: 'BooKi İşletmem',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ];

        html_vars([
            'page_title' => 'Sektörel Kurulum & Modüler Blueprint Sihirbazı',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/onboarding', $view_data);
    }

    /**
     * Return list of blueprints in JSON format.
     */
    public function get_blueprints(): void
    {
        try {
            method('get');
            $blueprints = $this->blueprint_service->get_all_blueprints();
            json_response($blueprints);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Return single blueprint details with full preview data.
     */
    public function get_blueprint_details(string $code = ''): void
    {
        try {
            method('get');
            if (empty($code)) {
                $code = (string) $this->input->get('code');
            }

            $blueprint = $this->blueprint_service->get_blueprint($code);
            if (!$blueprint) {
                throw new InvalidArgumentException("Sektör blueprint'i bulunamadı: {$code}");
            }

            json_response($blueprint);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Apply the selected blueprint and optionally seed demo data.
     */
    public function apply(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Bu işlem için yönetici yetkisi gerekmektedir.');
            }

            $industry_code = (string) $this->input->post('industry_code');
            $seed_demo = $this->input->post('seed_demo') === '1' || $this->input->post('seed_demo') === 'true' || $this->input->post('seed_demo') === true;
            $company_name = trim((string) $this->input->post('company_name'));

            if (empty($industry_code)) {
                throw new InvalidArgumentException('Lütfen bir sektör seçiniz.');
            }

            $options = [];
            if (!empty($company_name)) {
                $options['company_name'] = $company_name;
            }

            $result = $this->blueprint_service->apply_blueprint($industry_code, $seed_demo, $options);

            json_response([
                'success' => true,
                'message' => "{$result['industry']} şablonu ve modülleri başarıyla uygulandı!",
                'result' => $result,
                'redirect_url' => site_url('calendar'),
            ]);
        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
