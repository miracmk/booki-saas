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
 * Marketplace controller - tenant discovery portal (reservation.kibusiness.co).
 *
 * Multi-tenant SaaS only. Reads from the master DB's `tenants` and `reviews` tables to display
 * a searchable/filterable discovery site. Stays on the master DB for the entire request - never
 * tenant-resolves (see EA_Controller::resolve_tenant()'s marketplace host exception).
 */
class Marketplace extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_multi_tenant_mode()) {
            abort(404, 'Not Found');
        }
    }

    /**
     * List all marketplace-opted-in tenants with optional filtering.
     *
     * Query parameters:
     * - category: filter by category
     * - city: filter by city
     */
    public function index(): void
    {
        method('get');

        check('category', 'string|null');
        check('city', 'string|null');
        check('page', 'numeric|null');

        $category = trim((string) request('category'));
        $city = trim((string) request('city'));
        $page = max(1, (int) request('page', 1));
        $per_page = 12;

        // Fetch opted-in tenants with their review stats
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

        if ($category !== '') {
            $this->db->where('category', $category);
        }

        if ($city !== '') {
            $this->db->where('city', $city);
        }

        $total = $this->db->count_all_results('tenants');

        // Reset state after count
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

        if ($category !== '') {
            $this->db->where('category', $category);
        }

        if ($city !== '') {
            $this->db->where('city', $city);
        }

        $tenants = $this->db
            ->order_by('created_at', 'desc')
            ->limit($per_page, ($page - 1) * $per_page)
            ->get('tenants')
            ->result_array();

        // Fetch distinct categories and cities for filters
        $categories = $this->db
            ->distinct()
            ->select('category')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('category IS NOT NULL', null, false)
            ->order_by('category', 'asc')
            ->get('tenants')
            ->result_array();

        $cities = $this->db
            ->distinct()
            ->select('city')
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->where('city IS NOT NULL', null, false)
            ->order_by('city', 'asc')
            ->get('tenants')
            ->result_array();

        html_vars([
            'page_title' => 'Ki Reservation Marketplace',
            'tenants' => $tenants,
            'categories' => array_column($categories, 'category'),
            'cities' => array_column($cities, 'city'),
            'selected_category' => $category,
            'selected_city' => $city,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($total / $per_page)),
            'total' => $total,
            'per_page' => $per_page,
        ]);

        $this->load->view('pages/marketplace_index');
    }

    /**
     * Display a single business's marketplace profile with reviews.
     *
     * @param string $subdomain
     */
    public function business(string $subdomain = ''): void
    {
        method('get');

        $subdomain = strtolower(trim($subdomain));

        if ($subdomain === '') {
            abort(404, 'Not Found');
        }

        $tenant = $this->db
            ->select(
                'tenants.*,' .
                '(SELECT COUNT(*) FROM ' . $this->db->dbprefix('reviews') .
                ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
                ' AND status = "published") AS review_count,' .
                '(SELECT AVG(rating) FROM ' . $this->db->dbprefix('reviews') .
                ' WHERE ' . $this->db->dbprefix('reviews') . '.id_tenants = ' . $this->db->dbprefix('tenants') . '.id' .
                ' AND status = "published") AS avg_rating',
            )
            ->where('subdomain', $subdomain)
            ->where('marketplace_opt_in', 1)
            ->where('status', 'active')
            ->get('tenants')
            ->row_array();

        if (!$tenant) {
            abort(404, 'Not Found');
        }

        $reviews = $this->db
            ->where('id_tenants', $tenant['id'])
            ->where('status', 'published')
            ->order_by('created_at', 'desc')
            ->limit(50)
            ->get('reviews')
            ->result_array();

        // Calculate app domain for the "Book Appointment" link
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'reservationapp.kibusiness.co';
        $booking_url = 'https://' . $tenant['subdomain'] . '-' . $app_domain . '/booking';

        if (!empty($tenant['custom_domain'])) {
            $booking_url = 'https://' . $tenant['custom_domain'] . '/booking';
        }

        html_vars([
            'page_title' => $tenant['company_name'] ?? $tenant['subdomain'],
            'tenant' => $tenant,
            'reviews' => $reviews,
            'booking_url' => $booking_url,
        ]);

        $this->load->view('pages/marketplace_business');
    }

    /**
     * Submit a review for a tenant.
     *
     * POST parameters:
     * - id_tenants: tenant ID
     * - customer_name: reviewer name
     * - customer_phone_hash: hashed phone (optional)
     * - rating: 1-5 rating
     * - comment: review text
     * - source_appointment_hash: appointment hash (for linking, optional - will generate random if not provided)
     */
    public function submit_review(): void
    {
        try {
            method('post');

            check('id_tenants', 'numeric');
            check('customer_name', 'string');
            check('customer_phone_hash', 'string|null');
            check('rating', 'numeric');
            check('comment', 'string|null');
            check('source_appointment_hash', 'string|null');

            $tenant_id = (int) request('id_tenants');
            $customer_name = trim((string) request('customer_name'));
            $customer_phone_hash = trim((string) request('customer_phone_hash'));
            $rating = (int) request('rating');
            $comment = trim((string) request('comment'));
            $source_appointment_hash = trim((string) request('source_appointment_hash'));

            // Validate tenant exists and is opted-in
            $tenant = $this->db
                ->get_where('tenants', ['id' => $tenant_id, 'marketplace_opt_in' => 1])
                ->row_array();

            if (!$tenant) {
                throw new InvalidArgumentException('İşletme marketplace\'te bulunamadı.');
            }

            // Validate rating
            if ($rating < 1 || $rating > 5) {
                throw new InvalidArgumentException('Derecelendirme 1-5 arasında olmalıdır.');
            }

            // Validate customer name
            if ($customer_name === '' || strlen($customer_name) > 128) {
                throw new InvalidArgumentException('Geçerli bir müşteri adı girin.');
            }

            // If no source hash provided, generate a random one to avoid UNIQUE constraint violation
            // TODO: In production, should link to actual appointment in tenant's DB for verification
            if ($source_appointment_hash === '') {
                $source_appointment_hash = bin2hex(random_bytes(32));
            }

            $this->db->insert('reviews', [
                'id_tenants' => $tenant_id,
                'customer_name' => $customer_name,
                'customer_phone_hash' => $customer_phone_hash !== '' ? $customer_phone_hash : null,
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
                'source_appointment_hash' => $source_appointment_hash,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            json_response([
                'success' => true,
                'message' => 'Yorum başarıyla gönderildi. Yayınlanması için incelemeniz bekleniyor.',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
