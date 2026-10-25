<?php

// To develop against the stub registrar APIs in test-api/ instead of the real
// ones, define TESTING and give each registrar an api_path. See README.md.
// define('TESTING', true);

$config['enom'] = [
    'server' => '',
    'username' => '',
    'password' => ''
];

$config['namecheap'] = [
    'server' => '',
    'username' => '',
    'apikey' => '',
    'clientip' => '' // Public IPv4 address of this server, must be whitelisted in Namecheap's API settings
];

$config['resellerclub'] = [
    'server' => '',
    'username' => '',
    'apikey' => '',
    'customer-id' => ''
];

$config['cloudflare'] = [
    'server' => '', // Optional, defaults to https://api.cloudflare.com
    'account-id' => '',
    'apitoken' => '' // API token with Account > Registrar: Domains (Edit, for lock changes and pricing), Zone > Zone: Read, and Zone > DNS: Read permissions
];

$config['pricingTLDs'] = ['biz', 'cloud', 'co', 'com', 'email', 'family', 'info', 'mba', 'me', 'mobi', 'name', 'net', 'online', 'org', 'site', 'tech', 'us', 'website', 'zone'];
