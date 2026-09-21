<?php
require_once('init.php');

requirePostRequest();
requireValidCsrfToken();

$domain = requireValidDomain($_POST['domain'] ?? null);
$registrar = requireValidRegistrar($_POST['registrar'] ?? '');

if (!$clients[$registrar]->SupportsToggleLocked()) {
    rejectInvalidInput('Registrar does not support lock changes');
}

$clients[$registrar]->ToggleLocked($domain);

header("Location: index.php");
