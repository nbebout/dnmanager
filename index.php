<?php
require_once('init.php');

$domains = [];
foreach ($clients as $client) {
  $clientDomains = $client->GetAllDomains();
  $domains = array_merge($domains, $clientDomains);
}

if (!isset($_REQUEST['sortBy'])) {
  $_REQUEST['sortBy'] = 'domain';
}

switch ($_REQUEST['sortBy']) {
  case "expires":
    sortDomainsByExpires($domains);
    break;
  case "domain":
  default:
    sortDomainsByName($domains);
    break;
}

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
  </style>
</head>

<body>
  <h1>Domain Name Manager</h1>
  <table>
    <tr>
      <th><a href="index.php?sortBy=domain">Domain Name</a></th>
      <th>Registrar</th>
      <th><a href="index.php?sortBy=expires">Expiration Date</a></th>
      <th>Locked</th>
      <th>DNSSEC</th>
      <th>Nameservers</th>
    </tr>
    <?php foreach ($domains as $domain) : ?>
      <?php $split = explode('.', $domain->name); ?>
          <?php $registrarKey = strtolower($domain->registrar); ?>
          <tr>
            <td><?= h($domain->name) ?></td>
            <td><?= h($domain->registrar) ?></td>
            <td><?= h(explode(' ', $domain->expires, 2)[0]) ?></td>
            <td>
              <?php if ($clients[$registrarKey]->SupportsToggleLocked()) : ?>
                <a href="<?= buildUrl('toggleLockStatus.php', ['domain' => $domain->name, 'registrar' => $registrarKey]) ?>">
                  <?= $domain->locked ? 'Yes' : 'No' ?>
                </a>
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
