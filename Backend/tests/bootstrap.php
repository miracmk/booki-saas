<?php declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 *
 * Initializes the test environment by loading Composer's autoloader and setting up constants.
 */

require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Tests\\')) {
        $relativeClass = substr($class, 6);
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../system/');
}

putenv('APP_ENV=testing');
$_SERVER['APP_ENV'] = 'testing';
