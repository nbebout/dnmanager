<?php
require_once('init.php');

// $_REQUEST contains $_POST, $_GET, and $_COOKIE
$domain = $_REQUEST['domain'];
$registrar = $_REQUEST['registrar'];

$clients[$registrar]->ToggleLocked($domain);

header("Location: index.php");

