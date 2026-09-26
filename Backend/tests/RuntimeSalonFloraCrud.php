<?php
declare(strict_types=1);

$log = '/tmp/booki-salonflora-crud-' . date('Ymd-His') . '.log';
$write = static function (string $message) use ($log): void {
    file_put_contents($log, '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND);
    echo $message . PHP_EOL;
};
$fail = static function (string $message) use ($write): never {
    $write('FAIL ' . $message);
    throw new RuntimeException($message);
};
$check = static function (bool $condition, string $message) use ($fail): void {
    if (!$condition) {
        $fail($message);
    }
};

$_SERVER['argv'] = ['index.php', 'console', 'noop'];
$_SERVER['REQUEST_METHOD'] = null;
putenv('APP_ENV=testing');
$_SERVER['APP_ENV'] = 'testing';
ob_start();
require '/var/www/html/index.php';
ob_end_clean();
$ci = get_instance();
$db = $ci->db;

$check($db->table_exists('tenants'), 'master database has no tenants table');
$tenant = $db->get_where('tenants', ['subdomain' => 'salonflora'])->row_array();
$check((bool) $tenant, 'tenant salonflora not found');

$tenantConfig = [
    'hostname' => $tenant['db_host'],
    'username' => $tenant['db_username'],
    'password' => tenant_master_decrypt($tenant['db_password']),
    'database' => $tenant['db_name'],
    'dbdriver' => 'mysqli',
    'dbprefix' => 'ea_',
    'pconnect' => false,
    'db_debug' => true,
    'cache_on' => false,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
];
$ci->load->database($tenantConfig, false, true);
tenant_context([
    'id' => (int) $tenant['id'],
    'subdomain' => 'salonflora',
    'pii_enc_key' => tenant_master_decrypt($tenant['pii_enc_key']),
    'pii_hash_key' => tenant_master_decrypt($tenant['pii_hash_key']),
]);
$db = $ci->db;
$write('START tenant=salonflora db=' . $db->database);
$db->trans_begin();

try {
    $ci->load->model('providers_model');
    $ci->load->model('customers_model');
    $ci->load->model('appointments_model');

    $provider = $ci->providers_model->get([], 1, 0, 'id ASC')[0] ?? null;
    $customer = $ci->customers_model->get([], 1, 0, 'id ASC')[0] ?? null;
    $service = $db->order_by('id', 'ASC')->get('services', 1)->row_array();
    $check($provider && $customer && $service, 'missing provider/customer/service fixture');
    $write('FIXTURES provider=' . $provider['id'] . ' customer=' . $customer['id'] . ' service=' . $service['id']);

    $start = date('Y-m-d H:i:s', strtotime('+2 days 09:00:00'));
    $end = date('Y-m-d H:i:s', strtotime('+2 days 10:00:00'));
    $appointmentId = $ci->appointments_model->save([
        'start_datetime' => $start,
        'end_datetime' => $end,
        'id_services' => (int) $service['id'],
        'id_users_provider' => (int) $provider['id'],
        'id_users_customer' => (int) $customer['id'],
        'notes' => 'runtime CRUD test',
        'is_unavailability' => false,
    ]);
    $check($appointmentId > 0, 'booking create returned no id');
    $check((int) $db->where('id', $appointmentId)->count_all_results('appointments') === 1, 'booking create not persisted');
    $write('BOOKING_CREATE id=' . $appointmentId . ' PASS');

    $ci->appointments_model->save([
        'id' => $appointmentId,
        'start_datetime' => $start,
        'end_datetime' => date('Y-m-d H:i:s', strtotime('+2 days 11:00:00')),
        'id_services' => (int) $service['id'],
        'id_users_provider' => (int) $provider['id'],
        'id_users_customer' => (int) $customer['id'],
        'notes' => 'runtime CRUD test updated',
        'is_unavailability' => false,
    ]);
    $updated = $db->get_where('appointments', ['id' => $appointmentId])->row_array();
    $check(($updated['notes'] ?? null) === 'runtime CRUD test updated', 'booking update not persisted');
    $write('BOOKING_UPDATE id=' . $appointmentId . ' PASS');

    $ci->appointments_model->delete($appointmentId);
    $check((int) $db->where('id', $appointmentId)->count_all_results('appointments') === 0, 'booking delete not persisted');
    $write('BOOKING_DELETE id=' . $appointmentId . ' PASS');

    $suffix = date('YmdHis') . random_int(100, 999);
    $providerId = $ci->providers_model->save([
        'first_name' => 'Runtime',
        'last_name' => 'Test',
        'email' => 'runtime-' . $suffix . '@example.test',
        'services' => [(int) $service['id']],
        'stations' => [],
        'station_restriction_enabled' => false,
        'settings' => [
            'username' => 'runtime_' . $suffix,
            'password' => 'RuntimeTest#2026',
        ],
    ]);
    $check($providerId > 0, 'staff create returned no id');
    $write('STAFF_CREATE id=' . $providerId . ' PASS');

    $ci->providers_model->save([
        'id' => $providerId,
        'first_name' => 'Runtime Updated',
        'last_name' => 'Test',
        'email' => 'runtime-' . $suffix . '@example.test',
        'services' => [(int) $service['id']],
        'stations' => [],
        'station_restriction_enabled' => false,
        'settings' => ['username' => 'runtime_' . $suffix],
    ]);
    $updatedProvider = $db->get_where('users', ['id' => $providerId])->row_array();
    $check(($updatedProvider['first_name'] ?? null) === 'Runtime Updated', 'staff update not persisted');
    $write('STAFF_UPDATE id=' . $providerId . ' PASS');

    $settingsColumns = $db->query('SHOW COLUMNS FROM ea_user_settings')->result_array();
    $settingsColumnNames = array_column($settingsColumns, 'Field');
    $hasActiveField = in_array('is_active', $settingsColumnNames, true);
    $hasUserActiveField = array_key_exists('is_active', $updatedProvider);
    if (!$hasActiveField && !$hasUserActiveField) {
        $write('STAFF_TOGGLE BLOCKED no active/inactive field in users or user_settings');
    } else {
        $write('STAFF_TOGGLE REVIEW active field exists but needs product-level toggle contract');
    }

    $ci->providers_model->delete($providerId);
    $check((int) $db->where('id', $providerId)->count_all_results('users') === 0, 'staff delete not persisted');
    $write('STAFF_DELETE id=' . $providerId . ' PASS');

    $db->trans_rollback();
    $write('ROLLBACK PASS');
    $write('RESULT PASS log=' . $log);
} catch (Throwable $exception) {
    $db->trans_rollback();
    $write('ROLLBACK AFTER FAILURE');
    $write('DEBUG exception=' . get_class($exception) . ' message=' . $exception->getMessage());
    $write('DEBUG last_query=' . ($db->last_query() ?: 'none'));
    throw $exception;
}
