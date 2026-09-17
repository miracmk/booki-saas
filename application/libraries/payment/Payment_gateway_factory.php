<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - payment gateway factory (2026-08-27).
 *
 * Creates the appropriate gateway instance based on payment settings.
 * ---------------------------------------------------------------------------- */

class Payment_gateway_factory
{
    /**
     * Create a gateway instance for the given settings.
     *
     * @param array $payment_settings Payment settings row (from payment_settings table).
     *
     * @return Payment_gateway_interface|null Null if active_gateway is 'none' (payments disabled).
     *
     * @throws RuntimeException On unknown gateway type.
     */
    public static function make(array $payment_settings): ?Payment_gateway_interface
    {
        $active_gateway = $payment_settings['active_gateway'] ?? 'none';

        switch ($active_gateway) {
            case 'none':
                return null;

            case 'iyzico':
                self::load_gateway('iyzico');

                return new Iyzico_gateway($payment_settings);

            case 'paytr':
                self::load_gateway('paytr');

                return new Paytr_gateway($payment_settings);

            case 'stripe':
                self::load_gateway('stripe');

                return new Stripe_gateway($payment_settings);

            case 'odeal':
                self::load_gateway('odeal');

                return new Odeal_gateway($payment_settings);

            case 'garanti':
                self::load_gateway('garanti');

                return new Garanti_gateway($payment_settings);

            case 'enpara':
                self::load_gateway('enpara');

                return new Enpara_gateway($payment_settings);

            default:
                throw new RuntimeException("Unknown payment gateway: {$active_gateway}");
        }
    }

    /**
     * Load a gateway class file.
     *
     * @param string $gateway_name Gateway name (iyzico, paytr, stripe).
     */
    private static function load_gateway(string $gateway_name): void
    {
        $class_name = ucfirst($gateway_name) . '_gateway';
        $file_path = APPPATH . 'libraries/payment/' . $class_name . '.php';

        if (!class_exists($class_name) && is_file($file_path)) {
            require_once $file_path;
        }
    }
}
