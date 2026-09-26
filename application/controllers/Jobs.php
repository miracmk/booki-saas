<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Jobs monitoring controller (Faz 32+33, 2026-08-28).
 *
 * Provides an admin interface for viewing queue status and manually retrying
 * failed jobs. Access is gated on the PRIV_SYSTEM_SETTINGS permission.
 * ---------------------------------------------------------------------------- */

class Jobs extends App_Controller
{
    /**
     * Jobs controller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('jobs_model');
        $this->load->model('roles_model');
        $this->load->library('accounts');
    }

    /**
     * Render the jobs monitoring page.
     */
    public function index(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            session(['dest_url' => site_url('jobs')]);

            $user_id = session('user_id');
            $role_slug = session('role_slug');

            html_vars([
                'page_title' => 'İş Kuyruğu',
                'active_menu' => PRIV_SYSTEM_SETTINGS,
                'user_display_name' => $this->accounts->get_user_display_name($user_id),
                'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            ]);

            script_vars([
                'user_id' => $user_id,
                'role_slug' => $role_slug,
            ]);

            $this->load->view('pages/jobs');
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * List recent failed jobs and queue status.
     *
     * Returns an array with overall queue counts and the most recent failed jobs.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $limit = (int) request('limit', 50);
            $offset = (int) request('offset', 0);

            // Get overall queue counts.
            $counts = $this->jobs_model->count_by_status();

            // Get recent failed jobs.
            $failures = $this->jobs_model->recent_failures(60, $limit);

            json_response([
                'counts' => $counts,
                'failures' => $failures,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Retry a failed job.
     *
     * Resets the job's state to pending so it can be picked up by the queue again.
     */
    public function retry(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            check('job_id', 'numeric');

            $job_id = (int) request('job_id');

            $job = $this->jobs_model->find($job_id);

            if (!$job) {
                throw new RuntimeException('Job not found.');
            }

            $this->jobs_model->retry($job_id);

            audit_log('job.retry', 'job', $job_id, [
                'status' => $job['status'],
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
