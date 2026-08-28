<?php declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 *
 * Initializes the test environment by loading Composer's autoloader and setting up constants.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../system/');
}

putenv('APP_ENV=testing');
$_SERVER['APP_ENV'] = 'testing';
