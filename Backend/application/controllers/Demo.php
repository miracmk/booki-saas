<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Demo Role Controller.
 *
 * Provides endpoints for live role switching across vertical archetypes.
 */
class Demo extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('demo_service');
        $this->load->library('vertical_service');

        if (!is_demo_environment()) {
            show_error('Rol değiştirme ve demo araçları yalnızca BooKi-Demo ortamında kullanılabilir. Prod, Beta ve Dev ortamlarında rol bazlı değişiklik yapılamaz.', 403, 'Erişim Engellendi');
        }
    }

    /**
     * Switch current user session to the requested demo role.
     *
     * @param string $role_slug e.g. 'owner', 'waiter', 'kitchen', 'doctor', 'nurse', etc.
     */
    public function switch_role(string $role_slug = ''): void
    {
        try {
            if (empty($role_slug)) {
                $role_slug = (string) $this->input->post_get('role');
            }

            if (empty($role_slug)) {
                throw new InvalidArgumentException('Geçersiz rol seçimi.');
            }

            $result = $this->demo_service->switch_role($role_slug);

            if ($this->input->is_ajax_request()) {
                json_response($result);
                return;
            }

            redirect('dashboard');
        } catch (Throwable $e) {
            if ($this->input->is_ajax_request()) {
                json_exception($e);
                return;
            }
            redirect('dashboard');
        }
    }

    /**
     * Fetch list of available demo roles for the active vertical.
     */
    public function get_roles(): void
    {
        try {
            $roles = $this->demo_service->get_available_demo_roles();
            json_response([
                'success' => true,
                'business_type' => $this->vertical_service->current_business_type(),
                'roles' => $roles,
                'active_role' => session('role_slug'),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
