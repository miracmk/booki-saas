<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| App Configuration
|--------------------------------------------------------------------------
|
| Declare some of the global config values of BooKi.
|
*/

$config['version'] = '1.6.0'; // This must be changed manually.

$config['url'] = Config::BASE_URL;

$config['debug'] = Config::DEBUG_MODE;

// Salon Flora customization - the stock token ('TSJ83') never changes, so with DEBUG_MODE=FALSE (which makes
// asset_url() append '?cache_busting_token' and rewrite .js -> .min.js) browsers kept serving stale cached JS
// after every deploy, no matter how many times the Cloudflare cache was purged - a hard refresh was required to
// see any JS change. ASSET_VERSION is set in docker-compose.yml's environment block and MUST be bumped (or the
$config['cache_busting_token'] = getenv('ASSET_VERSION') ?: ('SF_CAL_' . (file_exists(FCPATH . 'assets/css/ki-command-center.min.css') ? filemtime(FCPATH . 'assets/css/ki-command-center.min.css') : time()));
