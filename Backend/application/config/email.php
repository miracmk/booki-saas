<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add custom values by settings them to the  array.
// Example: ['smtp_host'] = 'smtp.gmail.com';
// @link https://codeigniter.com/user_guide/libraries/email.html

$config['useragent'] = 'BooKi';
$config['protocol'] = 'mail'; // or 'smtp'
$config['mailtype'] = 'html'; // or 'text'
$config['smtp_debug'] = '0'; // or '1'
$config['smtp_auth'] = 0; //or FALSE for anonymous relay.
$config['smtp_host'] = '';
$config['smtp_user'] = '';
$config['smtp_pass'] = '';
$config['smtp_crypto'] = 'tls'; // or 'tls'
$config['smtp_port'] = 587;
$config['from_name'] = 'BooKi Demo';
$config['from_address'] = 'no-reply@demobookiapp.kibusiness.co';
$config['reply_to'] = 'no-reply@demobookiapp.kibusiness.co';
$config['crlf'] = "\r\n";
$config['newline'] = "\r\n";
