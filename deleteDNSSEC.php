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

try {
    if (!$clients[$registrar]->DeleteDnsSec($sld, $tld, $keytag, $algorithm, $digesttype, $digest)) {
        setFlashError("Failed to delete the DNSSEC record for $sld.$tld");
    }
} catch (Throwable $e) {
    setFlashError("Failed to delete the DNSSEC record for $sld.$tld: " . $e->getMessage());
}

redirectTo('manageDNSSEC.php', ['sld' => $sld, 'tld' => $tld, 'registrar' => $registrar]);
