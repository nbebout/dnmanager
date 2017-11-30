<?php
require_once('config.php');
require_once('enomClient.php');
require_once('namecheapClient.php');

$enomClient = new EnomClient($server, $username, $password);
$namecheapClient = new NameCheapClient($ncserver, $ncusername, $ncapikey);

if (defined('TESTING') && isset($apiPath)) {
    $enomClient->SetApiPath($apiPath);
}
