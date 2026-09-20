<?php
require_once('init.php');

requirePostRequest();
requireValidCsrfToken();

$sld = $_POST['sld'];
$tld = $_POST['tld'];
$keytag = $_POST['keytag'];
$algorithm = $_POST['algorithm'];
$digesttype = $_POST['digesttype'];
$digest = $_POST['digest'];
$registrar = $_POST['registrar'];

if (isset($clients[$registrar])) {
    $nslist = $clients[$registrar]->DeleteDnsSec($sld, $tld, $keytag, intval($algorithm), $digesttype, $digest);
}

// The client call encodes these values, encode here for our own use so they're not double encoded
$sld = urlencode($sld);
$tld = urlencode($tld);
$registrar = urlencode($registrar);
header("Location: manageDNSSEC.php?sld=$sld&tld=$tld&registrar=$registrar");
