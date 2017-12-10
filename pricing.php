<?php
require_once('init.php');

$tldarray = ['biz', 'cloud', 'co', 'com', 'email', 'family', 'info', 'mba', 'me', 'mobi', 'name', 'net', 'online', 'org', 'site', 'tech', 'us', 'website', 'zone'];
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
    <h1>Domain Pricing</h1>
    <table>
      <tr>
        <th>TLD</th>
        <th>eNom Register</th>
        <th>eNom Renew</th>
        <th>eNom Transfer</th>
        <th>NameCheap Register</th>
        <th>NameCheap Renew</th>
        <th>NameCheap Transfer</th>
      </tr>
      <?php setlocale(LC_MONETARY, 'en_US.UTF-8'); foreach ($tldarray as $tld): ?>
        <tr>
          <td><?= $tld; ?></td>
          <td><?= money_format('%.2n', $clients['enom']->GetResellerPrice('new', $tld)); ?></td>
          <td><?= money_format('%.2n', $clients['enom']->GetResellerPrice('renew', $tld)); ?></td>
          <td><?= money_format('%.2n', $clients['enom']->GetResellerPrice('transfer', $tld)); ?></td>
          <td><?= money_format('%.2n', $clients['namecheap']->GetResellerPrice('REGISTER', $tld)); ?></td>
          <td><?= money_format('%.2n', $clients['namecheap']->GetResellerPrice('RENEW', $tld)); ?></td>
          <td><?= money_format('%.2n', $clients['namecheap']->GetResellerPrice('TRANSFER', $tld)); ?></td>
        </tr>
      <?php endforeach; ?>
  </body>
</html>
