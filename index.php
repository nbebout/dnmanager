<?php
require_once('init.php');

$xml = $enomClient->GetAllDomains();
$ncxml = $namecheapClient->GetAllDomains();
$domainlist = $xml->GetAllDomains->DomainDetail;
$ncdomainlist = $ncxml->CommandResponse->DomainGetListResult->Domain;
var_dump($ncdomainlist);
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
        <th>Lock Status</th>
        <th>DNSSEC</th>
        <th>Nameservers</th>
      </tr>
      <?php foreach ($domainlist as $domain): ?>
      <?php $split = explode('.', $domain->DomainName); ?>
      <tr>
        <td><?= $domain->DomainName ?></td>
        <td>eNom</td>
        <td><?= $domain->{'expiration-date'} ?></td>
        <td><?= $domain->lockstatus ?></td>
        <td><a href="manageDNSSEC.php?sld=<?= $split[0] ?>&tld=<?= $split[1] ?>">Edit</a></td>
        <td><a href="manageDNS.php?sld=<?= $split[0] ?>&tld=<?= $split[1] ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
      <?php foreach ($ncdomainlist as $domain): ?>
      <tr>
        <td><?= $domain->attributes()->Name ?></td>
        <td>NameCheap</td>
        <td><?= $domain->attributes()->Expires ?></td>
        <td><?= $domain->attributes()->IsLocked ?></td>
        <td></td>
        <td></td>
      </tr>
      <?php endforeach; ?>
  </body>
</html>
