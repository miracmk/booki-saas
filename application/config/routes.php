<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes with
| underscores in the controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

require_once __DIR__ . '/../helpers/routes_helper.php';

$route['default_controller'] = 'booking';

// BooKi (2026-08-26) - multi-tenant SaaS: the bare app domain (bookiapp.kibusiness.co,
// no tenant subdomain) has no booking page of its own - it's the "which company are you with?"
// portal instead. See Portal.php / EA_Controller::resolve_tenant()'s bare-host exception.
$portal_host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));

if ($portal_host !== '' && $portal_host === (getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co')) {
    $route['default_controller'] = 'portal';
    $route['portal'] = 'portal/index';
    $route['portal/(:any)'] = 'portal/$1';
    $route['login'] = 'portal/index';
    $route['find_tenant'] = 'portal/find_tenant';
    $route['meta/webhook'] = 'meta/webhook';
    $route['meta/oauth_callback'] = 'meta/oauth_callback';
    $route['whatsapp/webhook'] = 'whatsapp/webhook';
    $route['instagram/webhook'] = 'instagram/webhook';
    $route['instagram/oauth_callback'] = 'meta/oauth_callback';
}

$route['meta/webhook'] = 'meta/webhook';
$route['meta/oauth_callback'] = 'meta/oauth_callback';
$route['instagram/oauth_callback'] = 'meta/oauth_callback';

// BooKi (2026-08-26) - SaaS admin panel: admin-bookiapp.kibusiness.co has no booking page
// either - it's the super-admin login/dashboard. See EA_Controller::resolve_tenant()'s superadmin
// host exception (stays on the master DB for the whole "Superadmin*" controller family).
if ($portal_host !== '' && $portal_host === (getenv('SUPERADMIN_DOMAIN') ?: 'admin-bookiapp.kibusiness.co')) {
    $route['default_controller'] = 'superadmin_auth';
}

// BooKi (2026-09-16) - public marketing site on the marketplace host (booki.kibusiness.co).
// The root of this host serves the Landing page; the discovery portal stays reachable at
// /marketplace. See EA_Controller::resolve_tenant()'s marketplace host exception.
$marketplace_domain = strtolower((string) (getenv('MARKETPLACE_DOMAIN') ?: 'booki.kibusiness.co'));
$randevuburada_domain = strtolower((string) (getenv('RANDEVUBURADA_DOMAIN') ?: 'randevuburada.kibusiness.co'));

if ($portal_host !== '' && $portal_host === $marketplace_domain) {
    $route['default_controller'] = 'landing';
    $route['privacy'] = 'landing/privacy';
    $route['terms'] = 'landing/terms';
    $route['about'] = 'landing/about';
    $route['hakkimizda'] = 'landing/about';
    $route['mesafeli-satis'] = 'landing/mesafeli_satis';
    $route['mesafeli-satis-sozlesmesi'] = 'landing/mesafeli_satis';
    $route['teslimat-iade'] = 'landing/teslimat_iade';
    $route['iade-teslimat'] = 'landing/teslimat_iade';
    $route['marketplace'] = 'marketplace/index';
    $route['marketplace/business/(:any)'] = 'marketplace/business/$1';
    $route['marketplace/services_preview/(:any)'] = 'marketplace/services_preview/$1';
    $route['marketplace/get_slots/(:any)'] = 'marketplace/get_slots/$1';
    $route['marketplace/create_booking/(:any)'] = 'marketplace/create_booking/$1';
    $route['marketplace/isletme/contact'] = 'marketplace/contact_request';
    $route['marketplace/isletme/(:any)'] = 'marketplace/isletme/$1';
    $route['marketplace/kategori/(:any)/(:any)/(:any)'] = 'marketplace/category/$1/$2/$3';
    $route['marketplace/kategori/(:any)/(:any)'] = 'marketplace/category/$1/$2';
    $route['marketplace/kategori/(:any)'] = 'marketplace/category/$1';
    $route['marketplace/sahiplen/(:any)'] = 'marketplace/claim/$1';
    $route['sitemap.xml'] = 'marketplace/sitemap';
    $route['sitemap-places-(:num).xml'] = 'marketplace/sitemap_places/$1';
    $route['api/places/photo'] = 'places_photo/index';
    $route['robots.txt'] = 'marketplace/robots';
    $route['llms.txt'] = 'marketplace/llms';
}

// RandevuBurada (2026-09-20) - dedicated standalone marketplace subdomain (randevuburada.kibusiness.co).
// The root of this host serves the marketplace directly!
if ($portal_host !== '' && $portal_host === $randevuburada_domain) {
    $route['default_controller'] = 'marketplace';
    $route['business/(:any)'] = 'marketplace/business/$1';
    $route['services_preview/(:any)'] = 'marketplace/services_preview/$1';
    $route['get_slots/(:any)'] = 'marketplace/get_slots/$1';
    $route['create_booking/(:any)'] = 'marketplace/create_booking/$1';
    $route['isletme/contact'] = 'marketplace/contact_request';
    $route['isletme/(:any)'] = 'marketplace/isletme/$1';
    $route['kategori/(:any)/(:any)/(:any)'] = 'marketplace/category/$1/$2/$3';
    $route['kategori/(:any)/(:any)'] = 'marketplace/category/$1/$2';
    $route['kategori/(:any)'] = 'marketplace/category/$1';
    $route['sahiplen/(:any)'] = 'marketplace/claim/$1';
    $route['marketplace'] = 'marketplace/index';
    $route['marketplace/business/(:any)'] = 'marketplace/business/$1';
    $route['marketplace/services_preview/(:any)'] = 'marketplace/services_preview/$1';
    $route['marketplace/get_slots/(:any)'] = 'marketplace/get_slots/$1';
    $route['marketplace/create_booking/(:any)'] = 'marketplace/create_booking/$1';
    $route['marketplace/isletme/contact'] = 'marketplace/contact_request';
    $route['marketplace/isletme/(:any)'] = 'marketplace/isletme/$1';
    $route['marketplace/kategori/(:any)/(:any)/(:any)'] = 'marketplace/category/$1/$2/$3';
    $route['marketplace/kategori/(:any)/(:any)'] = 'marketplace/category/$1/$2';
    $route['marketplace/kategori/(:any)'] = 'marketplace/category/$1';
    $route['marketplace/sahiplen/(:any)'] = 'marketplace/claim/$1';
    $route['sitemap.xml'] = 'marketplace/sitemap';
    $route['sitemap-places-(:num).xml'] = 'marketplace/sitemap_places/$1';
    $route['api/places/photo'] = 'places_photo/index';
    $route['robots.txt'] = 'marketplace/robots';
    $route['llms.txt'] = 'marketplace/llms';
    $route['privacy'] = 'landing/privacy';
    $route['terms'] = 'landing/terms';
    $route['about'] = 'landing/about';
    $route['hakkimizda'] = 'landing/about';
    $route['mesafeli-satis'] = 'landing/mesafeli_satis';
    $route['mesafeli-satis-sozlesmesi'] = 'landing/mesafeli_satis';
    $route['teslimat-iade'] = 'landing/teslimat_iade';
    $route['iade-teslimat'] = 'landing/teslimat_iade';
}

// Global legal, sitemap, robots, photo proxy and marketplace routes
$route['privacy'] = 'landing/privacy';
$route['terms'] = 'landing/terms';
$route['about'] = 'landing/about';
$route['hakkimizda'] = 'landing/about';
$route['mesafeli-satis'] = 'landing/mesafeli_satis';
$route['mesafeli-satis-sozlesmesi'] = 'landing/mesafeli_satis';
$route['teslimat-iade'] = 'landing/teslimat_iade';
$route['iade-teslimat'] = 'landing/teslimat_iade';
$route['sitemap.xml'] = 'marketplace/sitemap';
$route['sitemap-places-(:num).xml'] = 'marketplace/sitemap_places/$1';
$route['robots.txt'] = 'marketplace/robots';
$route['llms.txt'] = 'marketplace/llms';
$route['api/places/photo'] = 'places_photo/index';
$route['marketplace'] = 'marketplace/index';
$route['marketplace/business/(:any)'] = 'marketplace/business/$1';
$route['marketplace/services_preview/(:any)'] = 'marketplace/services_preview/$1';
$route['marketplace/get_slots/(:any)'] = 'marketplace/get_slots/$1';
$route['marketplace/create_booking/(:any)'] = 'marketplace/create_booking/$1';
$route['isletme/contact'] = 'marketplace/contact_request';
$route['isletme/(:any)'] = 'marketplace/isletme/$1';
$route['kategori/(:any)/(:any)/(:any)'] = 'marketplace/category/$1/$2/$3';
$route['kategori/(:any)/(:any)'] = 'marketplace/category/$1/$2';
$route['kategori/(:any)'] = 'marketplace/category/$1';
$route['sahiplen/(:any)'] = 'marketplace/claim/$1';
$route['marketplace/isletme/contact'] = 'marketplace/contact_request';
$route['marketplace/isletme/(:any)'] = 'marketplace/isletme/$1';
$route['marketplace/kategori/(:any)/(:any)/(:any)'] = 'marketplace/category/$1/$2/$3';
$route['marketplace/kategori/(:any)/(:any)'] = 'marketplace/category/$1/$2';
$route['marketplace/kategori/(:any)'] = 'marketplace/category/$1';
$route['marketplace/sahiplen/(:any)'] = 'marketplace/claim/$1';

// Customer Onboarding Routes
$route['onboarding/(:any)'] = 'customer_onboarding/index/$1';
$route['customer_onboarding'] = 'customer_onboarding/index';
$route['customer_onboarding/(:any)'] = 'customer_onboarding/$1';

// Super Admin Aliases
$route['superadmin'] = 'superadmin_tenants/index';
$route['superadmin_crm'] = 'superadmin_tenants/index';

$route['404_override'] = '';

$route['translate_uri_dashes'] = false;

/*
| -------------------------------------------------------------------------
| FRAME OPTIONS HEADERS
| -------------------------------------------------------------------------
| Set the appropriate headers so that iframe control and permissions are 
| properly configured.
|
| This prevents clickjacking attacks by disabling embedding in iframes.
|
| Options:
|
|   - DENY 
|   - SAMEORIGIN 
|
*/

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // NOTE: keep in sync with application/hooks/security_headers.php.
    // microphone/camera must stay `(self)` — `()` breaks getUserMedia()
    // on the superadmin voice-call modal (Chrome: "explicitly disallowed").
    header('Permissions-Policy: geolocation=(), microphone=(self), camera=(self)');
}

/*
| -------------------------------------------------------------------------
| CORS HEADERS
| -------------------------------------------------------------------------
| Set the appropriate headers so that CORS requirements are met and any 
| incoming preflight options request succeeds. 
|
| IMPORTANT: For production, restrict this to your specific trusted domains.
|
*/

// Get allowed origins from configuration or use a whitelist
$allowed_origins = (defined('CORS_ALLOWED_ORIGINS') && trim(CORS_ALLOWED_ORIGINS) !== '')
    ? array_map('trim', explode(',', CORS_ALLOWED_ORIGINS))
    : [];
$request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Only allow CORS when origins are explicitly whitelisted and origin matches
if (!empty($request_origin) && !empty($allowed_origins) && in_array($request_origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $request_origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

if (
    isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) &&
    !empty($request_origin) &&
    !empty($allowed_origins) &&
    in_array($request_origin, $allowed_origins, true)
) {
    // May also be using PUT, PATCH, HEAD etc
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF, X-CSRF-Token');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

if (
    isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) &&
    !empty($request_origin) &&
    !empty($allowed_origins) &&
    in_array($request_origin, $allowed_origins, true)
) {
    // Only allow safe headers
    $allowed_headers = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin', 'X-CSRF'];
    $requested_headers = array_map('trim', explode(',', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']));
    $safe_headers = array_filter($requested_headers, function ($h) use ($allowed_headers) {
        return in_array(trim($h), $allowed_headers, true);
    });
    if (!empty($safe_headers)) {
        header('Access-Control-Allow-Headers: ' . implode(', ', $safe_headers));
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/*
| -------------------------------------------------------------------------
| REST API ROUTING
| -------------------------------------------------------------------------
| Define the API resource routes using the routing helper function. By 
| default, each resource will have by default the following actions: 
| 
|   - index [GET]
|
|   - show/:id [GET]
|
|   - store [POST]
|
|   - update [PUT]
|
|   - destroy [DELETE]
|
| Some resources like the availabilities and the settings do not follow this 
| pattern and are explicitly defined. 
|
*/

route_api_resource($route, 'appointments', 'api/v1/');

route_api_resource($route, 'admins', 'api/v1/');

route_api_resource($route, 'service_categories', 'api/v1/');

route_api_resource($route, 'customers', 'api/v1/');

route_api_resource($route, 'providers', 'api/v1/');

route_api_resource($route, 'secretaries', 'api/v1/');

route_api_resource($route, 'services', 'api/v1/');

route_api_resource($route, 'unavailabilities', 'api/v1/');

route_api_resource($route, 'webhooks', 'api/v1/');

route_api_resource($route, 'blocked_periods', 'api/v1/');

route_api_resource($route, 'stations', 'api/v1/');

$route['api/v1/operations/live']['get'] = 'api/v1/operations_api_v1/live';
$route['api/v1/operations/status']['post'] = 'api/v1/operations_api_v1/update_status';
$route['api/v1/operations/checkin']['post'] = 'api/v1/operations_api_v1/checkin';
$route['api/v1/operations/floor-plan']['get'] = 'api/v1/operations_api_v1/floor_plan';
$route['api/v1/operations/validate-conflict']['post'] = 'api/v1/operations_api_v1/validate_conflict';
$route['api/v1/customers/(:num)/mini-crm']['get'] = 'api/v1/customers_api_v1/mini_crm/$1';

$route['api/v1/settings']['get'] = 'api/v1/settings_api_v1/index';

$route['api/v1/settings/(:any)']['get'] = 'api/v1/settings_api_v1/show/$1';

$route['api/v1/settings/(:any)']['put'] = 'api/v1/settings_api_v1/update/$1';

$route['api/v1/availabilities']['get'] = 'api/v1/availabilities_api_v1/get';

// BooKi Mobile Auth & Multi-Tenant API
$route['api/v1/auth/login']['post'] = 'api/v1/auth_api_v1/login';
$route['api/v1/auth/register']['post'] = 'api/v1/auth_api_v1/register';
$route['api/v1/auth/me']['get'] = 'api/v1/auth_api_v1/me';
$route['api/v1/auth/tenants']['get'] = 'api/v1/auth_api_v1/tenants';

/*
| -------------------------------------------------------------------------
| AGENT API ROUTING (server-to-server customer-representative agents)
| -------------------------------------------------------------------------
| Bearer-token secured JSON endpoints (per-tenant `agent_api_key` setting) used
| by the MCP server for ElevenLabs / conversational voice-text agents. See
| application/controllers/Agent_api.php and docs/AGENT_MCP.md.
|
*/

$route['agent/v1/business']['get'] = 'agent_api/business';

$route['agent/v1/services']['get'] = 'agent_api/services';

$route['agent/v1/providers']['get'] = 'agent_api/providers';

$route['agent/v1/availability']['get'] = 'agent_api/availability';

$route['agent/v1/customers/lookup']['post'] = 'agent_api/customer_lookup';

$route['agent/v1/customers/(:num)/appointments']['get'] = 'agent_api/customer_appointments/$1';

$route['agent/v1/appointments']['post'] = 'agent_api/create_appointment';

$route['agent/v1/appointments/(:num)/cancel']['post'] = 'agent_api/cancel_appointment/$1';

$route['agent/v1/appointments/(:num)/reschedule']['post'] = 'agent_api/reschedule_appointment/$1';

/*
| -------------------------------------------------------------------------
| CUSTOM ROUTING
| -------------------------------------------------------------------------
| You can add custom routes to the following section to define URL patterns
| that are later mapped to the available controllers in the filesystem.
|
*/

// BooKi (2026-08-28) - observability: health check endpoints
$route['health'] = 'health/index';
$route['health/deep'] = 'health/deep';

// BooKi (2026-09-17) - short review links sent over SMS/WhatsApp (see Review::short($code)).
$route['r/(:any)'] = 'review/short/$1';

// BooKi (2026-09-17) - public marketing landing pages and ad attribution routes
$route['p/(:any)'] = 'landing_page/view/$1';
$route['landing_page/(:any)'] = 'landing_page/view/$1';
$route['landing_page/view/(:any)'] = 'landing_page/view/$1';
$route['marketing/track_visit'] = 'track/visit';
$route['marketing/track_interaction'] = 'track/interaction';
$route['track/visit'] = 'track/visit';
$route['track/interaction'] = 'track/interaction';

// BooKi (2026-09-18) - World-Class SaaS Transformation Routes
$route['adisyons'] = 'adisyons/index';
$route['adisyons/(:any)'] = 'adisyons/$1';
$route['restaurant'] = 'restaurant/index';
$route['restaurant/(:any)'] = 'restaurant/$1';
$route['checkin'] = 'checkin/index';
$route['checkin/(:any)'] = 'checkin/$1';
$route['finance'] = 'finance/index';
$route['finance/(:any)'] = 'finance/$1';
$route['expenses'] = 'expenses/index';
$route['expenses/(:any)'] = 'expenses/$1';
$route['search'] = 'search/index';
$route['search/(:any)'] = 'search/$1';
$route['customer/portal'] = 'customer_portal/index';
$route['customer/portal/(:any)'] = 'customer_portal/$1';
if ($portal_host !== (getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co')) {
    $route['portal'] = 'customer_portal/index';
    $route['portal/(:any)'] = 'customer_portal/$1';
}

// BooKi (2026-09-18) - Industry Blueprints & Onboarding Wizard
$route['onboarding'] = 'onboarding/index';
$route['onboarding/(:any)'] = 'onboarding/$1';

// CLI console
$route['console'] = 'console/index';
$route['console/(:any)'] = 'console/$1';
$route['console/(:any)/(:any)'] = 'console/$1/$2';
$route['console/(:any)/(:any)/(:any)'] = 'console/$1/$2/$3';

/* End of file routes.php */
/* Location: ./application/config/routes.php */
