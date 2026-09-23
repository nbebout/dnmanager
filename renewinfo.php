<?php
require_once('init.php');

$domains = [];
foreach ($clients as $client) {
  $clientDomains = $client->GetAllDomains();
  $domains = array_merge($domains, $clientDomains);
}

// Cache eNom's renewal pricing for 15 minutes rather than fetching it live on
// every page load - GetAllRenewalPrices() makes one outbound API call per
// configured TLD, which is slow and, combined across pages, a common cause
// of a fronting proxy/load balancer returning a gateway timeout.
$enomPrices = [];
if (isset($clients['enom'])) {
  $enomPrices = cacheRemember('renewal_prices_enom_' . md5(implode(',', $config['pricingTLDs'])), 900, function () use ($clients, $config) {
    return $clients['enom']->GetAllRenewalPrices($config['pricingTLDs']);
  });
}

switch ($_REQUEST['sortBy'] ?? 'expires') {
  case "domain":
    sortDomainsByName($domains);
    break;
  case "expires":
  default:
    sortDomainsByExpires($domains);
    break;
}
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

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
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
      <th><a href="renewinfo.php?sortBy=domain">Domain Name</a></th>
      <th>Registrar</th>
      <th><a href="renewinfo.php?sortBy=expires">Expiration Date</a></th>
      <th>Renewal Cost</th>
      <th>Locked</th>
      <th>DNSSEC</th>
          <th>Nameservers</th>
        </tr>
      </thead>
      <tbody>
    <?php foreach ($domains as $domain) : ?>
      <?php [$sld, $tld] = splitDomain($domain->name); ?>
      <?php $registrarKey = strtolower($domain->registrar); ?>
      <tr>
        <td><?= h($domain->name) ?></td>
        <td><?= h($domain->registrar) ?></td>
        <td><?= h(explode(' ', $domain->expires, 2)[0]) ?></td>
        <td><?= isset($enomPrices[$tld]['renew']) ? h($fmt->formatCurrency($enomPrices[$tld]['renew'], 'USD')) : 'N/A' ?></td>
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
            <a href="<?= buildUrl('manageDNSSEC.php', ['sld' => $sld, 'tld' => $tld, 'registrar' => $registrarKey]) ?>">Edit</a>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($clients[$registrarKey]->SupportsNameservers()) : ?>
            <a href="<?= buildUrl('manageDNS.php', ['sld' => $sld, 'tld' => $tld, 'registrar' => $registrarKey]) ?>">Edit</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

</html>
