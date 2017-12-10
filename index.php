<?php
require_once('init.php');

$domains = [];
foreach ($clients as $client) {
  $clientDomains = $client->GetAllDomains();
  $domains = array_merge($domains, $clientDomains);
}

sortDomains($domains);
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
      a, a:link, a:visited, a:hover { color: black; }
    </style>
  </head>

  <body>
    <h1>Domain Name Manager</h1>
    <table>
      <tr>
        <th>Domain Name</th>
        <th>Registrar</th>
        <th>Expiration Date</th>
        <th>Locked</th>
        <th>DNSSEC</th>
        <th>Nameservers</th>
      </tr>
      <?php foreach ($domains as $domain): ?>
      <?php $split = explode('.', $domain->name); ?>
      <tr>
        <td><?= $domain->name ?></td>
        <td><?= $domain->registrar ?></td>
        <td><?= explode(' ', $domain->expires, 2)[0] ?></td>
        <td><?= $domain->locked ? 'Yes' : 'No' ?></td>
        <td>
          <?php if ($clients[strtolower($domain->registrar)]->SupportsDnsSec()): ?>
            <a href="manageDNSSEC.php?sld=<?= $split[0] ?>&tld=<?= $split[1] ?>&registrar=<?= strtolower($domain->registrar); ?>">Edit</a>
          <?php endif; ?>
        </td>
        <td><a href="manageDNS.php?sld=<?= $split[0] ?>&tld=<?= $split[1] ?>&registrar=<?= strtolower($domain->registrar); ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
  </body>
</html>
