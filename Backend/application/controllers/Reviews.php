<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Reviews (Dalga 3 / Faz 3.4).
 *
 * Tenant admin page + JSON API for moderating customer reviews. Reviews arrive
 * here as "pending" (customer already submitted via the single-use link) and are
 * either published (mirrored to the master DB so marketplace shows them) or
 * rejected (removed from the marketplace entirely).
 * ---------------------------------------------------------------------------- */

class Reviews extends App_Controller
{
    /**
     * Reviews constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_plan_feature(PRIV_REVIEWS);

        $this->load->library('accounts');
        $this->load->library('review_service');
    }

    /**
     * Render the review moderation page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('reviews')]);

        if (cannot('view', PRIV_REVIEWS)) {
            if (session('user_id')) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $user_id = session('user_id');
        $role_slug = session('role_slug');

        $this->load->model('roles_model');
        $this->load->model('reviews_model');
        $this->load->model('providers_model');
        $this->load->model('stations_model');

        $reviews = $this->reviews_model->get();
        $providers = $this->providers_model->get_available_providers();
        $stations = $this->stations_model->get();

        html_vars([
            'page_title' => 'Yorumlar',
            'active_menu' => PRIV_REVIEWS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'initials' => [
                'can_view' => can('view', PRIV_REVIEWS),
                'can_add' => can('add', PRIV_REVIEWS),
                'can_edit' => can('edit', PRIV_REVIEWS),
                'can_delete' => can('delete', PRIV_REVIEWS),
            ],
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'reviews' => $reviews,
            'providers' => $providers,
            'stations' => $stations,
            'counts' => $this->reviews_model->counts(),
            'routes' => [
                'publish' => site_url('reviews/publish_review'),
                'reject' => site_url('reviews/reject_review'),
            ],
            'initials' => [
                'can_view' => can('view', PRIV_REVIEWS),
                'can_add' => can('add', PRIV_REVIEWS),
                'can_edit' => can('edit', PRIV_REVIEWS),
                'can_delete' => can('delete', PRIV_REVIEWS),
            ],
        ]);

        $this->load->view('pages/reviews', [
            'reviews' => $reviews,
            'providers' => $providers,
            'stations' => $stations,
        ]);
    }

    /**
     * GET → list reviews (optional status, provider, station filters).
     */
    public function get_reviews(): void
    {
        method('get');

        if (cannot('view', PRIV_REVIEWS)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('reviews_model');

        $status = request('status') ?: null;
        $provider_id = (int) request('provider_id') ?: null;
        $station_id = (int) request('station_id') ?: null;

        json_response([
            'reviews' => $this->reviews_model->get($status, $provider_id, $station_id),
            'counts' => $this->reviews_model->counts(),
        ]);
    }

    /**
     * POST → publish a review (pending → published, mirror to master).
     */
    public function publish_review(): void
    {
        method('post');

        if (cannot('edit', PRIV_REVIEWS)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('reviews_model');

        try {
            $review_id = (int) request('review_id');

            if ($review_id <= 0) {
                throw new InvalidArgumentException('Geçersiz yorum kimliği.');
            }

            $review = $this->reviews_model->moderate($review_id, Reviews_model::STATUS_PUBLISHED, (int) session('user_id'));

            $this->review_service->mirror_to_master($review, Reviews_model::STATUS_PUBLISHED);

            json_response([
                'success' => true,
                'message' => 'Yorum yayınlandı.',
                'review' => $review,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → reject a review (pending → rejected, remove from marketplace).
     */
    public function reject_review(): void
    {
        method('post');

        if (cannot('edit', PRIV_REVIEWS)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('reviews_model');

        try {
            $review_id = (int) request('review_id');

            if ($review_id <= 0) {
                throw new InvalidArgumentException('Geçersiz yorum kimliği.');
            }

            $review = $this->reviews_model->moderate($review_id, Reviews_model::STATUS_REJECTED, (int) session('user_id'));

            $this->review_service->mirror_to_master($review, Reviews_model::STATUS_REJECTED);

            json_response([
                'success' => true,
                'message' => 'Yorum reddedildi.',
                'review' => $review,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}