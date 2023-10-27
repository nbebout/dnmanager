<?php
require_once('init.php');

sort($config['pricingTLDs']);

setlocale(LC_MONETARY, 'en_US.UTF-8');
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$prices = [];

foreach ($clients as $registrar => $client) {
  $prices[$registrar] = $client->GetAllPrices($config['pricingTLDs']);
}
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

        <?php foreach ($prices as $registrar => $_price_data): ?>
        <th><?= $registrar ?> Register</th>
        <th><?= $registrar ?> Renew</th>
        <th><?= $registrar ?> Transfer</th>
        <?php endforeach; ?>
      </tr>

      <?php foreach ($config['pricingTLDs'] as $tld): ?>
      <tr>
        <td><?= $tld; ?></td>
        <?php foreach ($prices as $price_data): ?>
        <td><?= $fmt->formatCurrency($price_data[$tld]['new'], "USD"); ?></td>
        <td><?= $fmt->formatCurrency($price_data[$tld]['renew'], "USD"); ?></td>
        <td><?= $fmt->formatCurrency($price_data[$tld]['transfer'], "USD"); ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
  </body>
</html>
