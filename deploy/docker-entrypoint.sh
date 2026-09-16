#!/bin/bash

# -----------------------------------------------------------------------------
# Easy!Appointments - Online Appointment Scheduler
#
# @package     EasyAppointments
# @author      A.Tselegidis <alextselegidis@gmail.com>
# @copyright   Copyright (c) Alex Tselegidis
# @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
# @link        https://easyappointments.org
# -----------------------------------------------------------------------------

##
# Set up the currently cloned Easy!Appointments build.
#
# This script will perform the required actions so that Easy!Appointments is configured properly to work with the
# provided environment variables.
#
# Usage:
#
#  ./docker-entrypoint.sh
#

# Config

cat <<EOF >/var/www/html/config.php
<?php
class Config {
    const BASE_URL              = '${BASE_URL}';
    const LANGUAGE              = '${LANGUAGE}';
    const DEBUG_MODE            = ${DEBUG_MODE};
    const DB_HOST               = '${DB_HOST}';
    const DB_NAME               = '${DB_NAME}';
    const DB_USERNAME           = '${DB_USERNAME}';
    const DB_PASSWORD           = '${DB_PASSWORD}';
    const GOOGLE_SYNC_FEATURE   = ${GOOGLE_SYNC_FEATURE};
    const GOOGLE_CLIENT_ID      = '${GOOGLE_CLIENT_ID}';
    const GOOGLE_CLIENT_SECRET  = '${GOOGLE_CLIENT_SECRET}';
}
EOF

# Email Config

cat <<EOF >/var/www/html/application/config/email.php
<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add custom values by settings them to the $config array.
// Example: $config['smtp_host'] = 'smtp.gmail.com';
// @link https://codeigniter.com/user_guide/libraries/email.html

\$config['useragent'] = 'Ki Reservation';
\$config['protocol'] = '${MAIL_PROTOCOL}'; // or 'smtp'
\$config['mailtype'] = 'html'; // or 'text'
\$config['smtp_debug'] = '${MAIL_SMTP_DEBUG}'; // or '1'
\$config['smtp_auth'] = ${MAIL_SMTP_AUTH}; //or FALSE for anonymous relay.
\$config['smtp_host'] = '${MAIL_SMTP_HOST}';
\$config['smtp_user'] = '${MAIL_SMTP_USER}';
\$config['smtp_pass'] = '${MAIL_SMTP_PASS}';
\$config['smtp_crypto'] = '${MAIL_SMTP_CRYPTO}'; // or 'tls'
\$config['smtp_port'] = ${MAIL_SMTP_PORT};
\$config['from_name'] = '${MAIL_FROM_NAME}';
\$config['from_address'] = '${MAIL_FROM_ADDRESS}';
\$config['reply_to'] = '${MAIL_REPLY_TO_ADDRESS}';
\$config['crlf'] = "\r\n";
\$config['newline'] = "\r\n";
EOF

# Ki Reservation (2026-08-26) - unlike Salon Flora's own standalone deployment (one fixed domain,
# where forcing base_url makes sense), this is the multi-tenant SaaS instance: config.php ALREADY
# computes base_url dynamically per request from the actual Host header (see its own
# $protocol/$domain/$request_uri logic), which is what lets each tenant's own subdomain/custom domain
# work correctly for redirects and asset URLs. Appending a fixed override here (as Salon Flora's copy
# of this script does) would silently break that for every tenant except whichever one happens to
# match BASE_URL - Config::BASE_URL stays available for the CLI-only fallback config.php already
# falls back to (cron-triggered emails etc.), so nothing else needs this file-append.

# Start Apache

apache2-foreground
