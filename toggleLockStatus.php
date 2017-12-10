<?php
require_once('init.php');

// $_REQUEST contains $_POST, $_GET, and $_COOKIE
$domain = $_REQUEST['domain'];
$registrar = $_REQUEST['registrar'];

if ($registrar == 'enom') {
  $clients['enom']->ToggleLocked($domain);
} else if ($registrar == 'namecheap') {
  $clients['namecheap']->ToggleLocked($domain);
}
header("Location: index.php");

