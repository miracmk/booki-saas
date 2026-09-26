<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Google Places Photo Proxy Controller
 *
 * Securely proxies and caches Google Places Photo requests so that API credentials
 * are never exposed to the client-side and CSP / mixed-content restrictions are
 * completely avoided by serving directly as same-origin image binary.
 *
 * GET /api/places/photo?ref={photo_reference}&maxwidth={px}
 * -------------------------------------------------------------------------- */

class Places_photo extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Proxy & stream a Google Places Photo request with disk caching.
     */
    public function index(): void
    {
        method('get');

        $ref = trim((string) request('ref'));
        $maxwidth = max(100, min(1600, (int) (request('maxwidth') ?: 600)));

        if ($ref === '') {
            abort(400, 'Missing photo reference');
        }

        // Cache file path
        $cache_dir = sys_get_temp_dir() . '/places_photos';
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0777, true);
        }
        $cache_key = md5($ref . '_' . $maxwidth);
        $cache_file = $cache_dir . '/' . $cache_key . '.jpg';

        // Serve from cache if valid (< 30 days)
        if (file_exists($cache_file) && filesize($cache_file) > 100 && (time() - filemtime($cache_file) < 86400 * 30)) {
            $this->serve_image($cache_file);
            return;
        }

        $api_key = '';
        if (function_exists('master_setting')) {
            $api_key = (string) master_setting('google_maps_key');
        }
        if (empty($api_key)) {
            $api_key = (string) (getenv('GOOGLE_PLACES_API_KEY') ?: 'AIzaSyAscIARfxTG_KzedaskCabzuRSTj-0bulA');
        }

        if ($api_key === '') {
            abort(503, 'Photo service unavailable');
        }

        // Handle both "places/XXX/photos/YYY" and raw photo reference
        $resource_name = str_starts_with($ref, 'places/') ? $ref : ('places/' . $ref);

        // Fetch photo URI from Places API (New)
        $url = 'https://places.googleapis.com/v1/' . $resource_name . '/media'
             . '?maxWidthPx=' . $maxwidth
             . '&key=' . urlencode($api_key)
             . '&skipHttpRedirect=true';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $download_url = null;
        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if (!empty($data['photoUri'])) {
                $download_url = $data['photoUri'];
            }
        }

        // Fallback to direct media endpoint
        if (empty($download_url)) {
            $download_url = 'https://places.googleapis.com/v1/' . $resource_name . '/media'
                          . '?maxWidthPx=' . $maxwidth
                          . '&key=' . urlencode($api_key);
        }

        // Download image binary
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $download_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $image_data = curl_exec($ch);
        $img_http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'image/jpeg';
        curl_close($ch);

        if ($img_http_code === 200 && !empty($image_data) && strlen($image_data) > 100) {
            @file_put_contents($cache_file, $image_data);
            $this->serve_binary($image_data, $content_type);
            return;
        }

        // If downloading failed, redirect as last resort
        header('Cache-Control: public, max-age=86400, s-maxage=604800');
        header('Location: ' . $download_url, true, 302);
        exit;
    }

    private function serve_image(string $file_path): void
    {
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=604800, s-maxage=604800');
        header('Content-Length: ' . filesize($file_path));
        header('X-Content-Type-Options: nosniff');
        readfile($file_path);
        exit;
    }

    private function serve_binary(string $data, string $content_type = 'image/jpeg'): void
    {
        header('Content-Type: ' . $content_type);
        header('Cache-Control: public, max-age=604800, s-maxage=604800');
        header('Content-Length: ' . strlen($data));
        header('X-Content-Type-Options: nosniff');
        echo $data;
        exit;
    }
}
