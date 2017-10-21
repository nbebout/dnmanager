<?php
require_once('init.php');

$tldarray = [com, net, org, mba, biz, cloud, info, mobi, name, us, zone, email, family];
sort($tldarray);
?>

<html>
  <head>
    <title>Domain Name Manager</title>
  </head>

  <body>
    <h1>Domain Name Manager</h1>
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
          <td>$<?= $enomClient->GetResellerPrice('new', $tld)->productprice->price; ?></td>
          <td>$<?= $enomClient->GetResellerPrice('renew', $tld)->productprice->price; ?></td>
          <td>$<?= $enomClient->GetResellerPrice('transfer', $tld)->productprice->price; ?></td>
        </tr>
      <?php endforeach; ?>
  </body>
</html>
