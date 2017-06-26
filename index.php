<?php
  include('config.php');

  // URL for API request
  $url =  "https://$server/interface.asp?command=GetAllDomains&responsetype=xml&uid=$username&pw=$password";

  // Load the API results into a SimpleXML object
  $xml = simplexml_load_file($url);
  $domainlist = $xml->GetAllDomains->DomainDetail;
/*
  // Read the results
  $rrpCode = $xml->RRPCode;
  $rrpText = $xml->RRPText;

  // Perform actions based on results
  switch ($rrpCode) {
    case 210:
	  echo "Domain available";
	  break;
	case 211:
	  echo "Domain not available";
	  break;
	default:
	  echo $rrpCode . ' ' . $rrpText;
      break;
  }
*/
?>

<html>
  <head>
    <title>Domain Name Manager</title>
  </head>

  <body>
    <h1>Domain Name Manager</h1>
    <table>
      <tr>
        <th>Domain Name</th>
        <th>Expiration Date</th>
        <th>Lock Status</th>
        <th>Show DNSSEC Records</th>
      </tr>

      <?php foreach ($domainlist as $domain): ?>
      <?php $split = explode('.', $domain->DomainName); ?>
      <tr>
        <td><?= $domain->DomainName ?></td>
        <td><?= $doamin->{'expiration-date'} ?></td>
        <td><?= $domain->lockstatus ?></td>
        <td><a href="manageDNSSEC.php?sld=<?= $split[0] ?>&tld=<?= $split[1] ?>">Show DNSSEC</a></td>
      </tr>
      <?php endforeach; ?>
  </body>
</html>
