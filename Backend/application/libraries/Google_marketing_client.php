<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Google Marketing & Analytics Client (Dalga 5 / Enterprise Suite).
 *
 * Direct REST client for upstream Google APIs:
 * - Google Analytics 4 (Data API v1): runReport, runRealtimeReport, getMetadata
 * - Google Ads API (REST v17): GAQL search, mutate campaign status, uploadClickConversions (offline conversions)
 * - Google Search Console API (Search Analytics)
 * - BigQuery Analytics Hub / Event Stream export formatting
 *
 * Implements standard Google APIs Explorer & REST schemas.
 * When credentials are not yet configured in tenant settings, returns clean
 * structured response states with setup instructions instead of breaking.
 * ---------------------------------------------------------------------------- */

class Google_marketing_client
{
    protected CI_Controller $CI;
    protected array $settings;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('settings_model');
        $this->settings = $this->CI->settings_model->get();
    }

    /**
     * Refresh settings from DB.
     */
    public function refresh_settings(): void
    {
        $this->settings = $this->CI->settings_model->get();
    }

    /**
     * Check if Google Analytics 4 credentials are configured.
     */
    public function is_ga4_configured(): bool
    {
        return !empty($this->settings['google_analytics_id']);
    }

    /**
     * Check if Google Ads API credentials are configured.
     */
    public function is_google_ads_configured(): bool
    {
        return !empty($this->settings['google_ads_id']);
    }

    /**
     * Google Analytics Data API v1: runRealtimeReport
     * POST https://analyticsdata.googleapis.com/v1beta/properties/{propertyId}:runRealtimeReport
     *
     * @param array $dimensions Dimensions e.g. ['unifiedScreenName', 'country', 'city', 'deviceCategory']
     * @param array $metrics Metrics e.g. ['activeUsers', 'conversions', 'eventCount']
     * @return array
     */
    public function run_ga4_realtime_report(array $dimensions = ['unifiedScreenName'], array $metrics = ['activeUsers']): array
    {
        $propertyId = $this->get_property_id();
        $token = $this->get_access_token();

        if (empty($propertyId) || empty($token)) {
            return [
                'configured' => false,
                'total_active_users' => 0,
                'rows' => [
                    ['dimension_values' => ['/booking'], 'metric_values' => ['1'], 'screen' => 'Randevu Rezervasyon', 'active_users' => 1],
                    ['dimension_values' => ['/services'], 'metric_values' => ['2'], 'screen' => 'Hizmetler', 'active_users' => 2],
                    ['dimension_values' => ['/landing/yaz-kampanyasi'], 'metric_values' => ['4'], 'screen' => 'Yaz Kampanyası Landing', 'active_users' => 4],
                ],
                'note' => 'Demo verisi - GA4 Property ID ve Google Service Account entegre edildiğinde canlı veriler akar.',
            ];
        }

        $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runRealtimeReport";

        $body = [
            'dimensions' => array_map(fn($d) => ['name' => $d], $dimensions),
            'metrics' => array_map(fn($m) => ['name' => $m], $metrics),
        ];

        $response = $this->http_post_json($url, $body, ["Authorization: Bearer {$token}"]);

        if (!$response['success']) {
            return [
                'configured' => true,
                'error' => $response['error'],
                'total_active_users' => 0,
                'rows' => [],
            ];
        }

        $data = $response['data'];
        $rows = [];
        $totalActive = 0;

        if (!empty($data['rows'])) {
            foreach ($data['rows'] as $r) {
                $dimVals = array_column($r['dimensionValues'] ?? [], 'value');
                $metVals = array_column($r['metricValues'] ?? [], 'value');
                $userCount = (int) ($metVals[0] ?? 0);
                $totalActive += $userCount;
                $rows[] = [
                    'dimension_values' => $dimVals,
                    'metric_values' => $metVals,
                    'screen' => $dimVals[0] ?? 'Bilinmeyen',
                    'active_users' => $userCount,
                ];
            }
        }

        return [
            'configured' => true,
            'total_active_users' => $totalActive,
            'rows' => $rows,
            'raw' => $data,
        ];
    }

    /**
     * Google Analytics Data API v1: runReport
     * POST https://analyticsdata.googleapis.com/v1beta/properties/{propertyId}:runReport
     *
     * @param string $startDate e.g. '30daysAgo' or '2026-08-17'
     * @param string $endDate e.g. 'today' or '2026-09-17'
     * @param array $dimensions
     * @param array $metrics
     * @return array
     */
    public function run_ga4_report(
        string $startDate = '30daysAgo',
        string $endDate = 'today',
        array $dimensions = ['sessionSource', 'sessionMedium', 'sessionCampaignName'],
        array $metrics = ['sessions', 'activeUsers', 'conversions', 'eventCount']
    ): array {
        $propertyId = $this->get_property_id();
        $token = $this->get_access_token();

        if (empty($propertyId) || empty($token)) {
            return [
                'configured' => false,
                'summary' => [
                    'sessions' => 1240,
                    'active_users' => 980,
                    'conversions' => 142,
                    'conversion_rate' => '11.45%',
                ],
                'sources' => [
                    ['source' => 'google', 'medium' => 'cpc', 'campaign' => 'Güzellik_Arama_2026', 'sessions' => 620, 'conversions' => 84, 'rate' => '13.5%'],
                    ['source' => 'meta', 'medium' => 'cpc', 'campaign' => 'Instagram_Reels_Tanıtım', 'sessions' => 410, 'conversions' => 46, 'rate' => '11.2%'],
                    ['source' => 'direct', 'medium' => '(none)', 'campaign' => '(direct)', 'sessions' => 210, 'conversions' => 12, 'rate' => '5.7%'],
                ],
                'note' => 'Demo raporu - Canlı GA4 verisi için Ayarlar > Google Analytics Property ID bağlayınız.',
            ];
        }

        $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";

        $body = [
            'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
            'dimensions' => array_map(fn($d) => ['name' => $d], $dimensions),
            'metrics' => array_map(fn($m) => ['name' => $m], $metrics),
        ];

        $response = $this->http_post_json($url, $body, ["Authorization: Bearer {$token}"]);

        if (!$response['success']) {
            return [
                'configured' => true,
                'error' => $response['error'],
                'sources' => [],
            ];
        }

        $data = $response['data'];
        $sources = [];
        $totalSessions = 0;
        $totalUsers = 0;
        $totalConversions = 0;

        if (!empty($data['rows'])) {
            foreach ($data['rows'] as $r) {
                $dimVals = array_column($r['dimensionValues'] ?? [], 'value');
                $metVals = array_column($r['metricValues'] ?? [], 'value');
                $sess = (int) ($metVals[0] ?? 0);
                $users = (int) ($metVals[1] ?? 0);
                $conv = (int) ($metVals[2] ?? 0);
                $totalSessions += $sess;
                $totalUsers += $users;
                $totalConversions += $conv;

                $rate = $sess > 0 ? round(($conv / $sess) * 100, 2) . '%' : '0%';

                $sources[] = [
                    'source' => $dimVals[0] ?? '',
                    'medium' => $dimVals[1] ?? '',
                    'campaign' => $dimVals[2] ?? '',
                    'sessions' => $sess,
                    'users' => $users,
                    'conversions' => $conv,
                    'rate' => $rate,
                ];
            }
        }

        $avgRate = $totalSessions > 0 ? round(($totalConversions / $totalSessions) * 100, 2) . '%' : '0%';

        return [
            'configured' => true,
            'summary' => [
                'sessions' => $totalSessions,
                'active_users' => $totalUsers,
                'conversions' => $totalConversions,
                'conversion_rate' => $avgRate,
            ],
            'sources' => $sources,
            'raw' => $data,
        ];
    }

    /**
     * Google Ads API v17: Search campaigns via GAQL (Google Ads Query Language)
     * POST https://googleads.googleapis.com/v17/customers/{customerId}/googleAds:search
     *
     * @param string|null $gaql
     * @return array
     */
    public function search_google_ads_campaigns(?string $gaql = null): array
    {
        $customerId = $this->get_google_ads_customer_id();
        $token = $this->get_access_token();
        $devToken = $this->settings['google_ads_developer_token'] ?? '';

        if (empty($customerId) || empty($token) || empty($devToken)) {
            // Simulated campaign metrics based on Google Ads v17 schema
            return [
                'configured' => false,
                'customer_id' => $customerId ?: '123-456-7890',
                'campaigns' => [
                    [
                        'id' => '1029384756',
                        'name' => 'Randevu Arama - İstanbul',
                        'status' => 'ENABLED',
                        'impressions' => 14200,
                        'clicks' => 840,
                        'cost' => '3250.00 TL',
                        'conversions' => 78,
                        'cpa' => '41.66 TL',
                        'roas' => '4.8x',
                    ],
                    [
                        'id' => '1029384757',
                        'name' => 'Maksimum Performans (PMax) - Güzellik',
                        'status' => 'ENABLED',
                        'impressions' => 28900,
                        'clicks' => 1120,
                        'cost' => '4800.00 TL',
                        'conversions' => 96,
                        'cpa' => '50.00 TL',
                        'roas' => '5.2x',
                    ],
                    [
                        'id' => '1029384758',
                        'name' => 'Marka Arama - BooKi',
                        'status' => 'PAUSED',
                        'impressions' => 3100,
                        'clicks' => 450,
                        'cost' => '850.00 TL',
                        'conversions' => 62,
                        'cpa' => '13.70 TL',
                        'roas' => '9.1x',
                    ],
                ],
                'note' => 'Örnek Google Ads verisi - Canlı Google Ads API erişimi için Müşteri No ve Geliştirici Anahtarını giriniz.',
            ];
        }

        $cleanCustomerId = str_replace('-', '', $customerId);
        $url = "https://googleads.googleapis.com/v17/customers/{$cleanCustomerId}/googleAds:search";

        $query = $gaql ?: "SELECT campaign.id, campaign.name, campaign.status, metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions, metrics.conversions_value FROM campaign WHERE segments.date DURING LAST_30_DAYS ORDER BY metrics.cost_micros DESC LIMIT 50";

        $headers = [
            "Authorization: Bearer {$token}",
            "developer-token: {$devToken}",
        ];

        $response = $this->http_post_json($url, ['query' => $query], $headers);

        if (!$response['success']) {
            return [
                'configured' => true,
                'error' => $response['error'],
                'campaigns' => [],
            ];
        }

        $campaigns = [];
        foreach ($response['data']['results'] ?? [] as $res) {
            $camp = $res['campaign'] ?? [];
            $met = $res['metrics'] ?? [];
            $costMicros = (float) ($met['costMicros'] ?? 0);
            $cost = $costMicros / 1000000;
            $conv = (float) ($met['conversions'] ?? 0);
            $cpa = $conv > 0 ? round($cost / $conv, 2) . ' TL' : '-';
            $val = (float) ($met['conversionsValue'] ?? 0);
            $roas = $cost > 0 ? round($val / $cost, 1) . 'x' : '-';

            $campaigns[] = [
                'id' => $camp['id'] ?? '',
                'name' => $camp['name'] ?? '',
                'status' => $camp['status'] ?? 'UNKNOWN',
                'impressions' => (int) ($met['impressions'] ?? 0),
                'clicks' => (int) ($met['clicks'] ?? 0),
                'cost' => number_format($cost, 2) . ' TL',
                'conversions' => $conv,
                'cpa' => $cpa,
                'roas' => $roas,
            ];
        }

        return [
            'configured' => true,
            'customer_id' => $customerId,
            'campaigns' => $campaigns,
        ];
    }

    /**
     * Google Ads API v17: mutate campaign status (pause or resume)
     *
     * @param string $campaignId
     * @param string $status 'ENABLED' or 'PAUSED'
     * @return array
     */
    public function mutate_campaign_status(string $campaignId, string $status = 'PAUSED'): array
    {
        $customerId = $this->get_google_ads_customer_id();
        $token = $this->get_access_token();
        $devToken = $this->settings['google_ads_developer_token'] ?? '';

        if (empty($customerId) || empty($token) || empty($devToken)) {
            return [
                'success' => true,
                'simulated' => true,
                'campaign_id' => $campaignId,
                'status' => $status,
                'message' => "Google Ads simülasyon modu: Kampanya {$campaignId} durumu '{$status}' olarak güncellendi.",
            ];
        }

        $cleanCustomerId = str_replace('-', '', $customerId);
        $url = "https://googleads.googleapis.com/v17/customers/{$cleanCustomerId}/campaigns:mutate";

        $body = [
            'operations' => [
                [
                    'update' => [
                        'resourceName' => "customers/{$cleanCustomerId}/campaigns/{$campaignId}",
                        'status' => $status,
                    ],
                    'updateMask' => 'status',
                ],
            ],
        ];

        $headers = [
            "Authorization: Bearer {$token}",
            "developer-token: {$devToken}",
        ];

        return $this->http_post_json($url, $body, $headers);
    }

    /**
     * Google Ads API v17: Offline Conversion Upload (uploadClickConversions)
     * Reports customer appointment bookings and revenue back to Google Ads Smart Bidding.
     *
     * POST https://googleads.googleapis.com/v17/customers/{customerId}:uploadClickConversions
     *
     * @param string $gclid
     * @param string $conversionActionId
     * @param float $value
     * @param string $orderId
     * @param string|null $conversionDateTime ISO format e.g. '2026-09-17 14:30:00+03:00'
     * @return array
     */
    public function upload_click_conversion(
        string $gclid,
        string $conversionActionId,
        float $value,
        string $orderId,
        ?string $conversionDateTime = null
    ): array {
        $customerId = $this->get_google_ads_customer_id();
        $token = $this->get_access_token();
        $devToken = $this->settings['google_ads_developer_token'] ?? '';

        $dateTime = $conversionDateTime ?: date('Y-m-d H:i:sP');

        if (empty($customerId) || empty($token) || empty($devToken)) {
            return [
                'success' => true,
                'simulated' => true,
                'gclid' => $gclid,
                'conversion_action' => $conversionActionId,
                'value' => $value,
                'currency' => 'TRY',
                'order_id' => $orderId,
                'conversion_date_time' => $dateTime,
                'message' => "Google Ads Çevrimdışı Dönüşüm kaydedildi (Simülasyon): {$value} TL rezervasyon geliri Google Ads Smart Bidding modeline iletildi.",
            ];
        }

        $cleanCustomerId = str_replace('-', '', $customerId);
        $url = "https://googleads.googleapis.com/v17/customers/{$cleanCustomerId}:uploadClickConversions";

        $body = [
            'conversions' => [
                [
                    'gclid' => $gclid,
                    'conversionAction' => "customers/{$cleanCustomerId}/conversionActions/{$conversionActionId}",
                    'conversionDateTime' => $dateTime,
                    'conversionValue' => $value,
                    'currencyCode' => 'TRY',
                    'orderId' => $orderId,
                ],
            ],
            'partialFailure' => true,
        ];

        $headers = [
            "Authorization: Bearer {$token}",
            "developer-token: {$devToken}",
        ];

        return $this->http_post_json($url, $body, $headers);
    }

    /**
     * BigQuery / Analytics Hub streaming payload export formatter.
     * Generates standard BigQuery Analytics Hub data exchange schemas for enterprise data warehouses.
     */
    public function format_bigquery_export(array $appointment, array $attribution): array
    {
        return [
            'event_timestamp' => time(),
            'event_name' => 'appointment_booked',
            'tenant_id' => tenant_context()['id'] ?? 0,
            'tenant_subdomain' => tenant_context()['subdomain'] ?? '',
            'appointment_id' => $appointment['id'] ?? 0,
            'customer_id' => $appointment['id_users_customer'] ?? 0,
            'service_id' => $appointment['id_services'] ?? 0,
            'provider_id' => $appointment['id_users_provider'] ?? 0,
            'start_datetime' => $appointment['start_datetime'] ?? '',
            'end_datetime' => $appointment['end_datetime'] ?? '',
            'price' => (float) ($appointment['price'] ?? 0),
            'currency' => 'TRY',
            'attribution' => [
                'session_id' => $attribution['session_id'] ?? '',
                'utm_source' => $attribution['utm_source'] ?? '',
                'utm_medium' => $attribution['utm_medium'] ?? '',
                'utm_campaign' => $attribution['utm_campaign'] ?? '',
                'gclid' => $attribution['gclid'] ?? '',
                'fbclid' => $attribution['fbclid'] ?? '',
                'device_type' => $attribution['device_type'] ?? '',
                'browser' => $attribution['browser'] ?? '',
                'clicked_at' => $attribution['clicked_at'] ?? '',
            ],
        ];
    }

    /**
     * Extract property ID from Google Analytics ID setting.
     * Handles both GA4 property ID (e.g. "987654321") or Measurement ID ("G-XXXXXX").
     */
    protected function get_property_id(): string
    {
        $id = trim($this->settings['google_analytics_id'] ?? '');
        // If property ID stored as numbers only
        if (ctype_digit($id)) {
            return $id;
        }
        // If stored as property/123456
        if (preg_match('/(?:properties\/)?(\d+)/', $id, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * Get Google Ads clean Customer ID.
     */
    protected function get_google_ads_customer_id(): string
    {
        return trim($this->settings['google_ads_id'] ?? '');
    }

    /**
     * Fetch OAuth2 access token or return empty.
     */
    protected function get_access_token(): string
    {
        return trim($this->settings['google_analytics_token'] ?? $this->settings['google_ads_token'] ?? '');
    }

    /**
     * Internal JSON POST helper.
     */
    protected function http_post_json(string $url, array $data, array $headers = []): array
    {
        $ch = curl_init($url);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => "cURL error: {$curlError}", 'http_code' => $httpCode];
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decoded, 'http_code' => $httpCode];
        }

        $errMsg = $decoded['error']['message'] ?? $decoded['message'] ?? "HTTP {$httpCode}";
        return ['success' => false, 'error' => $errMsg, 'data' => $decoded, 'http_code' => $httpCode];
    }
}
