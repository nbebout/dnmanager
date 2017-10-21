<?php
require_once('config.php');
require_once('enomClient.php');

$enomClient = new EnomClient($server, $username, $password);

if (defined('TESTING') && isset($apiPath)) {
    $enomClient->SetApiPath($apiPath);
}