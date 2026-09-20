<?php
require_once('init.php');

requirePostRequest();
requireValidCsrfToken();

$domain = $_POST['domain'];
$registrar = $_POST['registrar'];

if (isset($clients[$registrar])) {
  $clients[$registrar]->ToggleLocked($domain);
}

header("Location: index.php");
