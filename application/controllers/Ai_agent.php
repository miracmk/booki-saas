<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - AI Asistan admin controller (Dalga 4, 2026-09-12).
 *
 * Thin controller: conversation state lives in the PHP session, all the
 * actual chat/tool-calling logic is in Ai_agent_client. This file is only
 * responsible for auth/permission gating, the pending-change approval
 * workflow (the one place customer data actually gets mutated), and
 * rendering the page.
 *
 * @package Controllers
 */
class Ai_agent extends EA_Controller
{
    private const SESSION_KEY = 'ai_agent_history';

    private const MAX_HISTORY_MESSAGES = 20;

    public function __construct()
    {
        parent::__construct();

        require_plan_feature(PRIV_AI_AGENT);

        $this->load->library('ai_agent_client');
    }

    /**
     * Render the AI Asistan page (chat + pending approval queue).
     */
    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_AI_AGENT)) {
            abort(403, 'Forbidden');
        }

        html_vars([
            'page_title' => 'AI Asistan',
            'active_menu' => PRIV_AI_AGENT,
            'history' => $this->session->userdata(self::SESSION_KEY) ?: [],
            'pending' => $this->get_pending_changes(),
        ]);

        script_vars([
            'routes' => [
                'chat' => site_url('ai_agent/chat'),
                'pending' => site_url('ai_agent/pending'),
                'approve' => site_url('ai_agent/approve'),
                'reject' => site_url('ai_agent/reject'),
                'reset' => site_url('ai_agent/reset'),
            ],
        ]);

        $this->load->view('pages/ai_agent');
    }

    /**
     * Send one user message, run the tool-calling loop, return the assistant's reply.
     * Conversation history is kept server-side in the PHP session (not DB - see
     * migration 137 doc comment) so a page refresh doesn't lose context, but it's
     * not shared across devices/staff and is capped to avoid unbounded growth.
     */
    public function chat(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('message', 'string');
            $user_message = trim((string) request('message'));

            if ($user_message === '') {
                throw new InvalidArgumentException('Mesaj boş olamaz.');
            }

            $history = $this->session->userdata(self::SESSION_KEY) ?: [];
            $history[] = ['role' => 'user', 'content' => $user_message];

            $result = $this->ai_agent_client->chat($history);

            $history[] = ['role' => 'assistant', 'content' => $result['reply']];

            // Cap history length (keep it recent, not endless).
            if (count($history) > self::MAX_HISTORY_MESSAGES) {
                $history = array_slice($history, -self::MAX_HISTORY_MESSAGES);
            }

            $this->session->set_userdata(self::SESSION_KEY, $history);

            json_response([
                'success' => true,
                'reply' => $result['reply'],
                'tool_calls' => $result['tool_calls'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Clear the conversation (start fresh) without touching pending changes.
     */
    public function reset(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $this->session->unset_userdata(self::SESSION_KEY);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * List pending (not yet resolved) proposed changes.
     */
    public function pending(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            json_response(['success' => true, 'pending' => $this->get_pending_changes()]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Approve a pending change: apply it via Customers_model::save() (which
     * re-validates like every other write path) then mark it resolved.
     */
    public function approve(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('id', 'integer');
            $id = (int) request('id');

            $change = $this->db->get_where('ai_agent_pending_changes', ['id' => $id, 'status' => 'pending'])->row_array();

            if (!$change) {
                throw new InvalidArgumentException('Bekleyen değişiklik bulunamadı.');
            }

            if ($change['target_table'] === 'users') {
                $this->load->model('customers_model');

                $customer = $this->customers_model->find((int) $change['target_id']);
                $changes = json_decode((string) $change['changes'], true) ?: [];

                $this->customers_model->save(array_merge($customer, $changes));
            } else {
                throw new RuntimeException('Bilinmeyen hedef tablo: ' . $change['target_table']);
            }

            $this->db->update('ai_agent_pending_changes', [
                'status' => 'approved',
                'resolved_at' => date('Y-m-d H:i:s'),
                'resolved_by' => session('user_id'),
            ], ['id' => $id]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Reject a pending change without applying it.
     */
    public function reject(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('id', 'integer');
            $id = (int) request('id');

            $this->db->update('ai_agent_pending_changes', [
                'status' => 'rejected',
                'resolved_at' => date('Y-m-d H:i:s'),
                'resolved_by' => session('user_id'),
            ], ['id' => $id, 'status' => 'pending']);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function get_pending_changes(): array
    {
        return $this->db
            ->where('status', 'pending')
            ->order_by('created_at', 'desc')
            ->get('ai_agent_pending_changes')
            ->result_array();
    }
}
