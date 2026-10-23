<?php
require_once('init.php');

sort($config['pricingTLDs']);

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$prices = [];

foreach ($clients as $registrarKey => $client) {
  $prices[$client->GetRegistrarName()] = getRegistrarPrices($registrarKey, $client, $config['pricingTLDs']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Domain Name Manager</title>
  <link rel="stylesheet" href="styles.css">

  <style>
    table,
    th,
    td {
      border: 1px solid black;
      padding: 4px;
    }

    body {
      font-family: sans-serif;
    }
  </style>
</head>

<body>
  <h1>Domain Pricing</h1>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
      <th>TLD</th>

      <?php foreach ($prices as $registrar => $_price_data) : ?>
        <th><?= h($registrar) ?> Register</th>
        <th><?= h($registrar) ?> Renew</th>
        <th><?= h($registrar) ?> Transfer</th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>

    <?php foreach ($config['pricingTLDs'] as $tld) : ?>
      <tr>
        <td><?= h($tld); ?></td>
        <?php foreach ($prices as $price_data) : ?>
          <?php foreach (['new', 'renew', 'transfer'] as $priceType) : ?>
            <td>
              <?php if (isset($price_data[$tld][$priceType])) : ?>
                <?= h($fmt->formatCurrency($price_data[$tld][$priceType], "USD")) ?>
              <?php else : ?>
                N/A
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

</html>
