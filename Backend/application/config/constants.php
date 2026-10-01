<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
const FILE_READ_MODE = 0644;
const FILE_WRITE_MODE = 0666;
const DIR_READ_MODE = 0755;
const DIR_WRITE_MODE = 0777;

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

const FOPEN_READ = 'rb';
const FOPEN_READ_WRITE = 'r+b';
const FOPEN_WRITE_CREATE_DESTRUCTIVE = 'wb'; // truncates existing file data, use with care
const FOPEN_READ_WRITE_CREATE_DESTRUCTIVE = 'w+b'; // truncates existing file data, use with care
const FOPEN_WRITE_CREATE = 'ab';
const FOPEN_READ_WRITE_CREATE = 'a+b';
const FOPEN_WRITE_CREATE_STRICT = 'xb';
const FOPEN_READ_WRITE_CREATE_STRICT = 'x+b';

/*
|--------------------------------------------------------------------------
| Application Data
|--------------------------------------------------------------------------
|
| These constants are used globally from the application when handling data.
|
*/
const DB_SLUG_CUSTOMER = 'customer';
const DB_SLUG_PROVIDER = 'provider';
const DB_SLUG_ADMIN = 'admin';
const DB_SLUG_SECRETARY = 'secretary';

const FILTER_TYPE_ALL = 'all';
const FILTER_TYPE_PROVIDER = 'provider';
const FILTER_TYPE_SERVICE = 'service';

const AJAX_SUCCESS = 'SUCCESS';
const AJAX_FAILURE = 'FAILURE';

const SETTINGS_SYSTEM = 'SETTINGS_SYSTEM';
const SETTINGS_USER = 'SETTINGS_USER';

const PRIV_VIEW = 1;
const PRIV_ADD = 2;
const PRIV_EDIT = 4;
const PRIV_DELETE = 8;

const PRIV_DASHBOARD = 'dashboard';
const PRIV_APPOINTMENTS = 'appointments';
const PRIV_CUSTOMERS = 'customers';
const PRIV_SERVICES = 'services';
const PRIV_USERS = 'users';
const PRIV_SYSTEM_SETTINGS = 'system_settings';
const PRIV_USER_SETTINGS = 'user_settings';
const PRIV_WEBHOOKS = 'webhooks';
const PRIV_BLOCKED_PERIODS = 'blocked_periods';
const PRIV_STATIONS = 'stations'; // Salon Flora customization
const PRIV_REPORTS = 'reports'; // Salon Flora customization
const PRIV_BRANCHES = 'branches'; // BooKi customization (2026-08-27)
const PRIV_PACKAGES = 'packages'; // BooKi customization (2026-08-27)
const PRIV_PRODUCTS = 'products'; // BooKi customization (2026-08-27)
const PRIV_WAITLIST = 'waitlist'; // BooKi customization (Dalga 1, 2026-08-28)
const PRIV_MEMBERSHIPS = 'memberships'; // BooKi customization (Dalga 1, 2026-08-28)
const PRIV_INVOICES = 'invoices'; // BooKi customization (Dalga 1, 2026-08-28)
const PRIV_POS = 'pos'; // BooKi customization (Dalga 1, 2026-08-28)
const PRIV_MARKETING = 'marketing'; // BooKi customization (Dalga 3 / Faz 3.3, 2026-09-09)
const PRIV_REVIEWS = 'reviews'; // BooKi customization (Dalga 3 / Faz 3.4, 2026-09-09)
const PRIV_AI_AGENT = 'ai_agent'; // BooKi customization (Dalga 4, 2026-09-12)

const DATE_FORMAT_DMY = 'DMY';
const DATE_FORMAT_MDY = 'MDY';
const DATE_FORMAT_YMD = 'YMD';

const TIME_FORMAT_REGULAR = 'regular';
const TIME_FORMAT_MILITARY = 'military';

const MIN_PASSWORD_LENGTH = 7;
const MAX_PASSWORD_LENGTH = 100;
const ANY_PROVIDER = 'any-provider';

const CALENDAR_VIEW_DEFAULT = 'default';
const CALENDAR_VIEW_TABLE = 'table';

const AVAILABILITIES_TYPE_FLEXIBLE = 'flexible';
const AVAILABILITIES_TYPE_FIXED = 'fixed';

const EVENT_MINIMUM_DURATION = 5; // Minutes

const DEFAULT_COMPANY_COLOR = '#ffffff';

const LDAP_DEFAULT_FILTER = '(&(objectClass=*)(|(cn={{KEYWORD}})(sn={{KEYWORD}})(mail={{KEYWORD}})(givenName={{KEYWORD}})(uid={{KEYWORD}})))';

const LDAP_WHITELISTED_ATTRIBUTES = [
    'givenname',
    'cn',
    'dn',
    'sn',
    'mail',
    'telephonenumber',
    'description',
    'member',
    'objectclass',
    'objectcategory',
    'instancetype',
    'whencreated',
    'name',
    'samaccountname',
    'samaccounttype',
    'objectcategory',
    'memberof',
    'distinguishedname',
    'uid',
];

const LDAP_DEFAULT_FIELD_MAPPING = [
    'first_name' => 'givenname',
    'last_name' => 'sn',
    'email' => 'mail',
    'phone_number' => 'telephonenumber',
    'username' => 'cn',
];

/*
|--------------------------------------------------------------------------
| Webhook Actions
|--------------------------------------------------------------------------
|
| External application endpoints can subscribe to these webhook actions.  
|
*/

const WEBHOOK_APPOINTMENT_SAVE = 'appointment_save';
const WEBHOOK_APPOINTMENT_DELETE = 'appointment_delete';
const WEBHOOK_UNAVAILABILITY_SAVE = 'unavailability_save';
const WEBHOOK_UNAVAILABILITY_DELETE = 'unavailability_delete';
const WEBHOOK_CUSTOMER_SAVE = 'customer_save';
const WEBHOOK_CUSTOMER_DELETE = 'customer_delete';
const WEBHOOK_SERVICE_SAVE = 'service_save';
const WEBHOOK_SERVICE_DELETE = 'service_delete';
const WEBHOOK_SERVICE_CATEGORY_SAVE = 'service_category_save';
const WEBHOOK_SERVICE_CATEGORY_DELETE = 'service_category_delete';
const WEBHOOK_PROVIDER_SAVE = 'provider_save';
const WEBHOOK_PROVIDER_DELETE = 'provider_delete';
const WEBHOOK_SECRETARY_SAVE = 'secretary_save';
const WEBHOOK_SECRETARY_DELETE = 'secretary_delete';
const WEBHOOK_ADMIN_SAVE = 'admin_save';
const WEBHOOK_ADMIN_DELETE = 'admin_delete';
const WEBHOOK_BLOCKED_PERIOD_SAVE = 'blocked_period_save';
const WEBHOOK_BLOCKED_PERIOD_DELETE = 'blocked_period_delete';

const STORAGE_RETENTION_DAYS = 90;

/*
|--------------------------------------------------------------------------
| Salon Flora Session Tracking Thresholds
|--------------------------------------------------------------------------
|
| Control how a completed session's real duration (actual_end_datetime -
| actual_start_datetime) is compared against the service's planned
| duration to decide whether staff must give a reason for the deviation.
|
*/

const SESSION_WARNING_THRESHOLD_MINUTES = 10;
const SESSION_LATE_START_GRACE_MINUTES = 10;

// Salon Flora customization (2026-08-25) - default for the 'session_deviation_tolerance_minutes' setting
// (business_settings.php lets an admin change it). A session's real duration within +/- this many minutes
// of the service's planned duration is billed as the FULL planned duration ("Normal", no question asked).
// Outside the window: running over bills the real (longer) duration; leaving early requires staff to
// classify it as justified (bills full planned duration) or unjustified (bills the real, shorter
// duration) - see Appointments_model::compute_effective_billing() and Calendar.php::check_out().
//
// Replaces the old SESSION_DEVIATION_EARLY_MIN_MINUTES/EARLY_PERCENT/LATE_MINUTES thresholds (which only
// gated whether a reason was REQUIRED) and the old SESSION_BILLING_ROUND_MINUTES=30 blind-rounding rule
// (which silently billed e.g. a 54-minute real session against a 60-minute service as just 30 minutes -
// a confirmed real underpayment of provider commissions, not an intentional discount).
const SESSION_DEVIATION_TOLERANCE_MINUTES_DEFAULT = 10;

// Salon Flora customization (2026-08-25) - fixed reason codes for an early exit beyond tolerance (staff
// picks one, then separately marks it haklı/haksız - see Calendar.php::check_out()). Keys are stored on
// appointments.early_exit_reason_code; labels are what staff see.
const EARLY_EXIT_REASON_CODES = [
    'customer_left_early' => 'Müşteri erken ayrılmak istedi',
    'customer_late_arrival' => 'Müşteri geç geldi, seans kısaldı',
    'health_issue' => 'Sağlık durumu',
    'technical_issue' => 'Teknik/ekipman arızası',
    'other' => 'Diğer',
];

/*
|--------------------------------------------------------------------------
| Salon Flora Payment Tracking
|--------------------------------------------------------------------------
|
| Allowed payment methods and payment_status values for a completed
| appointment, and how often the "did the session finish?" prompt re-asks
| when staff dismiss it with "not yet".
|
*/

const PAYMENT_METHODS = ['iban', 'physical_pos', 'virtual_pos', 'cash'];
const PAYMENT_STATUS_PENDING = 'pending';
const PAYMENT_STATUS_COLLECTED = 'collected';
const PAYMENT_STATUS_NOT_COLLECTED = 'not_collected';
const SESSION_END_PROMPT_SNOOZE_MINUTES = 5;

/*
|--------------------------------------------------------------------------
| Salon Flora Bursa Districts
|--------------------------------------------------------------------------
|
| Fixed list of districts used for the (repurposed) "city" field, since the
| business only operates in Bursa. Kept as a single shared constant so the
| public booking form and every backend form (customers, appointment modal)
| offer identical values.
|
*/

const SALONFLORA_BURSA_DISTRICTS = [
    'Osmangazi', 'Nilüfer', 'Yıldırım', 'Gürsu', 'Kestel', 'Mudanya', 'Gemlik',
    'İnegöl', 'Orhangazi', 'Karacabey', 'Mustafakemalpaşa', 'Orhaneli', 'Harmancık',
    'Keles', 'Büyükorhan', 'İznik',
];

/*
|--------------------------------------------------------------------------
| BooKi Core Platform Version
|--------------------------------------------------------------------------
*/
defined('BOOKI_VERSION') || define('BOOKI_VERSION', '1.0.0');

/* End of file constants.php */
/* Location: ./application/config/constants.php */
