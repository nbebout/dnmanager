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
        <th>ResellerClub Register</th>
        <th>ResellerClub Renew</th>
        <th>ResellerClub Transfer</th>
      </tr>
      <?php setlocale(LC_MONETARY, 'en_US.UTF-8'); 
          $enomPrices = $clients['enom']->GetAllPrices($config['pricingTLDs']);
          $namecheapPrices = $clients['namecheap']->GetAllPrices($config['pricingTLDs']);
          $resellerclubPrices = $clients['resellerclub']->GetAllPrices($config['pricingTLDs']);
        foreach ($config['pricingTLDs'] as $tld):
      ?>
        <tr>
          <td><?= $tld; ?></td>
          <td><?= money_format('%.2n', $enomPrices[$tld]['new']); ?></td>
          <td><?= money_format('%.2n', $enomPrices[$tld]['renew']); ?></td>
          <td><?= money_format('%.2n', $enomPrices[$tld]['transfer']); ?></td>
          <td><?= money_format('%.2n', $namecheapPrices[$tld]['new']); ?></td>
          <td><?= money_format('%.2n', $namecheapPrices[$tld]['renew']); ?></td>
          <td><?= money_format('%.2n', $namecheapPrices[$tld]['transfer']); ?></td>
          <td><?= money_format('%.2n', $resellerclubPrices[$tld]['new']); ?></td>
          <td><?= money_format('%.2n', $resellerclubPrices[$tld]['renew']); ?></td>
          <td><?= money_format('%.2n', $resellerclubPrices[$tld]['transfer']); ?></td>
        </tr>
      <?php endforeach; ?>
  </body>
</html>
