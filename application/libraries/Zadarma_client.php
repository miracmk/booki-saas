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
        if ($k === '') { $k = (string)(master_setting('zadarma_api_key') ?: 'ceba11321113fd2628a1'); }
        return trim($k);
    }
    public function api_secret(): string {
        $s = getenv('ZADARMA_API_SECRET') ?: '';
        if ($s === '') { $s = (string)(master_setting('zadarma_api_secret') ?: '7fc7705128bd1c8ef754'); }
        return trim($s);
    }
    public function sip_login(): string {
        $l = getenv('ZADARMA_SIP_LOGIN') ?: '';
        if ($l === '') { $l = (string)(master_setting('zadarma_sip_login') ?: ''); }
        return trim($l);
    }
    /**
     * Callback 'from' icin PBX dahili numarasi.
     * Resmi dokuman: "from – your phone/SIP number, the PBX extension or the PBX scenario".
     * '325384-100' formatindaki login -> dahili '100' dondurur; duz SIP numarasi ise aynen doner.
     */
    public function pbx_extension(): string {
        $login = $this->sip_login();
        if ($login === '') { return ''; }
        if (preg_match('/^(\d+)-(\d+)$/', $login, $m)) { return $m[2]; }
        return preg_replace('/[^0-9]/', '', $login) ?? '';
    }
    /**
     * Callback 'to' formati: dokuman orneklerinde + isaretsiz uluslararasi hane dizisi
     * (orn: 905062505562, 442037691881). normalize_phone sonucundaki '+' atilir.
     */
    public function e164_digits(string $raw): string {
        $n = $this->normalize_phone($raw);
        return preg_replace('/[^0-9]/', '', $n) ?? '';
    }

    /**
     * Zadarma PBX / Outbound dial formatı:
     * Zadarma santralinde '90' ülke kodu ön eki tanımlı olduğunda, '00' olmadan
     * gönderilen numaralar yerel kabul edilerek '90' ön eki tekrar eklenir (9090...).
     * '00' ile başlayan numaralar uluslararası çıkış kodu sayılır ve mükerrer 90 engellenir.
     */
    public function format_dial_digits(string $raw): string {
        $trimmed = trim($raw);
        if ($trimmed === '') return '';
        // Kısa dahili (örn: 100, 101)
        if (preg_match('/^\d{1,5}$/', $trimmed)) {
            return $trimmed;
        }
        $digits = preg_replace('/[^0-9]/', '', $trimmed) ?? '';
        if ($digits === '') return '';
        if (str_starts_with($digits, '00')) {
            return $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '0090' . substr($digits, 1);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '0090' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '90')) {
            return '00' . $digits;
        }
        return '00' . $digits;
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
        // Resmi SDK (zadarma/user-api-v1 lib/Client.php) ile birebir ayni:
        // ksort -> http_build_query(RFC1738) -> base64(hash_hmac('sha1', method+query+md5(query), secret))
        ksort($params);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        $md5 = md5($query);
        $data = $method . $query . $md5;
        return base64_encode(hash_hmac('sha1', $data, $this->api_secret()));
    }
    public function call(string $method, array $params = [], string $http = 'GET'): array {
        $key = $this->api_key();
        $params['format'] = 'json'; // Resmi SDK her istige format parametresi ekler (imzaya dahil)
        $sig = $this->signature($method, $params);
        $url = self::API_BASE . $method;
        $headers = ['Authorization: ' . $key . ':' . $sig];
        $ch = curl_init();
        $http_upper = strtoupper($http);
        if ($http_upper === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } elseif ($http_upper === 'PUT') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } elseif ($http_upper === 'DELETE') {
            $qs = http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url . ($qs !== '' ? '?' . $qs : ''));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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
    /**
     * GET /v1/request/callback/ (resmi dokuman metodu 'get' olarak belirtilir).
     * from: PBX dahili (orn 100) veya SIP numarasi - Zadarma once BU cihazi calar.
     * to:   hedef numara, +'siz uluslararasi hane dizisi (orn 905062505562).
     * sip:  opsiyonel - giden bacakta CallerID olarak kullanilacak SIP/dahili (orn 100).
     * GET 400 donerse (bazi hesaplar POST kabul eder) tek kez POST ile denenir.
     */
    public function request_callback(string $from, string $to, ?string $sip = null): array {
        $from_formatted = $this->format_dial_digits($from);
        $to_formatted = $this->format_dial_digits($to);
        $p = ['from' => $from_formatted, 'to' => $to_formatted];
        if ($sip !== null && $sip !== '') { $p['sip'] = $sip; }
        $res = $this->call('/v1/request/callback/', $p, 'GET');
        if (!$res['ok'] && (int) $res['http_code'] === 400) {
            log_message('debug', 'Zadarma callback GET 400, POST fallback deneniyor');
            $res = $this->call('/v1/request/callback/', $p, 'POST');
        }
        return $res;
    }
    /**
     * WebRTC webphone widget key (GET /v1/webrtc/get_key/).
     * Anahtar 72 saat gecerlidir; sip parametresi login veya dahili kabul eder.
     */
    public function webrtc_key(string $sip): array {
        return $this->call('/v1/webrtc/get_key/', ['sip' => $sip], 'GET');
    }

    /**
     * WebRTC widget entegrasyon bilgisi (GET /v1/webrtc/).
     * is_exists (bool), domains (array), settings (shape, position) doner.
     */
    public function webrtc_info(): array {
        return $this->call('/v1/webrtc/', [], 'GET');
    }

    /**
     * WebRTC widget entegrasyonu olusturma (POST /v1/webrtc/create/).
     */
    public function webrtc_create(string $domain): array {
        return $this->call('/v1/webrtc/create/', ['domain' => $domain], 'POST');
    }

    /**
     * WebRTC widget'a yeni domain ekleme (POST /v1/webrtc/domain/).
     */
    public function webrtc_add_domain(string $domain): array {
        return $this->call('/v1/webrtc/domain/', ['domain' => $domain], 'POST');
    }

    /**
     * WebRTC widget'tan domain silme (DELETE /v1/webrtc/domain/).
     */
    public function webrtc_delete_domain(string $domain): array {
        return $this->call('/v1/webrtc/domain/', ['domain' => $domain], 'DELETE');
    }

    /**
     * WebRTC widget ayarlarini guncelleme (PUT /v1/webrtc/).
     * shape: 'square' | 'rounded'
     * position: 'top_left' | 'top_right' | 'bottom_right' | 'bottom_left'
     */
    public function webrtc_update_settings(string $shape = 'square', string $position = 'bottom_right'): array {
        return $this->call('/v1/webrtc/', ['shape' => $shape, 'position' => $position], 'PUT');
    }

    /**
     * WebRTC widget entegrasyonunu tamamen silme (DELETE /v1/webrtc/).
     */
    public function webrtc_delete(): array {
        return $this->call('/v1/webrtc/', [], 'DELETE');
    }

    public function balance(): array { return $this->call('/v1/info/balance/', [], 'GET'); }
}
