<?php
  include('config.php');
  // URL for API request
  $sld = urlencode($_GET['sld']);
  $tld = urlencode($_GET['tld']);
  $url = "https://$server/interface.asp?command=GetDnsSec&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld";
  // Load the API results into a SimpleXML object
  $xml = simplexml_load_file($url);
  $keylist = $xml->DnsSecData->KeyData;
?>
<html>
  <head>
    <title>Domain Name Manager</title>
  </head>
  <body>
    <h1>Domain Name Manager</h1>
    <h3>DNSSEC records for <?= "$sld.$tld" ?></h3>
    <table>
      <tr>
        <th>Key Tag</th>
        <th>Algorithm</th>
        <th>Digest Type</th>
        <th>Digest</th>
        <th>Delete</th>
      </tr>

      <?php foreach ($keylist as $key): ?>
      <tr>
        <td><?= $key->KeyTag ?></td>
        <td><?= $key->Algorithm ?></td>
        <td><?= $key->DigestType ?></td>
        <td><?= $key->Digest ?></td>
        <td><a href="deleteDNSSEC.php?sld=<?= $sld ?>&tld=<?= $tld ?>&keytag=<?= $key->KeyTag ?>&algorithm=<?= $key->Algorithm ?>&digesttype=<?= $key->DigestType ?>&digest=<?= $key->Digest ?>">Delete</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <br />
    <a href="listDomains.php">Return to Domain List</a>
  </body>
</html>
