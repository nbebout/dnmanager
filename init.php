<?php
$config = [];
require_once('config.php');
require_once('classes/init.php');

$clients = [];

// Setup enom client
if (!empty($config['enom']['server'])) {
    $enomClient = new EnomClient($config['enom']['server'], $config['enom']['username'], $config['enom']['password']);
    if (defined('TESTING') && isset($config['enom']['api_path'])) {
        $enomClient->SetApiPath($config['enom']['api_path']);
    }
    $clients['enom'] = $enomClient;
}

// Setup Namecheap client
if (!empty($config['namecheap']['server'])) {
    $namecheapClient = new NameCheapClient($config['namecheap']['server'], $config['namecheap']['username'], $config['namecheap']['apikey']);
    if (defined('TESTING') && isset($config['namecheap']['api_path'])) {
        $namecheapClient->SetApiPath($config['namecheap']['api_path']);
    }
    $clients['namecheap'] = $namecheapClient;
}

// Setup ResellerClub client
if (!empty($config['resellerclub']['server'])) {
    $resellerclubClient = new ResellerClubClient($config['resellerclub']['server'], $config['resellerclub']['username'], $config['resellerclub']['apikey']);
    if (defined('TESTING') && isset($config['resellerclub']['api_path'])) {
        $resellerclubClient->SetApiPath($config['resellerclub']['api_path']);
    }
    $clients['resellerclub'] = $resellerclubClient;
}

function sortDomainsByName(array &$domains) {
    usort($domains, function($a, $b): int {
       return strcmp($a->name, $b->name);
    });
}

function sortDomainsByExpires(array &$domains) {
    usort($domains, function($a, $b): int {
       return (strtotime($a->expires) < strtotime($b->expires)) ? -1 : 1;
    });
}
