<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Zadarma webhook dinleyicisi (call status tracker).
 * Zadarma panelinde "API > Webhook URL" olarak tanimlayin:
 *   https://<domain>/zadarma/webhook
 * Olaylar: NOTIFY_START / NOTIFY_INTERNAL / NOTIFY_ANSWER / NOTIFY_END
 * + zd_echo dogrulamasi (gelen string aynen dondurulur).
 * ---------------------------------------------------------------------------- */

class Zadarma extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_model');
    }

    /**
     * Zadarma sunucusunun cagirdigi webhook.
     * CSRF disi birakildi (config.php csrf_exclude_uris icinde).
     */
    public function webhook(): void
    {
        // 1) zd_echo dogrulamasi
        $echo = $this->input->post_get('zd_echo');
        if ($echo === null) {
            $raw = file_get_contents('php://input');
            parse_str((string) $raw, $parsed);
            $echo = $parsed['zd_echo'] ?? null;
        }
        if ($echo !== null && $echo !== '') {
            header('Content-Type: text/plain; charset=utf-8');
            echo (string) $echo;
            return;
        }

        $event = (string) ($this->input->post_get('event') ?: '');
        $call_id = (string) ($this->input->post_get('call_id') ?: $this->input->post_get('pbx_call_id') ?: '');
        $from = (string) ($this->input->post_get('caller_id') ?: $this->input->post_get('from') ?: '');
        $to = (string) ($this->input->post_get('called_did') ?: $this->input->post_get('to') ?: '');
        $status = strtolower((string) ($this->input->post_get('call_status') ?: ''));

        log_message(
            'info',
            'Zadarma::webhook event=' . $event . ' call_id=' . $call_id .
            ' from=' . $from . ' to=' . $to . ' status=' . $status
        );

        // Durumu lead aktivitesine isle (best-effort, sessiz gec).
        try {
            if ($event !== '' && $this->db->table_exists('lead_activities')) {
                $title = match ($event) {
                    'NOTIFY_START' => 'Cagri basladi (caliyor)',
                    'NOTIFY_INTERNAL' => 'Dahili hat cevaplandi',
                    'NOTIFY_ANSWER' => 'Musteri cevaplandi',
                    'NOTIFY_END' => 'Cagri sona erdi',
                    default => 'Zadarma olayi: ' . $event,
                };
                $this->db->insert('lead_activities', [
                    'activity_type' => 'call',
                    'title' => $title,
                    'description' => 'Zadarma webhook: ' . $event . ' (' . $from . ' -> ' . $to . ')',
                    'performed_by' => 'Zadarma Webhook',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', 'Zadarma::webhook log fail: ' . $e->getMessage());
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo 'OK';
    }
}
