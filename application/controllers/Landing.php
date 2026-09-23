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
 * Landing controller - the public marketing site (booki.kibusiness.co).
 *
 * Multi-tenant SaaS only. Serves the server-rendered BooKi marketing page on the marketplace
 * host (the bare `MARKETPLACE_DOMAIN`, e.g. booki.kibusiness.co) so the sales/landing page,
 * the tenant discovery portal and the superadmin panel are all reachable on their own distinct
 * hosts. Stays on the master DB for the whole request - never tenant-resolves (see
 * EA_Controller::resolve_tenant()'s marketplace host exception).
 *
 * The landing page is intentionally plain server-rendered PHP + CSS (no SPA): it must be
 * fast, indexable and dependency-free. Lead capture is wired in Dalga 0.5 (ea_leads + email +
 * Zoho CRM); until then the CTA simply points at the existing portal/marketplace.
 */
class Landing extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_multi_tenant_mode()) {
            abort(404, 'Not Found');
        }
    }

    /**
     * Render the marketing landing page.
     */
    public function index(): void
    {
        method('get');

        // Pull the list of marketplace-opted-in tenants + review averages so the landing
        // "featured salons" strip reflects real, live data (same queries Marketplace.php uses,
        // trimmed to 6 for the homepage). Note: master `tenants` has no `company_name` column —
        // display name falls back to `subdomain`.
        $this->db->select(
            'tenants.*,' .
            '(SELECT COUNT(*) FROM ' . $this->db->dbprefix('reviews') .
            ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
            ' AND status = "published") AS review_count,' .
            '(SELECT AVG(rating) FROM ' . $this->db->dbprefix('reviews') .
            ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
            ' AND status = "published") AS avg_rating',
        );

        $this->db->where('marketplace_opt_in', 1);
        $this->db->where('status', 'active');

        $featured = $this->db
            ->order_by('created_at', 'desc')
            ->limit(6)
            ->get('tenants')
            ->result_array();

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        html_vars([
            'page_title' => 'BooKi — Online Randevu ve Salon Yönetim Sistemi',
            'featured_tenants' => $featured,
            'app_domain' => $app_domain,
            'portal_url' => 'https://' . $app_domain . '/portal',
        ]);

        $this->load->view('pages/landing_home');
    }

    /**
     * Render the Google OAuth and KVKK/GDPR compliant Privacy Policy.
     */
    public function privacy(): void
    {
        method('get');

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        html_vars([
            'page_title' => 'BooKi — Privacy Policy / Gizlilik Politikası',
            'app_domain' => $app_domain,
            'portal_url' => 'https://' . $app_domain . '/portal',
        ]);

        $this->load->view('pages/privacy_policy');
    }

    /**
     * Render the Terms of Service.
     */
    public function terms(): void
    {
        method('get');

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        html_vars([
            'page_title' => 'BooKi — Terms of Service / Kullanım Şartları',
            'app_domain' => $app_domain,
            'portal_url' => 'https://' . $app_domain . '/portal',
        ]);

        $this->load->view('pages/terms_of_service');
    }

    /**
     * Render the public "About Us / Hakkımızda" page.
     */
    public function about(): void
    {
        method('get');

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        html_vars([
            'page_title' => 'BooKi — About Us / Hakkımızda',
            'app_domain' => $app_domain,
            'portal_url' => 'https://' . $app_domain . '/portal',
        ]);

        $this->load->view('pages/about_us');
    }

    /**
     * Render the Distance Sales Contract / Mesafeli Satış Sözleşmesi page
     * (required for e-commerce / iyzico merchant approval).
     */
    public function mesafeli_satis(): void
    {
        method('get');

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        html_vars([
            'page_title' => 'BooKi — Mesafeli Satış Sözleşmesi / Distance Sales Contract',
            'app_domain' => $app_domain,
            'portal_url' => 'https://' . $app_domain . '/portal',
        ]);

        $this->load->view('pages/mesafeli_satis');
    }

    /**
     * Render the Delivery & Returns / Teslimat ve İade page
     * (required for e-commerce / iyzico merchant approval).
     */
    public function teslimat_iade(): void
    {
        method('get');

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        html_vars([
            'page_title' => 'BooKi — Teslimat ve İade Politikası / Delivery & Returns Policy',
            'app_domain' => $app_domain,
            'portal_url' => 'https://' . $app_domain . '/portal',
        ]);

        $this->load->view('pages/teslimat_iade');
    }
}