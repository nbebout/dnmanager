<?php
require_once('init.php');

$sld = $_GET['sld'];
$tld = $_GET['tld'];
$keytag = $_GET['keytag'];
$algorithm = $_GET['algorithm'];
$digesttype = $_GET['digesttype'];
$digest = $_GET['digest'];
$registrar = $_REQUEST['registrar'];

if (isset($clients[$registrar])) {
    $nslist = $clients[$registrar]->DeleteDnsSec($sld, $tld, $keytag, intval($algorithm), $digesttype, $digest);
}

// The client call encodes these values, encode here for our own use so they're not double encoded
$sld = urlencode($sld);
$tld = urlencode($tld);
$registrar = urlencode($registrar);
header("Location: manageDNSSEC.php?sld=$sld&tld=$tld&registrar=$registrar");
