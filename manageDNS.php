<?php
  include('config.php');

  $sld = urlencode($_GET['sld']);
  $tld = urlencode($_GET['tld']);

  // URL for API request
  $url = "https://$server/interface.asp?command=GetDns&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld";

  // Load the API results into a SimpleXML object
  $xml = simplexml_load_file($url);
  $nslist = $xml->dns;
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Domain Name Manager</title>
  </head>

  <body>
    <h1>Domain Name Manager</h1>

    <h3>Nameservers for <?= "$sld.$tld" ?></h3>
    <table>

      <?php foreach ($nslist as $ns): ?>
      <tr>
        <td><?= $ns ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <br>

    <br />
    <a href="index.php">Return to Domain List</a>
  </body>
</html>
