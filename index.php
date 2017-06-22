<?php
  include('config.php');
  // URL for API request
  $url =  "https://$server/interface.asp?command=GetAllDomains&responsetype=xml&uid=$username&pw=$password";
  // Load the API results into a SimpleXML object
  $xml = simplexml_load_file($url);
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
    <td>Domain Name</td>
    <td>Expiration Date</td>
    <td>Lock Status</td>
    <td>Show DNSSEC Records</td>
   </tr>
<?php
$domainlist = $xml->GetAllDomains->DomainDetail;
foreach ($domainlist as $domain) {
  $expire = $domain->{'expiration-date'};
  $split = explode('.', $domain->DomainName);
  echo "<tr><td>$domain->DomainName</td><td>$expire</td><td>$domain->lockstatus</td><td><a href=\"showDNSSEC.php?sld=$split[0]&tld=$split[1]\">Show DNSSEC</a></td></tr>";
}
?>
