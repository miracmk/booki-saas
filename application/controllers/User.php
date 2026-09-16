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
 * User controller.
 *
 * Handles the user related operations.
 *
 * @package Controllers
 */
class User extends EA_Controller
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('accounts');
        $this->load->library('email_messages');
    }

    /**
     * Redirect to the login page.
     */
    public function index(): void
    {
        redirect('login');
    }

    /**
     * Display the login page.
     *
     * @deprecated Since 1.5 Use the Login controller instead.
     */
    public function login(): void
    {
        redirect('login');
    }

    /**
     * Display the logout page.
     *
     * @deprecated Since 1.5 Use the Logout controller instead.
     */
    public function logout(): void
    {
        redirect('logout');
    }

    /**
     * Display the password recovery page.
     *
     * @deprecated Since 1.5 Use the Logout controller instead.
     */
    public function forgot_password(): void
    {
        redirect('recovery');
    }
}
