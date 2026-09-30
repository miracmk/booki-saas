<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Meta Marketing & Conversions API (CAPI) Client (Dalga 5 / Enterprise Suite).
 *
 * Direct REST client for Meta Graph API (v20.0):
 * - Meta Marketing API: campaigns list, status toggle (ACTIVE/PAUSED), insights (spend, clicks, roas)
 * - Meta Conversions API (CAPI): server-to-server event dispatch (Schedule, Purchase, Lead)
 *   with SHA-256 hashed customer parameters (em, ph, fn, ln), fbp/fbc cookies, and event_id deduplication.
 * - Test event code support for Meta Events Manager real-time debugging.
 * ---------------------------------------------------------------------------- */

class Meta_marketing_client
{
    protected CI_Controller $CI;
    protected array $settings;
    protected string $apiVersion = 'v26.0';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('settings_model');
        $this->settings = $this->CI->settings_model->get();
    }

    public function refresh_settings(): void
    {
        $this->settings = $this->CI->settings_model->get();
    }

    public function is_meta_capi_configured(): bool
    {
        return !empty($this->settings['meta_pixel_id']) && !empty($this->settings['meta_capi_token']);
    }

    public function is_meta_ads_configured(): bool
    {
        return !empty($this->settings['meta_ad_account_id']) && !empty($this->settings['meta_capi_token']);
    }

    /**
     * Meta Marketing API: Get campaigns from ad account.
     * GET https://graph.facebook.com/v20.0/act_{ad_account_id}/campaigns
     *
     * @return array
     */
    public function get_campaigns(): array
    {
        $adAccountId = $this->get_clean_ad_account_id();
        $token = trim($this->settings['meta_capi_token'] ?? '');

        if (empty($adAccountId) || empty($token)) {
            // Simulated campaign metrics based on Meta Marketing API v20.0
            return [
                'configured' => false,
                'ad_account_id' => $adAccountId ?: 'act_1029384756',
                'campaigns' => [
                    [
                        'id' => '1202093847561001',
                        'name' => 'Instagram Reels - Güzellik & Bakım Dönüşüm',
                        'status' => 'ACTIVE',
                        'objective' => 'OUTCOME_SALES',
                        'daily_budget' => '150.00 TL',
                        'impressions' => 38400,
                        'clicks' => 1420,
                        'spend' => '2150.00 TL',
                        'conversions' => 68,
                        'cpc' => '1.51 TL',
                        'roas' => '4.6x',
                    ],
                    [
                        'id' => '1202093847561002',
                        'name' => 'Facebook Feed - Hafta Sonu Randevu Fırsatı',
                        'status' => 'ACTIVE',
                        'objective' => 'OUTCOME_LEADS',
                        'daily_budget' => '100.00 TL',
                        'impressions' => 19200,
                        'clicks' => 680,
                        'spend' => '1200.00 TL',
                        'conversions' => 41,
                        'cpc' => '1.76 TL',
                        'roas' => '3.8x',
                    ],
                    [
                        'id' => '1202093847561003',
                        'name' => 'Story Yeniden Hedefleme (Retargeting)',
                        'status' => 'PAUSED',
                        'objective' => 'OUTCOME_SALES',
                        'daily_budget' => '80.00 TL',
                        'impressions' => 7400,
                        'clicks' => 310,
                        'spend' => '540.00 TL',
                        'conversions' => 29,
                        'cpc' => '1.74 TL',
                        'roas' => '6.4x',
                    ],
                ],
                'note' => 'Örnek Meta Ads verisi - Canlı Meta Reklam Hesabı ve CAPI Access Token bağlandığında gerçek veriler listelenir.',
            ];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$adAccountId}/campaigns?" . http_build_query([
            'fields' => 'id,name,status,objective,daily_budget,lifetime_budget,insights{impressions,clicks,spend,cpc,cpm,actions,purchase_roas}',
            'access_token' => $token,
            'limit' => 50,
        ]);

        $response = $this->http_get_json($url);

        if (!$response['success']) {
            return [
                'configured' => true,
                'error' => $response['error'],
                'campaigns' => [],
            ];
        }

        $campaigns = [];
        foreach ($response['data']['data'] ?? [] as $c) {
            $insight = $c['insights']['data'][0] ?? [];
            $spend = (float) ($insight['spend'] ?? 0);
            $clicks = (int) ($insight['clicks'] ?? 0);
            $cpc = (float) ($insight['cpc'] ?? ($clicks > 0 ? $spend / $clicks : 0));
            $budget = !empty($c['daily_budget']) ? number_format($c['daily_budget'] / 100, 2) . ' TL' : '-';

            // Find purchase or schedule actions
            $conv = 0;
            foreach ($insight['actions'] ?? [] as $act) {
                if (in_array($act['action_type'] ?? '', ['schedule', 'purchase', 'lead', 'onsite_conversion.total_actions'], true)) {
                    $conv += (int) ($act['value'] ?? 0);
                }
            }

            $roas = '-';
            if (!empty($insight['purchase_roas'][0]['value'])) {
                $roas = round((float) $insight['purchase_roas'][0]['value'], 1) . 'x';
            }

            $campaigns[] = [
                'id' => $c['id'],
                'name' => $c['name'],
                'status' => $c['status'],
                'objective' => $c['objective'] ?? '',
                'daily_budget' => $budget,
                'impressions' => (int) ($insight['impressions'] ?? 0),
                'clicks' => $clicks,
                'spend' => number_format($spend, 2) . ' TL',
                'conversions' => $conv,
                'cpc' => number_format($cpc, 2) . ' TL',
                'roas' => $roas,
            ];
        }

        return [
            'configured' => true,
            'ad_account_id' => $adAccountId,
            'campaigns' => $campaigns,
        ];
    }

    /**
     * Meta Marketing API: Update campaign status (ACTIVE or PAUSED).
     * POST https://graph.facebook.com/v20.0/{campaign_id}
     *
     * @param string $campaignId
     * @param string $status 'ACTIVE' or 'PAUSED'
     * @return array
     */
    public function update_campaign_status(string $campaignId, string $status = 'PAUSED'): array
    {
        $token = trim($this->settings['meta_capi_token'] ?? '');

        if (empty($token)) {
            return [
                'success' => true,
                'simulated' => true,
                'campaign_id' => $campaignId,
                'status' => $status,
                'message' => "Meta Marketing API simülasyon modu: Kampanya {$campaignId} durumu '{$status}' olarak ayarlandı.",
            ];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$campaignId}";

        $body = [
            'status' => $status,
            'access_token' => $token,
        ];

        return $this->http_post_form($url, $body);
    }

    /**
     * Meta Conversions API (CAPI): Send server-to-server event.
     * POST https://graph.facebook.com/v20.0/{pixel_id}/events
     *
     * @param string $eventName 'Schedule', 'Purchase', 'Lead', 'Contact', etc.
     * @param array $customer Customer data (first_name, last_name, email, phone_number)
     * @param array $customData Event metrics (value, currency, content_name, order_id)
     * @param array $attribution Context (ip_address, user_agent, fbp, fbc, fbclid, event_source_url)
     * @param string|null $eventId Unique deduplication ID
     * @return array
     */
    public function send_event(
        string $eventName,
        array $customer,
        array $customData = [],
        array $attribution = [],
        ?string $eventId = null
    ): array {
        $pixelId = trim($this->settings['meta_pixel_id'] ?? '');
        $token = trim($this->settings['meta_capi_token'] ?? '');
        $testCode = trim($this->settings['meta_test_event_code'] ?? '');

        $eventId = $eventId ?: 'evt_' . bin2hex(random_bytes(10));
        $eventTime = time();

        // Customer Information Parameters normalized and SHA-256 hashed according to Meta specs
        $userData = [];

        if (!empty($customer['email'])) {
            $userData['em'] = [hash('sha256', strtolower(trim($customer['email'])))];
        }
        if (!empty($customer['phone_number'])) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $customer['phone_number']);
            // If local Turkish 10 digits starting with 5, prepend country code 90
            if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '5')) {
                $cleanPhone = '90' . $cleanPhone;
            }
            $userData['ph'] = [hash('sha256', $cleanPhone)];
        }
        if (!empty($customer['first_name'])) {
            $userData['fn'] = [hash('sha256', strtolower(trim($customer['first_name'])))];
        }
        if (!empty($customer['last_name'])) {
            $userData['ln'] = [hash('sha256', strtolower(trim($customer['last_name'])))];
        }

        // Client network parameters (unhashed per Meta CAPI spec)
        if (!empty($attribution['ip_address'])) {
            $userData['client_ip_address'] = $attribution['ip_address'];
        }
        if (!empty($attribution['user_agent'])) {
            $userData['client_user_agent'] = $attribution['user_agent'];
        }

        // Click ID / Meta cookies
        if (!empty($attribution['fbp'])) {
            $userData['fbp'] = $attribution['fbp'];
        }
        if (!empty($attribution['fbc'])) {
            $userData['fbc'] = $attribution['fbc'];
        } elseif (!empty($attribution['fbclid'])) {
            $userData['fbc'] = "fb.1.{$eventTime}." . $attribution['fbclid'];
        }

        $eventPayload = [
            'event_name' => $eventName,
            'event_time' => $eventTime,
            'event_id' => $eventId,
            'event_source_url' => $attribution['event_source_url'] ?? site_url('booking'),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => array_merge([
                'currency' => 'TRY',
                'value' => (float) ($customData['value'] ?? 0),
            ], $customData),
        ];

        if (empty($pixelId) || empty($token)) {
            return [
                'success' => true,
                'simulated' => true,
                'event_name' => $eventName,
                'event_id' => $eventId,
                'events_received' => 1,
                'payload' => $eventPayload,
                'message' => "Meta Conversions API (CAPI) simülasyon modu: '{$eventName}' sunucu olayı başarıyla oluşturuldu ve hazırlandı.",
            ];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$pixelId}/events";

        $postBody = [
            'data' => [$eventPayload],
            'access_token' => $token,
        ];

        if (!empty($testCode)) {
            $postBody['test_event_code'] = $testCode;
        }

        return $this->http_post_json($url, $postBody);
    }

    protected function get_clean_ad_account_id(): string
    {
        $id = trim($this->settings['meta_ad_account_id'] ?? '');
        if (empty($id)) return '';
        if (!str_starts_with($id, 'act_')) {
            $id = 'act_' . $id;
        }
        return $id;
    }

    protected function http_get_json(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => "cURL error: {$curlError}"];
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decoded];
        }

        $errMsg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
        return ['success' => false, 'error' => $errMsg, 'data' => $decoded];
    }

    protected function http_post_form(string $url, array $params): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => "cURL error: {$curlError}"];
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decoded];
        }

        $errMsg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
        return ['success' => false, 'error' => $errMsg, 'data' => $decoded];
    }

    protected function http_post_json(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => "cURL error: {$curlError}"];
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decoded];
        }

        $errMsg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
        return ['success' => false, 'error' => $errMsg, 'data' => $decoded];
    }
}
