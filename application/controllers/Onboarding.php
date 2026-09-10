<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Onboarding controller - multi-tenant SaaS only.
 *
 * A freshly created tenant's `settings` table has no company profile data yet (tenant_create() only
 * seeds the generic EasyAppointments defaults). EA_Controller::enforce_onboarding() redirects the
 * tenant's admin here on their first login (whenever the 'onboarding_completed' setting is missing/
 * not '1') to collect it before they reach the calendar. Standalone deployments (e.g. Salon Flora's
 * own production) never hit this - tenant_context() is null there.
 */
class Onboarding extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        // NOTE: NOT is_multi_tenant_mode() - by this point resolve_tenant() has already swapped
        // $this->db to the tenant's own database (no `tenants` table there), so that check would
        // always read false. tenant_context() being set is the correct "in a tenant request" signal.
        if (!tenant_context()) {
            abort(404, 'Not Found');
        }

        if (!session('user_id') || session('role_slug') !== 'admin') {
            // Constructor-level redirect - the router still invokes index()/save() afterwards unless
            // execution is explicitly stopped here (redirect() only sends the header, unlike abort()).
            redirect('login');
            exit();
        }
    }

    /**
     * Render the onboarding form.
     */
    public function index(): void
    {
        method('get');

        if (setting('onboarding_completed') === '1') {
            redirect('dashboard');
            return;
        }

        html_vars([
            'page_title' => lang('onboarding_title'),
            'company_name' => setting('company_name'),
            'company_address' => setting('company_address'),
            'company_phone' => setting('company_phone'),
            'business_type' => setting('business_type'),
            'social_instagram' => setting('social_instagram'),
            'social_telegram' => setting('social_telegram'),
            'social_facebook' => setting('social_facebook'),
            'social_website' => setting('social_website'),
        ]);

        script_vars([
            'company_working_plan' => setting('company_working_plan'),
        ]);

        $this->load->view('pages/onboarding');
    }

    /**
     * Save the submitted onboarding data and mark the tenant as onboarded.
     */
    public function save(): void
    {
        try {
            method('post');

            check('company_name', 'string');
            check('business_type', 'string|null');
            check('company_address', 'string|null');
            check('company_phone', 'string|null');
            check('social_instagram', 'string|null');
            check('social_telegram', 'string|null');
            check('social_facebook', 'string|null');
            check('social_website', 'string|null');
            check('working_start', 'string|null');
            check('working_end', 'string|null');
            check('closed_days', 'array|null');

            $company_name = trim((string) request('company_name'));

            if ($company_name === '') {
                throw new InvalidArgumentException(lang('field_required'));
            }

            setting([
                'company_name' => $company_name,
                'business_type' => trim((string) request('business_type')),
                'company_address' => trim((string) request('company_address')),
                'company_phone' => trim((string) request('company_phone')),
                'social_instagram' => trim((string) request('social_instagram')),
                'social_telegram' => trim((string) request('social_telegram')),
                'social_facebook' => trim((string) request('social_facebook')),
                'social_website' => trim((string) request('social_website')),
                'company_working_plan' => $this->build_working_plan(
                    (string) request('working_start', '09:00'),
                    (string) request('working_end', '18:00'),
                    (array) request('closed_days', []),
                ),
                'onboarding_completed' => '1',
            ]);

            audit_log('onboarding.completed', 'settings', tenant_context()['id'] ?? null);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Build a company_working_plan JSON value (same shape the base schema seeds) from the
     * onboarding wizard's simplified "one start/end time + which days are closed" inputs.
     */
    private function build_working_plan(string $start, string $end, array $closed_days): string
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $plan = [];

        foreach ($days as $day) {
            $plan[$day] = in_array($day, $closed_days, true)
                ? null
                : ['start' => $start, 'end' => $end, 'breaks' => []];
        }

        return json_encode($plan);
    }
}
