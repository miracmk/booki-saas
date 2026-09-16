<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| AUTO-LOADER
| -------------------------------------------------------------------
| This file specifies which systems should be loaded by default.
|
| In order to keep the framework as light-weight as possible only the
| absolute minimal resources are loaded by default. For example,
| the database is not connected to automatically since no assumption
| is made regarding whether you intend to use it.  This file lets
| you globally define which systems you would like loaded with every
| request.
|
| -------------------------------------------------------------------
| Instructions
| -------------------------------------------------------------------
|
| These are the things you can load automatically:
|
| 1. Packages
| 2. Libraries
| 3. Helper files
| 4. Custom config files
| 5. Language files
| 6. Models
|
*/

/*
| -------------------------------------------------------------------
|  Auto-load Packages
| -------------------------------------------------------------------
| Prototype:
|
|  $autoload['packages'] = array(APPPATH.'third_party', '/usr/local/shared');
|
*/

$autoload['packages'] = [];

/*
| -------------------------------------------------------------------
|  Auto-load Libraries
| -------------------------------------------------------------------
| These are the classes located in the system/libraries folder
| or in your application/libraries folder.
|
| Prototype:
|
|	$autoload['libraries'] = array('database', 'session', 'xmlrpc');
*/

$autoload['libraries'] = ['database', 'session'];

/*
| -------------------------------------------------------------------
|  Auto-load Helper Files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['helper'] = array('url', 'file');
*/

$autoload['helper'] = [
    'array',
    'asset',
    'config',
    'correlation', // Ki Reservation (2026-08-28) - correlation_id() for distributed tracing
    'date',
    'debug',
    'env',
    'file',
    'html',
    'http',
    'installation',
    'language',
    'password',
    'path',
    'permission',
    'plan', // Ki Reservation (2026-09-12) - Free/Basic/Premium/Elite feature gating, see the helper's docblock
    'rate_limit',
    'routes',
    'salonflora_audit', // Ki Reservation customization - audit_log() (see the helper's docblock)
    'salonflora_crypto', // Salon Flora customization - PII encryption/hashing (see the helper's docblock)
    'security',
    'session',
    'setting',
    'string',
    'tenant', // Ki Reservation (2026-08-26) - tenant_context()/is_multi_tenant_mode(), see the helper's docblock
    'tenant_master_crypto', // Ki Reservation (2026-08-26) - protects tenant secrets in the master DB
    'url',
    'validation',
];

/*
| -------------------------------------------------------------------
|  Auto-load Config files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['config'] = array('config1', 'config2');
|
| NOTE: This item is intended for use ONLY if you have created custom
| config files.  Otherwise, leave it blank.
|
*/

$autoload['config'] = ['app', 'google', 'email'];

/*
| -------------------------------------------------------------------
|  Auto-load Language files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['language'] = array('lang1', 'lang2');
|
| NOTE: Do not include the "_lang" part of your file.  For example
| "codeigniter_lang.php" would be referenced as array('codeigniter');
|
*/

$autoload['language'] = [];

/*
| -------------------------------------------------------------------
|  Auto-load Models
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['model'] = array('model1', 'model2');
|
*/

$autoload['model'] = [];

/* End of file autoload.php */
/* Location: ./application/config/autoload.php */
