<?php
require_once('init.php');

requirePostRequest();
requireValidCsrfToken();

$sld = requireValidDomainPart($_POST['sld'] ?? null, 'sld');
$tld = requireValidTld($_POST['tld'] ?? null);
$registrar = requireValidRegistrar($_POST['registrar'] ?? '');
[$keytag, $algorithm, $digesttype, $digest] = requireValidDnssecInput(
    $_POST['keytag'] ?? null,
    $_POST['algorithm'] ?? null,
    $_POST['digesttype'] ?? null,
    isset($_POST['digest']) && is_string($_POST['digest']) ? str_replace(' ', '', $_POST['digest']) : null
);

if (!$clients[$registrar]->SupportsDnsSec()) {
    rejectInvalidInput('Registrar does not support DNSSEC');
}

$clients[$registrar]->DeleteDnsSec($sld, $tld, $keytag, $algorithm, $digesttype, $digest);

// The client call encodes these values, encode here for our own use so they're not double encoded
$sld = urlencode($sld);
$tld = urlencode($tld);
$registrar = urlencode($registrar);
header("Location: manageDNSSEC.php?sld=$sld&tld=$tld&registrar=$registrar");
