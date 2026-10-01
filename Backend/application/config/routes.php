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
// portal instead. See Portal.php / App_Controller::resolve_tenant()'s bare-host exception.
$portal_host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));

if ($portal_host !== '' && ($portal_host === (getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co') || in_array($portal_host, ['bookiapp.kibusiness.co', 'demobookiapp.kibusiness.co', 'devbookiapp.kibusiness.co', 'betabookiapp.kibusiness.co'], true))) {
    $route['default_controller'] = 'portal';
    $route['portal'] = 'portal/index';
    $route['portal/(:any)'] = 'portal/$1';
    $route['login'] = 'portal/index';
    $route['find_tenant'] = 'portal/find_tenant';
    $route['meta/connect/(:any)'] = 'meta/connect/$1';
    $route['meta/disconnect/(:any)'] = 'meta/disconnect/$1';
    $route['meta/oauth/(:any)'] = 'meta/oauth/$1';
    $route['meta/oauth'] = 'meta/oauth';
    $route['meta/webhook'] = 'meta/webhook';
    $route['meta/oauth_callback'] = 'meta/oauth_callback';
    $route['meta/scopes'] = 'meta/scopes';
    $route['whatsapp/webhook'] = 'whatsapp/webhook';
    $route['instagram/webhook'] = 'instagram/webhook';
    $route['instagram/oauth_callback'] = 'meta/oauth_callback';
    $route['google/oauth_callback'] = 'google/oauth_callback';
    $route['google_integrations/oauth_callback'] = 'google_integrations/oauth_callback';
}

$route['meta/connect/(:any)'] = 'meta/connect/$1';
$route['meta/disconnect/(:any)'] = 'meta/disconnect/$1';
$route['meta/oauth/(:any)'] = 'meta/oauth/$1';
$route['meta/oauth'] = 'meta/oauth';
$route['meta/webhook'] = 'meta/webhook';
$route['meta/oauth_callback'] = 'meta/oauth_callback';
$route['meta/scopes'] = 'meta/scopes';
$route['instagram/oauth_callback'] = 'meta/oauth_callback';
$route['google/oauth_callback'] = 'google/oauth_callback';
$route['google_integrations/oauth_callback'] = 'google_integrations/oauth_callback';

// BooKi (2026-08-26) - SaaS admin panel: admin-bookiapp.kibusiness.co has no booking page
// either - it's the super-admin login/dashboard. See App_Controller::resolve_tenant()'s superadmin
// host exception (stays on the master DB for the whole "Superadmin*" controller family).
if ($portal_host !== '' && ($portal_host === (getenv('SUPERADMIN_DOMAIN') ?: 'admin-bookiapp.kibusiness.co') || str_starts_with($portal_host, 'admin-'))) {
    $route['default_controller'] = 'superadmin_auth';
}

// BooKi (2026-09-16) - public marketing site on the marketplace host (booki.kibusiness.co).
// The root of this host serves the Landing page; the discovery portal stays reachable at
// /marketplace. See App_Controller::resolve_tenant()'s marketplace host exception.
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
$route['gizlilik'] = 'landing/privacy';
$route['gizlilik-politikasi'] = 'landing/privacy';
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

$route['agent/v1/stations']['get'] = 'agent_api/stations';

$route['agent/v1/verticals/data']['get'] = 'agent_api/vertical_data';

$route['agent/v1/handoff']['post'] = 'agent_api/handoff';

$route['agent/v1/marketing_attributions']['get'] = 'agent_api/marketing_attributions';

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
$route['adisyons/(:any)/(:any)'] = 'adisyons/$1/$2';
$route['adisyons/(:any)'] = 'adisyons/$1';
$route['restaurant'] = 'restaurant/index';
$route['restaurant/api/(:any)'] = 'restaurant/api_$1';
$route['restaurant/(:any)/(:any)'] = 'restaurant/$1/$2';
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
$route['onboarding/get_blueprints'] = 'onboarding/get_blueprints';
$route['onboarding/get_blueprint_details/(:any)'] = 'onboarding/get_blueprint_details/$1';
$route['onboarding/apply'] = 'onboarding/apply';
$route['onboarding/(:any)'] = 'customer_onboarding/index/$1';

// Multi-Vertical Enterprise Suite Web Routes
$route['verticals/gift_cards'] = 'verticals/gift_cards';
$route['verticals/redeem_gift_card'] = 'verticals/redeem_gift_card';
$route['verticals/kds'] = 'verticals/kds';
$route['verticals/sports'] = 'verticals/sports';
$route['verticals/create_sports_match'] = 'verticals/create_sports_match';
$route['verticals/join_sports_match'] = 'verticals/join_sports_match';
$route['verticals/verify_turnstile'] = 'verticals/verify_turnstile';
$route['verticals/clinic'] = 'verticals/clinic';
$route['verticals/add_clinical_record'] = 'verticals/add_clinical_record';
$route['verticals/get_patient_history/(:num)'] = 'verticals/get_patient_history/$1';
$route['verticals/save_patient_insurance'] = 'verticals/save_patient_insurance';
$route['verticals/get_telehealth_link/(:num)'] = 'verticals/get_telehealth_link/$1';
$route['verticals/automotive'] = 'verticals/automotive';
$route['verticals/add_vehicle'] = 'verticals/add_vehicle';
$route['verticals/create_work_order'] = 'verticals/create_work_order';
$route['verticals/update_work_order_status'] = 'verticals/update_work_order_status';
$route['verticals/save_vehicle_inspection'] = 'verticals/save_vehicle_inspection';
$route['verticals/experience'] = 'verticals/experience';
$route['verticals/save_digital_waiver'] = 'verticals/save_digital_waiver';
$route['verticals/sign_digital_waiver'] = 'verticals/sign_digital_waiver';
$route['verticals/issue_event_ticket'] = 'verticals/issue_event_ticket';
$route['verticals/validate_event_ticket'] = 'verticals/validate_event_ticket';
$route['verticals/hospitality'] = 'verticals/hospitality';
$route['verticals/update_room_status'] = 'verticals/update_room_status';
$route['verticals/add_room_charge'] = 'verticals/add_room_charge';
$route['verticals/save_guest_preferences'] = 'verticals/save_guest_preferences';

$route['kds'] = 'verticals/kds';
$route['gift_cards'] = 'verticals/gift_cards';
$route['sports'] = 'verticals/sports';
$route['clinic'] = 'verticals/clinic';
$route['automotive'] = 'verticals/automotive';
$route['experience'] = 'verticals/experience';
$route['hospitality'] = 'verticals/hospitality';

// Multi-Vertical Enterprise Suite API Routes
$route['api/v1/verticals/gift_cards/issue']['post'] = 'api/v1/verticals_api_v1/issue_gift_card';
$route['api/v1/verticals/gift_cards/(:any)']['get'] = 'api/v1/verticals_api_v1/get_gift_card/$1';
$route['api/v1/verticals/gift_cards/redeem']['post'] = 'api/v1/verticals_api_v1/redeem_gift_card';
$route['api/v1/verticals/deposits/record']['post'] = 'api/v1/verticals_api_v1/record_deposit';
$route['api/v1/verticals/deposits/update']['post'] = 'api/v1/verticals_api_v1/update_deposit_status';

$route['api/v1/verticals/restaurant/guest_preferences/(:num)']['get'] = 'api/v1/verticals_api_v1/get_guest_preferences/$1';
$route['api/v1/verticals/restaurant/guest_preferences/(:num)']['post'] = 'api/v1/verticals_api_v1/save_guest_preferences/$1';
$route['api/v1/verticals/restaurant/kds/orders']['get'] = 'api/v1/verticals_api_v1/get_kitchen_orders';
$route['api/v1/verticals/restaurant/kds/order']['post'] = 'api/v1/verticals_api_v1/create_kitchen_order';
$route['api/v1/verticals/restaurant/kds/update_status']['post'] = 'api/v1/verticals_api_v1/update_kitchen_order_status';

$route['api/v1/verticals/sports/matches']['get'] = 'api/v1/verticals_api_v1/get_sports_matches';
$route['api/v1/verticals/sports/matches']['post'] = 'api/v1/verticals_api_v1/create_sports_match';
$route['api/v1/verticals/sports/matches/(:num)/join']['post'] = 'api/v1/verticals_api_v1/join_sports_match/$1';
$route['api/v1/verticals/sports/turnstile/verify']['post'] = 'api/v1/verticals_api_v1/verify_turnstile';

$route['api/v1/verticals/clinic/records']['post'] = 'api/v1/verticals_api_v1/add_clinical_record';
$route['api/v1/verticals/clinic/patient_history/(:num)']['get'] = 'api/v1/verticals_api_v1/get_patient_clinical_history/$1';
$route['api/v1/verticals/clinic/insurance/(:num)']['post'] = 'api/v1/verticals_api_v1/save_patient_insurance/$1';
$route['api/v1/verticals/clinic/telehealth/(:num)']['get'] = 'api/v1/verticals_api_v1/get_telehealth_link/$1';

$route['api/v1/verticals/automotive/vehicles']['post'] = 'api/v1/verticals_api_v1/add_vehicle';
$route['api/v1/verticals/automotive/vehicles/by_plate/(:any)']['get'] = 'api/v1/verticals_api_v1/get_vehicle_by_plate/$1';
$route['api/v1/verticals/automotive/vehicles/customer/(:num)']['get'] = 'api/v1/verticals_api_v1/get_customer_vehicles/$1';
$route['api/v1/verticals/automotive/inspections']['post'] = 'api/v1/verticals_api_v1/save_vehicle_inspection';
$route['api/v1/verticals/automotive/inspections/(:any)']['get'] = 'api/v1/verticals_api_v1/public_inspection_report/$1';
$route['api/v1/verticals/automotive/inspections/(:any)/approve']['post'] = 'api/v1/verticals_api_v1/approve_inspection/$1';
$route['api/v1/verticals/automotive/work_orders']['post'] = 'api/v1/verticals_api_v1/create_work_order';
$route['api/v1/verticals/automotive/work_orders/(:num)/status']['post'] = 'api/v1/verticals_api_v1/update_work_order_status/$1';
$route['api/v1/verticals/automotive/work_orders/status']['post'] = 'api/v1/verticals_api_v1/update_work_order_status';

$route['api/v1/verticals/experience/waivers']['post'] = 'api/v1/verticals_api_v1/save_digital_waiver';
$route['api/v1/verticals/experience/waivers/sign']['post'] = 'api/v1/verticals_api_v1/sign_digital_waiver';
$route['api/v1/verticals/experience/addons/(:num)']['post'] = 'api/v1/verticals_api_v1/add_booking_addon/$1';
$route['api/v1/verticals/experience/tickets/issue']['post'] = 'api/v1/verticals_api_v1/issue_event_ticket';
$route['api/v1/verticals/experience/tickets']['post'] = 'api/v1/verticals_api_v1/issue_event_ticket';
$route['api/v1/verticals/experience/tickets/validate']['post'] = 'api/v1/verticals_api_v1/validate_event_ticket';

$route['verticals/hospitality_checkin'] = 'verticals/hospitality_checkin';
$route['verticals/hospitality_checkout'] = 'verticals/hospitality_checkout';
$route['verticals/hospitality_tape_chart'] = 'verticals/hospitality_tape_chart';
$route['verticals/hospitality_run_night_audit'] = 'verticals/hospitality_run_night_audit';
$route['verticals/hospitality_save_room_type'] = 'verticals/hospitality_save_room_type';
$route['verticals/hospitality_save_rate_plan'] = 'verticals/hospitality_save_rate_plan';
$route['verticals/hospitality_housekeeping_task_update'] = 'verticals/hospitality_housekeeping_task_update';
$route['verticals/hospitality_create_housekeeping_task'] = 'verticals/hospitality_create_housekeeping_task';
$route['verticals/hospitality_maintenance_ticket'] = 'verticals/hospitality_maintenance_ticket';
$route['verticals/hospitality_resolve_maintenance'] = 'verticals/hospitality_resolve_maintenance';
$route['verticals/hospitality_kbs_export'] = 'verticals/hospitality_kbs_export';
$route['verticals/hospitality_ical_export/(:num)'] = 'verticals/hospitality_ical_export/$1';
$route['verticals/hospitality_ical_sync'] = 'verticals/hospitality_ical_sync';
$route['verticals/hospitality_apply_property_template'] = 'verticals/hospitality_apply_property_template';

$route['api/v1/verticals/hospitality/rooms']['get'] = 'api/v1/verticals_api_v1/get_hotel_rooms';
$route['api/v1/verticals/hospitality/room_status']['post'] = 'api/v1/verticals_api_v1/update_room_status';
$route['api/v1/verticals/hospitality/charges']['post'] = 'api/v1/verticals_api_v1/add_room_charge';
$route['api/v1/verticals/hospitality/guest_preferences/(:num)']['post'] = 'api/v1/verticals_api_v1/save_guest_preferences/$1';
$route['api/v1/verticals/hospitality/tape_chart']['get'] = 'api/v1/verticals_api_v1/get_tape_chart';
$route['api/v1/verticals/hospitality/checkin']['post'] = 'api/v1/verticals_api_v1/express_checkin';
$route['api/v1/verticals/hospitality/checkout']['post'] = 'api/v1/verticals_api_v1/express_checkout';
$route['api/v1/verticals/hospitality/night_audit']['post'] = 'api/v1/verticals_api_v1/run_night_audit';
$route['api/v1/verticals/hospitality/room_types']['get'] = 'api/v1/verticals_api_v1/get_room_types';
$route['api/v1/verticals/hospitality/room_types']['post'] = 'api/v1/verticals_api_v1/save_room_type';
$route['api/v1/verticals/hospitality/property_templates']['get'] = 'api/v1/verticals_api_v1/get_property_templates';
$route['api/v1/verticals/hospitality/apply_template']['post'] = 'api/v1/verticals_api_v1/apply_property_template';
$route['api/v1/verticals/hospitality/housekeeping']['get'] = 'api/v1/verticals_api_v1/get_housekeeping_board';
$route['api/v1/verticals/hospitality/housekeeping/update']['post'] = 'api/v1/verticals_api_v1/update_housekeeping_task';
$route['api/v1/verticals/hospitality/maintenance']['post'] = 'api/v1/verticals_api_v1/create_maintenance_ticket';
$route['api/v1/verticals/hospitality/maintenance/resolve']['post'] = 'api/v1/verticals_api_v1/resolve_maintenance_ticket';
$route['api/v1/verticals/hospitality/kbs/export']['get'] = 'api/v1/verticals_api_v1/export_kbs';
$route['api/v1/verticals/hospitality/ical/(:num)']['get'] = 'api/v1/verticals_api_v1/export_room_ical/$1';
$route['api/v1/verticals/hospitality/ical/sync']['post'] = 'api/v1/verticals_api_v1/sync_room_ical';

$route['verticals/education'] = 'verticals/education';
$route['education'] = 'verticals/education';
$route['verticals/save_attendance'] = 'verticals/save_attendance';
$route['verticals/save_student_grade'] = 'verticals/save_student_grade';
$route['api/v1/verticals/education/attendance']['post'] = 'api/v1/verticals_api_v1/save_attendance';
$route['api/v1/verticals/education/grades']['post'] = 'api/v1/verticals_api_v1/save_student_grade';

// Sector 9: Professional Services (Legal, Consulting, Real Estate)
$route['verticals/legal'] = 'verticals/legal';
$route['legal'] = 'verticals/legal';
$route['verticals/save_legal_case'] = 'verticals/save_legal_case';
$route['api/v1/verticals/legal/cases']['post'] = 'api/v1/verticals_api_v1/save_legal_case';

$route['verticals/consulting'] = 'verticals/consulting';
$route['consulting'] = 'verticals/consulting';
$route['verticals/save_consulting_time_log'] = 'verticals/save_consulting_time_log';
$route['api/v1/verticals/consulting/time_logs']['post'] = 'api/v1/verticals_api_v1/save_consulting_time_log';

$route['verticals/real_estate'] = 'verticals/real_estate';
$route['real_estate'] = 'verticals/real_estate';
$route['verticals/save_real_estate_listing'] = 'verticals/save_real_estate_listing';
$route['api/v1/verticals/real_estate/listings']['post'] = 'api/v1/verticals_api_v1/save_real_estate_listing';


// CLI console
$route['console'] = 'console/index';
$route['console/(:any)'] = 'console/$1';
$route['console/(:any)/(:any)'] = 'console/$1/$2';
$route['console/(:any)/(:any)/(:any)'] = 'console/$1/$2/$3';

// Unified Virtual POS Webhook & Callback endpoints (iyzico, PayTR, Stripe, Garanti, Enpara)
$route['payment/callback'] = 'payment_webhooks/callback';
$route['payment/callback/(:any)'] = 'payment_webhooks/callback/$1';
$route['payment/unified_callback'] = 'payment_webhooks/callback';
$route['payment/unified_callback/(:any)'] = 'payment_webhooks/callback/$1';
// RandevuBurada Tenant Hub Routes
$route['randevuburada'] = 'randevuburada/profile';
$route['randevuburada/profile'] = 'randevuburada/profile';
$route['randevuburada/save_profile'] = 'randevuburada/save_profile';
$route['randevuburada/services'] = 'randevuburada/services';
$route['randevuburada/toggle_service'] = 'randevuburada/toggle_service';
$route['randevuburada/save_service_price'] = 'randevuburada/save_service_price';
$route['randevuburada/reviews'] = 'randevuburada/reviews';
$route['randevuburada/save_sources'] = 'randevuburada/save_sources';
$route['randevuburada/publish_review'] = 'randevuburada/publish_review';
$route['randevuburada/reject_review'] = 'randevuburada/reject_review';
$route['randevuburada/reservations'] = 'randevuburada/reservations';
$route['randevuburada/update_reservation_status'] = 'randevuburada/update_reservation_status';

// Unified Settings Center & Legacy Route Aliases
$route['general_settings'] = 'settings/general';
$route['booking_settings'] = 'settings/booking';
$route['business_settings'] = 'settings/business';
$route['legal_settings'] = 'settings/legal';
$route['messaging_settings'] = 'settings/communication';
$route['integrations'] = 'settings/integrations';
$route['api_settings'] = 'settings/security';

$route['settings/api/reveal_secret']['post'] = 'settings/reveal_secret';
$route['settings/api/rotate_agent_key']['post'] = 'settings/rotate_agent_key';
$route['settings/api/test_ping']['post'] = 'settings/test_ping';
$route['settings/api/add_blocked_period']['post'] = 'settings/add_blocked_period';
$route['settings/api/delete_blocked_period']['post'] = 'settings/delete_blocked_period';
$route['settings/api/apply_global_working_plan']['post'] = 'settings/apply_global_working_plan';
$route['settings/api/(:any)']['get'] = 'settings/api_get/$1';
$route['settings/api/(:any)']['post'] = 'settings/api_save/$1';
$route['settings/api/(:any)']['put'] = 'settings/api_save/$1';
$route['settings/(:any)'] = 'settings/$1';
$route['settings'] = 'settings/index';

// Unified Omnichannel Chat Portal & AI Copilot Routes
$route['chat_portal'] = 'chat_portal/index';
$route['chat_portal/api_threads'] = 'chat_portal/api_threads';
$route['chat_portal/api_messages'] = 'chat_portal/api_messages';
$route['chat_portal/api_send'] = 'chat_portal/api_send';
$route['chat_portal/api_toggle_handoff'] = 'chat_portal/api_toggle_handoff';
$route['chat_portal/api_suggest'] = 'chat_portal/api_suggest';
$route['chat_portal/api_switch_wa_mode'] = 'chat_portal/api_switch_wa_mode';
$route['chat_portal/api_qr_status'] = 'chat_portal/api_qr_status';
$route['chat_portal/api_qr_start'] = 'chat_portal/api_qr_start';
$route['inbox'] = 'chat_portal/index';
$route['messages'] = 'chat_portal/index';

// Enterprise HRMS Aliases
$route['ik'] = 'hr/index';
$route['pdks'] = 'attendance/index';
$route['izinler'] = 'leaves/index';
$route['bordro'] = 'payroll/index';
$route['personel_portali'] = 'ess/index';

// Online Consent / Waiver Signing
$route['consents/sign/(:any)'] = 'consents/sign/$1';
$route['consents/submit_signature/(:any)'] = 'consents/submit_signature/$1';

/* End of file routes.php */
/* Location: ./application/config/routes.php */

