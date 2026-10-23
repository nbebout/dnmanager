<?php
require_once('init.php');

sort($config['pricingTLDs']);

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$prices = [];
$errors = $clientErrors;

// A registrar whose pricing can't be loaded still gets its columns, showing N/A.
foreach ($clients as $registrarKey => $client) {
  try {
    $prices[$client->GetRegistrarName()] = getRegistrarPrices($registrarKey, $client, $config['pricingTLDs']);
  } catch (Throwable $e) {
    $prices[$client->GetRegistrarName()] = [];
    $errors[] = $client->GetRegistrarName() . ': failed to load prices: ' . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Domain Name Manager</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <h1>Domain Pricing</h1>
  <?php foreach ($errors as $error) : ?>
    <p class="error"><?= h($error) ?></p>
  <?php endforeach; ?>
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
