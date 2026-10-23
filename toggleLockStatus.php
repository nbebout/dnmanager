<?php
require_once('init.php');

requirePostRequest();
requireValidCsrfToken();

$domain = requireValidDomain($_POST['domain'] ?? null);
$registrar = requireValidRegistrar($_POST['registrar'] ?? '');

if (!$clients[$registrar]->SupportsToggleLocked()) {
    rejectInvalidInput('Registrar does not support lock changes');
}

try {
    if (!$clients[$registrar]->ToggleLocked($domain)) {
        setFlashError("Failed to change the lock status of $domain");
    }
} catch (Throwable $e) {
    setFlashError("Failed to change the lock status of $domain: " . $e->getMessage());
}

header("Location: index.php");
