<?php
require_once('init.php');

$xml = $enomClient->GetAllDomains();
$ncxml = $namecheapClient->GetAllDomains();
$domainlist = $xml->GetAllDomains->DomainDetail;
$ncdomainlist = $ncxml->CommandResponse->DomainGetListResult->Domain;
$domains = array();
foreach ($domainlist as $domain):
  $domains[] = array('domain' => $domain->DomainName, 'expiry' => $domain->{'expiration-date'}, 'islocked' => $domain->lockstatus, 'registrar' => 'eNom');
endforeach;
foreach ($ncdomainlist as $domain):
  $domains[] = array('domain' => $domain->attributes()->Name, 'expiry' => $domain->attributes()->Expires, 'islocked' => $domain->attributes()->IsLocked, 'registrar' => 'NameCheap');
endforeach;

$sorteddomains = array_msort($domains, array('domain'=>SORT_ASC));
$domains = $sorteddomains;

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
<!--        <th>Lock Status</th> -->
        <th>DNSSEC</th>
        <th>Nameservers</th>
      </tr>
      <?php foreach ($domains as $domain): ?>
      <?php $split = explode('.', $domain[domain]); ?>
      <tr>
        <td><?= $domain[domain] ?></td>
        <td><?= $domain[registrar] ?></td>
        <td><?= strtok($domain[expiry], ' ') ?></td>
<!--       <td><?= $domain[islocked] ?></td> -->
        <td><a href="manageDNSSEC.php?sld=<?= $split[0] ?>&tld=<?= $split[1] ?>">Edit</a></td>
        <td><a href="manageDNS.php?sld=<?= $split[0] ?>&tld=<?= $split[1] ?>&registrar=<?= strtolower($domain[registrar]); ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
  </body>
</html>
