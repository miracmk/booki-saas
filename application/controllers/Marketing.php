<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Marketing (Dalga 3 / Faz 3.3).
 *
 * Admin page + JSON API for customer segments and broadcast campaigns.
 * Segments are named customer lists (vip / inactive / birthday / all / custom);
 * campaigns broadcast a message to a segment over email / sms / whatsapp /
 * telegram. Sends are batched via Campaigns_model::send_batch() so long
 * deliveries never block a single request.
 * ---------------------------------------------------------------------------- */

class Marketing extends EA_Controller
{
    /**
     * Marketing constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_plan_feature(PRIV_MARKETING);

        $this->load->library('accounts');
    }

    /**
     * Render the marketing management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('marketing')]);

        if (cannot('view', PRIV_MARKETING)) {
            if (session('user_id')) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $user_id = session('user_id');
        $role_slug = session('role_slug');

        $this->load->model('roles_model');
        $this->load->model('segments_model');
        $this->load->model('campaigns_model');

        html_vars([
            'page_title' => 'Pazarlama',
            'active_menu' => PRIV_MARKETING,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'initials' => [
                'can_add' => can('add', PRIV_MARKETING),
                'can_edit' => can('edit', PRIV_MARKETING),
                'can_delete' => can('delete', PRIV_MARKETING),
            ],
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'segments' => $this->segments_model->get(),
            'campaigns' => $this->campaigns_model->get(),
            'initials' => [
                'can_add' => can('add', PRIV_MARKETING),
                'can_edit' => can('edit', PRIV_MARKETING),
                'can_delete' => can('delete', PRIV_MARKETING),
            ],
        ]);

        $this->load->view('pages/marketing', [
            'segments' => $this->segments_model->get(),
            'campaigns' => $this->campaigns_model->get(),
        ]);
    }

    /**
     * GET → list all segments.
     */
    public function get_segments(): void
    {
        method('get');

        if (cannot('view', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('segments_model');

        json_response(['segments' => $this->segments_model->get()]);
    }

    /**
     * POST → create a segment.
     */
    public function create_segment(): void
    {
        method('post');

        if (cannot('add', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('segments_model');

        try {
            $rules = request('rules');

            $id = $this->segments_model->save([
                'name' => request('name'),
                'type' => request('type'),
                'rules' => is_array($rules) ? json_encode($rules, JSON_UNESCAPED_UNICODE) : null,
                'enabled' => (int) (bool) request('enabled', 1),
            ]);

            json_response(['id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → update a segment.
     */
    public function update_segment(): void
    {
        method('post');

        if (cannot('edit', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('segments_model');

        try {
            $rules = request('rules');

            $id = $this->segments_model->save([
                'id' => (int) request('id'),
                'name' => request('name'),
                'type' => request('type'),
                'rules' => is_array($rules) ? json_encode($rules, JSON_UNESCAPED_UNICODE) : null,
                'enabled' => (int) (bool) request('enabled', 1),
            ]);

            json_response(['id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → delete a segment.
     */
    public function delete_segment(): void
    {
        method('post');

        if (cannot('delete', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('segments_model');

        try {
            $this->segments_model->delete((int) request('id'));

            json_response(['deleted' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → refresh one segment's member count.
     */
    public function refresh_segment(): void
    {
        method('post');

        if (cannot('edit', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('segments_model');

        try {
            $count = $this->segments_model->refresh_count((int) request('id'));

            json_response(['id' => (int) request('id'), 'member_count' => $count]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → refresh every segment's member count.
     */
    public function refresh_all_segments(): void
    {
        method('post');

        if (cannot('edit', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('segments_model');

        try {
            json_response(['counts' => $this->segments_model->refresh_all_counts()]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * GET → list all campaigns.
     */
    public function get_campaigns(): void
    {
        method('get');

        if (cannot('view', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('campaigns_model');

        json_response(['campaigns' => $this->campaigns_model->get()]);
    }

    /**
     * POST → create a campaign.
     */
    public function create_campaign(): void
    {
        method('post');

        if (cannot('add', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('campaigns_model');

        try {
            $id = $this->campaigns_model->save([
                'name' => request('name'),
                'segment_id' => (int) request('segment_id'),
                'channel' => request('channel'),
                'subject' => request('subject'),
                'message' => request('message'),
            ]);

            json_response(['id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → update a campaign.
     */
    public function update_campaign(): void
    {
        method('post');

        if (cannot('edit', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('campaigns_model');

        try {
            $id = $this->campaigns_model->save([
                'id' => (int) request('id'),
                'name' => request('name'),
                'segment_id' => (int) request('segment_id'),
                'channel' => request('channel'),
                'subject' => request('subject'),
                'message' => request('message'),
            ]);

            json_response(['id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → delete a campaign.
     */
    public function delete_campaign(): void
    {
        method('post');

        if (cannot('delete', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('campaigns_model');

        try {
            $this->campaigns_model->delete((int) request('id'));

            json_response(['deleted' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → resolve the campaign's segment into recipient rows.
     */
    public function prepare_campaign(): void
    {
        method('post');

        if (cannot('edit', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('campaigns_model');

        try {
            $recipients = $this->campaigns_model->prepare_broadcast((int) request('id'));

            json_response(['recipients' => $recipients]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST → send the next batch of a campaign.
     * Call repeatedly until the returned status leaves 'sending'/'queued'.
     */
    public function send_campaign(): void
    {
        method('post');

        if (cannot('edit', PRIV_MARKETING)) {
            json_response(['message' => 'Bu işlem için yetkiniz yok.'], 403);

            return;
        }

        $this->load->model('campaigns_model');

        try {
            json_response($this->campaigns_model->send_batch((int) request('id'), (int) request('limit', 50)));
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}