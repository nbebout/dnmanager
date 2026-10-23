<?php
require_once('init.php');

requirePostRequest();
requireValidCsrfToken();

$domain = requireValidDomain($_POST['domain'] ?? null);
$registrar = requireValidRegistrar($_POST['registrar'] ?? '');
// The form sends the state to change to, rather than asking for a toggle, so
// a double submit or a stale page can't flip the lock the wrong way.
$locked = $_POST['locked'] ?? null;
if ($locked !== '0' && $locked !== '1') {
    rejectInvalidInput('Invalid lock status');
}
$locked = $locked === '1';
$action = $locked ? 'lock' : 'unlock';

if (!$clients[$registrar]->SupportsToggleLocked()) {
    rejectInvalidInput('Registrar does not support lock changes');
}

try {
    if (!$clients[$registrar]->SetLocked($domain, $locked)) {
        setFlashError("Failed to $action $domain");
    }
} catch (Throwable $e) {
    setFlashError("Failed to $action $domain: " . $e->getMessage());
}

redirectTo('index.php');
