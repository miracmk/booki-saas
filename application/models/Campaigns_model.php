<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi (Dalga 3 / Faz 3.3) - Marketing Campaigns Model
 *
 * Named broadcast campaigns. A campaign targets a segment; prepare_broadcast()
 * resolves the segment into campaign_recipients rows (pending); send_batch()
 * delivers a slice of pending recipients over the chosen channel via the
 * Notification library (which honours the job queue) and records per-recipient
 * outcomes. Campaign lifecycle: draft → queued → sending → sent|failed.
 * ---------------------------------------------------------------------------- */

class Campaigns_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'segment_id' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'total_recipients' => 'integer',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';

    /**
     * Pause an active or queued campaign.
     */
    public function pause(int $campaign_id): bool
    {
        $this->find($campaign_id);
        $this->db->update('marketing_campaigns', [
            'status' => self::STATUS_PAUSED,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $campaign_id]);

        return true;
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(int $campaign_id): bool
    {
        $this->find($campaign_id);
        $this->db->update('marketing_campaigns', [
            'status' => self::STATUS_ACTIVE,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $campaign_id]);

        return true;
    }

    /**
     * Get a specific campaign.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $campaign_id): array
    {
        $campaign = $this->db->get_where('marketing_campaigns', ['id' => $campaign_id])->row_array();

        if (!$campaign) {
            throw new InvalidArgumentException('Kampanya bulunamadı: ' . $campaign_id);
        }

        $this->cast($campaign);

        return $campaign;
    }

    /**
     * Get all campaigns, newest first.
     */
    public function get(): array
    {
        $campaigns = $this->db->order_by('created_at', 'DESC')->get('marketing_campaigns')->result_array();

        foreach ($campaigns as &$campaign) {
            $this->cast($campaign);
        }

        return $campaigns;
    }

    /**
     * Save (insert or update) a campaign.
     *
     * @return int Campaign ID.
     * @throws InvalidArgumentException|RuntimeException
     */
    public function save(array $campaign): int
    {
        $this->validate($campaign);

        $now = date('Y-m-d H:i:s');

        if (empty($campaign['id'])) {
            unset($campaign['id']);
            $campaign['created_at'] = $now;
            $campaign['updated_at'] = $now;
            $campaign['sent_count'] = 0;
            $campaign['failed_count'] = 0;
            $campaign['total_recipients'] = 0;
            $campaign['status'] ??= self::STATUS_DRAFT;
            $campaign['campaign_type'] ??= 'broadcast';

            if (!$this->db->insert('marketing_campaigns', $campaign)) {
                throw new RuntimeException('Kampanya eklenemedi.');
            }

            return (int) $this->db->insert_id();
        }

        $campaign['updated_at'] = $now;

        if (!$this->db->update('marketing_campaigns', $campaign, ['id' => $campaign['id']])) {
            throw new RuntimeException('Kampanya güncellenemedi.');
        }

        return (int) $campaign['id'];
    }

    /**
     * Delete a campaign and its recipient rows.
     */
    public function delete(int $campaign_id): void
    {
        $this->db->delete('campaign_recipients', ['campaign_id' => $campaign_id]);
        $this->db->delete('marketing_campaigns', ['id' => $campaign_id]);
    }

    /**
     * Validate a campaign row.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $campaign): void
    {
        if (empty($campaign['name'])) {
            throw new InvalidArgumentException('Kampanya adı zorunludur.');
        }

        $campaignType = $campaign['campaign_type'] ?? 'broadcast';
        $isExternalAd = in_array($campaignType, ['google_ads', 'meta_ads'], true);

        if (!$isExternalAd && (empty($campaign['segment_id']) || (int) $campaign['segment_id'] <= 0)) {
            throw new InvalidArgumentException('Hedef segment seçilmek zorundadır.');
        }

        $allowedChannels = ['email', 'sms', 'whatsapp', 'telegram', 'google_ads', 'meta_ads'];
        if (!in_array($campaign['channel'] ?? '', $allowedChannels, true)) {
            throw new InvalidArgumentException('Geçersiz kanal: ' . ($campaign['channel'] ?? ''));
        }

        if (!$isExternalAd && trim((string) ($campaign['message'] ?? '')) === '') {
            throw new InvalidArgumentException('Mesaj içeriği zorunludur.');
        }

        if (!empty($campaign['id'])) {
            $existing = $this->find((int) $campaign['id']);

            $editableStatuses = [self::STATUS_DRAFT, self::STATUS_FAILED, self::STATUS_ACTIVE, self::STATUS_PAUSED];
            if (!in_array($existing['status'], $editableStatuses, true)) {
                throw new InvalidArgumentException('Bu durumdaki kampanya düzenlenemez.');
            }
        }
    }

    /**
     * Resolve the target segment into pending campaign_recipients rows.
     * Idempotent: aborts if the campaign has already been prepared (status != draft).
     *
     * @return int Number of recipients added.
     * @throws InvalidArgumentException
     */
    public function prepare_broadcast(int $campaign_id): int
    {
        $campaign = $this->find($campaign_id);

        if ($campaign['status'] !== self::STATUS_DRAFT) {
            throw new InvalidArgumentException('Bu kampanya zaten hazırlanmış veya gönderilmiş.');
        }

        if ($campaign['segment_id'] <= 0) {
            throw new InvalidArgumentException('Kampanyanın hedef segmenti yok.');
        }

        $this->load->model('segments_model');

        $segment = $this->segments_model->find($campaign['segment_id']);

        $member_ids = $this->segments_model->get_member_ids($segment);
        $member_ids = array_values(array_unique(array_map('intval', $member_ids)));

        if ($member_ids === []) {
            $this->db->update('marketing_campaigns', [
                'total_recipients' => 0,
                'sent_count' => 0,
                'failed_count' => 0,
                'status' => self::STATUS_SENT,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $campaign_id]);

            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $rows = [];

        foreach ($member_ids as $customer_id) {
            $rows[] = [
                'campaign_id' => $campaign_id,
                'customer_id' => $customer_id,
                'channel' => $campaign['channel'],
                'recipient' => '',
                'status' => 'pending',
                'created_at' => $now,
            ];
        }

        // Chunked insert to stay under MySQL packet limits.
        foreach (array_chunk($rows, 200) as $chunk) {
            $this->db->insert_batch('campaign_recipients', $chunk);
        }

        $this->db->update('marketing_campaigns', [
            'total_recipients' => count($member_ids),
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => self::STATUS_QUEUED,
            'updated_at' => $now,
        ], ['id' => $campaign_id]);

        return count($member_ids);
    }

    /**
     * Send up to $limit pending recipients for a campaign.
     *
     * @return array<string,mixed> Summary: {status, sent, failed, stats:{total,sent,failed}}
     */
    public function send_batch(int $campaign_id, int $limit = 50): array
    {
        $campaign = $this->find($campaign_id);

        if (!in_array($campaign['status'], [self::STATUS_QUEUED, self::STATUS_SENDING], true)) {
            return [
                'status' => $campaign['status'],
                'sent' => 0,
                'failed' => 0,
                'stats' => $this->stats($campaign),
            ];
        }

        $this->db->update('marketing_campaigns', [
            'status' => self::STATUS_SENDING,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $campaign_id]);

        $pending = $this->db
            ->where('campaign_id', $campaign_id)
            ->where('status', 'pending')
            ->limit(max(1, $limit))
            ->get('campaign_recipients')
            ->result_array();

        $sent = 0;
        $failed = 0;

        foreach ($pending as $recipient_row) {
            $outcome = $this->send_to_recipient($campaign, (int) $recipient_row['id']);
            $outcome ? $sent++ : $failed++;
        }

        $remaining = (int) $this->db
            ->where('campaign_id', $campaign_id)
            ->where('status', 'pending')
            ->count_all_results('campaign_recipients');

        if ($remaining === 0) {
            $this->db->update('marketing_campaigns', [
                'status' => self::STATUS_SENT,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $campaign_id]);
        }

        $refreshed = $this->find($campaign_id);

        return [
            'status' => $refreshed['status'],
            'sent' => $sent,
            'failed' => $failed,
            'stats' => $this->stats($refreshed),
        ];
    }

    /**
     * Send a campaign message to one pending recipient; record the outcome.
     *
     * @return bool Whether the send was handed off successfully (queued or actually sent).
     */
    protected function send_to_recipient(array $campaign, int $recipient_id): bool
    {
        try {
            $this->load->model('customers_model');
            $this->load->library('notifications');

            $recipient_row = $this->db->get_where('campaign_recipients', ['id' => $recipient_id])->row_array();

            if (!$recipient_row) {
                return false;
            }

            $customer = $this->customers_model->find((int) $recipient_row['customer_id']);
            $customer['_recipient_type'] = 'customer';

            $text = $this->render_message($campaign, $customer);
            $subject = $this->render_subject($campaign, $customer);

            // Notifications do not throw; them gracefully returns on unconfigured channels.
            switch ($campaign['channel']) {
                case 'sms':
                    $this->notifications->send_sms($customer, $text);
                    $recipient = (string) ($customer['phone_number'] ?? '');
                    break;

                case 'whatsapp':
                    $this->notifications->send_whatsapp($customer, $text);
                    $recipient = (string) ($customer['phone_number'] ?? '');
                    break;

                case 'telegram':
                    $this->notifications->send_telegram($customer, $text);
                    $recipient = (string) ($customer['telegram_chat_id'] ?? '');
                    break;

                case 'email':
                default:
                    $this->notifications->send_generic_email($customer, $subject, $text);
                    $recipient = (string) ($customer['email'] ?? '');
                    break;
            }

            $now = date('Y-m-d H:i:s');

            $this->db->update('campaign_recipients', [
                'status' => 'sent',
                'recipient' => $recipient,
                'sent_at' => $now,
                'error_message' => null,
            ], ['id' => $recipient_id]);

            $this->db->set('sent_count', 'sent_count + 1', false)
                ->where('id', $campaign['id'])
                ->update('marketing_campaigns');

            return true;
        } catch (Throwable $e) {
            log_message('error', 'Campaigns::send_to_recipient(' . $recipient_id . ') failed: ' . $e->getMessage());

            $this->db->update('campaign_recipients', [
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ], ['id' => $recipient_id]);

            $this->db->set('failed_count', 'failed_count + 1', false)
                ->where('id', $campaign['id'])
                ->update('marketing_campaigns');

            return false;
        }
    }

    /**
     * Recompute the campaign status after a batch: sent when no pending remain.
     */
    protected function stats(array $campaign): array
    {
        return [
            'total' => $campaign['total_recipients'],
            'sent' => $campaign['sent_count'],
            'failed' => $campaign['failed_count'],
        ];
    }

    /**
     * Render the message with {{placeholder}} substitution per customer.
     */
    protected function render_message(array $campaign, array $customer): string
    {
        $this->load->model('settings_model');

        $settings = $this->settings_model->get();

        return strtr(trim((string) $campaign['message']), [
            '{{customer_name}}' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            '{{customer_first_name}}' => (string) ($customer['first_name'] ?? ''),
            '{{company_name}}' => (string) ($settings['company_name'] ?? ''),
        ]);
    }

    /**
     * Render the email subject with placeholder substitution.
     */
    protected function render_subject(array $campaign, array $customer): string
    {
        $this->load->model('settings_model');

        $settings = $this->settings_model->get();

        return strtr(trim((string) ($campaign['subject'] ?? '')), [
            '{{customer_name}}' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            '{{customer_first_name}}' => (string) ($customer['first_name'] ?? ''),
            '{{company_name}}' => (string) ($settings['company_name'] ?? ''),
        ]);
    }
}