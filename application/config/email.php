<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add custom values by settings them to the  array.
// Example: ['smtp_host'] = 'smtp.gmail.com';
// @link https://codeigniter.com/user_guide/libraries/email.html

$config['useragent'] = 'Ki Reservation';
$config['protocol'] = 'smtp'; // or 'smtp'
$config['mailtype'] = 'html'; // or 'text'
$config['smtp_debug'] = '0'; // or '1'
$config['smtp_auth'] = 1; //or FALSE for anonymous relay.
$config['smtp_host'] = getenv('MAIL_SMTP_HOST') ?: '';
$config['smtp_user'] = getenv('MAIL_SMTP_USER') ?: '';
$config['smtp_pass'] = getenv('MAIL_SMTP_PASS') ?: '';
$config['smtp_crypto'] = getenv('MAIL_SMTP_CRYPTO') ?: 'tls';
$config['smtp_port'] = (int) (getenv('MAIL_SMTP_PORT') ?: 587);
$config['from_name'] = getenv('MAIL_FROM_NAME') ?: 'Ki Reservation';
$config['from_address'] = getenv('MAIL_FROM_ADDRESS') ?: '';
$config['reply_to'] = getenv('MAIL_REPLY_TO_ADDRESS') ?: '';
$config['crlf'] = "\r\n";
$config['newline'] = "\r\n";

$config['promo_footer_enabled'] = filter_var(getenv('MAIL_PROMO_FOOTER_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN);
$config['promo_footer_html'] = getenv('MAIL_PROMO_FOOTER_HTML') ?: '<hr style="border:none;border-top:1px solid #eee;margin:24px 0 12px;"><p style="font-size:12px;color:#999;font-family:sans-serif;">Bu e-posta <a href="https://kibusiness.co" style="color:#999;">Ki Reservation</a> randevu yönetim sistemi üzerinden gönderilmiştir. Kendi işletmeniz için ücretsiz deneyin: <a href="https://kibusiness.co">kibusiness.co</a></p>';
