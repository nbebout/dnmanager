<?php
require_once('init.php');

$domains = [];
foreach ($clients as $client) {
  $clientDomains = $client->GetAllDomains();
  $domains = array_merge($domains, $clientDomains);
}
$enomPrices = $clients['enom']->GetAllRenewalPrices($config['pricingTLDs']);

switch ($_REQUEST['sortBy']) {
  case "domain":
    sortDomainsByName($domains);
    break;
  case "expires":
  default:
    sortDomainsByExpires($domains);
    break;
}
setlocale(LC_MONETARY, 'en_US.UTF-8');
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

?>
<!DOCTYPE html>
<html>

<head>
  <title>Domain Name Manager</title>

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

    a,
    a:link,
    a:visited,
    a:hover {
      color: black;
    }

    .inline-action-form {
      display: inline;
      margin: 0;
    }

    .link-button {
      background: none;
      border: none;
      color: black;
      cursor: pointer;
      font: inherit;
      padding: 0;
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <h1>Domain Name Manager</h1>
  <table>
    <tr>
      <th><a href="renewinfo.php?sortBy=domain">Domain Name</a></th>
      <th>Registrar</th>
      <th><a href="renewinfo.php?sortBy=expires">Expiration Date</a></th>
      <th>Renewal Cost</th>
      <th>Locked</th>
      <th>DNSSEC</th>
      <th>Nameservers</th>
    </tr>
    <?php foreach ($domains as $domain) : ?>
      <?php $split = explode('.', $domain->name);
      $tld = $split[1]; ?>
      <?php $registrarKey = strtolower($domain->registrar); ?>
      <tr>
        <td><?= h($domain->name) ?></td>
        <td><?= h($domain->registrar) ?></td>
        <td><?= h(explode(' ', $domain->expires, 2)[0]) ?></td>
        <td><?= h($fmt->formatCurrency($enomPrices[$tld]['renew'], 'USD')) ?></td>
        <td>
          <?php if ($clients[$registrarKey]->SupportsToggleLocked()) : ?>
            <form action="toggleLockStatus.php" method="post" class="inline-action-form">
              <input type="hidden" name="domain" value="<?= h($domain->name) ?>">
              <input type="hidden" name="registrar" value="<?= h($registrarKey) ?>">
              <?= csrfInput() ?>
              <button type="submit" class="link-button"><?= $domain->locked ? 'Yes' : 'No' ?></button>
            </form>
          <?php else : ?>
            <?= $domain->locked ? 'Yes' : 'No' ?>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($clients[$registrarKey]->SupportsDnsSec()) : ?>
            <a href="<?= buildUrl('manageDNSSEC.php', ['sld' => $split[0], 'tld' => $split[1], 'registrar' => $registrarKey]) ?>">Edit</a>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($clients[$registrarKey]->SupportsNameservers()) : ?>
            <a href="<?= buildUrl('manageDNS.php', ['sld' => $split[0], 'tld' => $split[1], 'registrar' => $registrarKey]) ?>">Edit</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
</body>

</html>
