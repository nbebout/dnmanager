<?php
require_once('init.php');

sort($config['pricingTLDs']);
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
      <?php setlocale(LC_MONETARY, 'en_US.UTF-8'); 
        foreach ($config['pricingTLDs'] as $tld):
          $enomPrices = $clients['enom']->GetResellerPrice($tld);
          $namecheapPrices = $clients['namecheap']->GetResellerPrice($tld); 
      ?>
        <tr>
          <td><?= $tld; ?></td>
          <td><?= money_format('%.2n', $enomPrices['new']); ?></td>
          <td><?= money_format('%.2n', $enomPrices['renew']); ?></td>
          <td><?= money_format('%.2n', $enomPrices['transfer']); ?></td>
          <td><?= money_format('%.2n', $namecheapPrices['new']); ?></td>
          <td><?= money_format('%.2n', $namecheapPrices['renew']); ?></td>
          <td><?= money_format('%.2n', $namecheapPrices['transfer']); ?></td>
        </tr>
      <?php endforeach; ?>
  </body>
</html>
