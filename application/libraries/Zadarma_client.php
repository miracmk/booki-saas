<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Zadarma API v1 client (HMAC-SHA1 auth + callback call).
 * Spec: https://zadarma.com/en/support/api/
 */
class Zadarma_client
{
    private const API_BASE = 'https://api.zadarma.com';
    protected $CI;
    public function __construct() { $this->CI = &get_instance(); }
    public function api_key(): string {
        $k = getenv('ZADARMA_API_KEY') ?: '';
        if ($k === '') { $k = (string)(master_setting('zadarma_api_key') ?: 'a79256819256392e2336'); }
        return trim($k);
    }
    public function api_secret(): string {
        $s = getenv('ZADARMA_API_SECRET') ?: '';
        if ($s === '') { $s = (string)(master_setting('zadarma_api_secret') ?: 'd52e13a9f59d0a68851b'); }
        return trim($s);
    }
    public function sip_login(): string {
        $l = getenv('ZADARMA_SIP_LOGIN') ?: '';
        if ($l === '') { $l = (string)(master_setting('zadarma_sip_login') ?: ''); }
        return trim($l);
    }
    public function normalize_phone(string $raw): string {
        $p = trim($raw);
        $plus = str_starts_with($p, '+');
        $p = preg_replace('/[^0-9]/', '', $p) ?? '';
        if ($p === '') return '';
        if ($plus) return '+' . $p;
        if (str_starts_with($p, '00')) return '+' . substr($p, 2);
        if (str_starts_with($p, '0') && strlen($p) === 11) return '+90' . substr($p, 1);
        if (strlen($p) === 10 && str_starts_with($p, '5')) return '+90' . $p;
        return $p;
    }
    public function is_configured(): bool {
        return $this->api_key() !== '' && $this->api_secret() !== '' && $this->sip_login() !== '';
    }
    public function signature(string $method, array $params): string {
        ksort($params);
        $query = http_build_query($params);
        $md5 = md5($query);
        $data = $method . $query . $md5;
        $hex = hash_hmac('sha1', $data, $this->api_secret());
        return base64_encode(pack('H*', $hex));
    }
    public function call(string $method, array $params = [], string $http = 'GET'): array {
        $key = $this->api_key();
        $sig = $this->signature($method, $params);
        $url = self::API_BASE . $method;
        $headers = ['Authorization: ' . $key . ':' . $sig];
        $ch = curl_init();
        if (strtoupper($http) === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $qs = http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url . ($qs !== '' ? '?' . $qs : ''));
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch); $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno !== 0) {
            log_message('error', 'Zadarma_client curl error: ' . $err);
            return ['ok' => false, 'http_code' => 0, 'body' => [], 'error' => 'Zadarma ulasilamadi: ' . $err];
        }
        $dec = json_decode((string)$raw, true);
        $body = is_array($dec) ? $dec : (string)$raw;
        $ok = $code >= 200 && $code < 300;
        if (is_array($body) && isset($body['status']) && $body['status'] !== 'success') { $ok = false; }
        if (!$ok) { log_message('error', 'Zadarma_client fail ' . $method . ' http=' . $code . ' raw=' . substr((string)$raw,0,500)); }
        $error = '';
        if (!$ok) { $error = is_array($body) ? ($body['message'] ?? ('Zadarma hatasi HTTP ' . $code)) : ('Zadarma hatasi HTTP ' . $code); }
        return ['ok' => $ok, 'http_code' => $code, 'body' => $body, 'error' => (string)$error];
    }
    public function request_callback(string $from, string $to, ?string $sip = null): array {
        $p = ['from' => $from, 'to' => $to];
        if ($sip !== null && $sip !== '') { $p['sip'] = $sip; }
        return $this->call('/v1/request/callback/', $p, 'POST');
    }
    public function balance(): array { return $this->call('/v1/info/balance/', [], 'GET'); }
}
