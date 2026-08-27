<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - SMS gateway factory (2026-08-27).
 *
 * Creates appropriate SMS gateway instance based on configuration.
 * Returns null if no gateway is configured or if gateway type is unknown.
 *
 * @package Libraries\Messaging
 */

class Sms_gateway_factory
{
    /**
     * Create an SMS gateway instance from settings array.
     *
     * @param array $settings Array with keys 'sms_gateway', 'netgsm_username', 'netgsm_password', etc.
     *
     * @return Sms_gateway_interface|null Gateway instance, or null if no gateway is configured.
     */
    public static function make(array $settings): ?Sms_gateway_interface
    {
        $gateway_type = $settings['sms_gateway'] ?? 'none';

        switch ($gateway_type) {
            case 'netgsm':
                return new Netgsm_sms_gateway(
                    $settings['netgsm_username'] ?? null,
                    $settings['netgsm_password'] ?? null,
                    $settings['netgsm_header'] ?? null,
                );

            case 'none':
            default:
                return null;
        }
    }
}
