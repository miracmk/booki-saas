<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Accounting connector interface.
 *
 * Defines the contract for accounting system integrations (e.g., Paraşüt).
 * Implementations must handle OAuth2 authentication, invoice creation, and status tracking.
 *
 * @package Libraries
 */
interface Accounting_connector_interface
{
    /**
     * Establish a connection to the accounting service.
     *
     * Performs OAuth2 authentication or credential setup.
     *
     * @param array $credentials OAuth/connection credentials (e.g., ['authorization_code' => '...', ...]).
     *
     * @throws RuntimeException If connection setup fails.
     */
    public function connect(array $credentials): void;

    /**
     * Check if a valid connection is currently established.
     *
     * @return bool True if the connector is authenticated and ready to use.
     */
    public function is_connected(): bool;

    /**
     * Create an invoice in the accounting system for a completed appointment.
     *
     * @param array $appointment Appointment record with id, start_datetime, end_datetime, id_services, etc.
     * @param array $customer Customer record with id, first_name, last_name, email, phone_number, etc.
     *
     * @return string External invoice ID assigned by the accounting system (for reference).
     *
     * @throws RuntimeException If invoice creation fails.
     */
    public function create_invoice(array $appointment, array $customer): string;

    /**
     * Retrieve the status of an invoice previously created in the accounting system.
     *
     * @param string $external_id Invoice ID assigned by the accounting system (return value from create_invoice).
     *
     * @return string Status string (e.g., 'pending', 'sent', 'paid', 'cancelled').
     *
     * @throws RuntimeException If status retrieval fails.
     */
    public function get_invoice_status(string $external_id): string;
}
