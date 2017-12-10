<?php
require_once('init.php');

$tldarray = [com, net, org, mba, biz, cloud, info, mobi, name, us, zone, email, family];
sort($tldarray);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Domain Name Manager</title>

    <style>
      table, th, td {
        border: 1px solid black;
        padding: 4px;
      }
      body { font-family: sans-serif; }
    </style>
  </head>

  <body>
    <h1>Domain Pricing - eNom</h1>
    <table>
      <tr>
        <th>TLD</th>
        <th>Register</th>
        <th>Renew</th>
        <th>Transfer</th>
      </tr>
      <?php foreach ($tldarray as $tld): ?>
        <tr>
          <td><?= $tld; ?></td>
          <td>$<?= $clients['enom']->GetResellerPrice('new', $tld); ?></td>
          <td>$<?= $clients['enom']->GetResellerPrice('renew', $tld); ?></td>
          <td>$<?= $clients['enom']->GetResellerPrice('transfer', $tld); ?></td>
        </tr>
      <?php endforeach; ?>
  </body>
</html>
