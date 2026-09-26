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
 * BooKi customization (2026-08-24) - read-only viewer for the audit_log table (see
 * salonflora_audit_helper.php). Admin-only (gated on PRIV_SYSTEM_SETTINGS, same as General Settings),
 * because this can reveal e.g. which staff member erased which customer and when.
 */
class Audit_log extends App_Controller
{
    private const PAGE_SIZE = 100;

    /**
     * Audit_log constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');
        $this->load->library('accounts');
    }

    /**
     * Render the audit log page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('audit_log')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $action_filter = $this->input->get('action');
        $from_date = $this->input->get('from');
        $to_date = $this->input->get('to');
        $page = max(1, (int) $this->input->get('page'));

        $this->db->from('audit_log');

        if (!empty($action_filter)) {
            $this->db->like('action', $action_filter);
        }

        if (!empty($from_date)) {
            $this->db->where('created_at >=', $from_date . ' 00:00:00');
        }

        if (!empty($to_date)) {
            $this->db->where('created_at <=', $to_date . ' 23:59:59');
        }

        $total = $this->db->count_all_results('', false);

        $this->db
            ->order_by('created_at', 'DESC')
            ->limit(self::PAGE_SIZE, (($page - 1) * self::PAGE_SIZE));

        $entries = $this->db->get()->result_array();

        foreach ($entries as &$entry) {
            $entry['details'] = $entry['details'] !== null ? json_decode($entry['details'], true) : null;
        }

        html_vars([
            'page_title' => 'Denetim Kayıtları',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'entries' => $entries,
            'total' => $total,
            'page' => $page,
            'page_size' => self::PAGE_SIZE,
            'action_filter' => (string) $action_filter,
            'from_date' => (string) $from_date,
            'to_date' => (string) $to_date,
        ]);

        $this->load->view('pages/audit_log');
    }
}
