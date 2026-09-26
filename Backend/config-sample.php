<?php
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
 * BooKi Configuration File
 *
 * Set your installation BASE_URL * without the trailing slash * and the database
 * credentials in order to connect to the database. You can enable the DEBUG_MODE
 * while developing the application.
 *
 * Set the default language by changing the LANGUAGE constant. For a full list of
 * available languages look at the /application/config/config.php file.
 */
class Config
{
    // ------------------------------------------------------------------------
    // GENERAL SETTINGS
    // ------------------------------------------------------------------------

    const BASE_URL = 'http://localhost';
    const LANGUAGE = 'english';
    const DEBUG_MODE = false;

    // ------------------------------------------------------------------------
    // DATABASE SETTINGS
    // ------------------------------------------------------------------------

    const DB_HOST = 'mysql';
    const DB_NAME = 'ki_reservation';
    const DB_USERNAME = 'user';
    const DB_PASSWORD = 'password';

    // ------------------------------------------------------------------------
    // GOOGLE CALENDAR SYNC (Optional - can also be configured via UI)
    // ------------------------------------------------------------------------
    // These settings are optional and can be configured through the admin UI
    // at Settings > Integrations > Google Calendar. If configured here, they
    // will be used as fallback values.
    //
    // const GOOGLE_SYNC_FEATURE = false;
    // const GOOGLE_CLIENT_ID = '';
    // const GOOGLE_CLIENT_SECRET = '';
}
