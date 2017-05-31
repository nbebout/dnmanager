<?php
  include('config.php');
  // URL for API request
  $sld = $_GET['sld'];
  $tld = $_GET['tld'];
  $url = "https://$server/interface.asp?command=GetDnsSec&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld";
  // Load the API results into a SimpleXML object
  $xml = simplexml_load_file($url);
?>
<html>
 <head>
  <title>Domain Name Manager</title>
 </head>
 <body>
  <h1>Domain Name Manager</h1>
  <h3>DNSSEC records for <?php echo "$sld.$tld"; ?></h3>
  <table>
   <tr>
    <td>Key Tag</td>
    <td>Algorithm</td>
    <td>Digest Type</td>
    <td>Digest</td>
    <td>Show DNSSEC Records</td>
   </tr>
<?php
$keylist = $xml->DnsSecData->KeyData;

foreach ($keylist as $key) {
  echo "<tr><td>$key->KeyTag</td><td>$key->Algorithm</td><td>$key->DigestType</td><td>$key->Digest</td><td><a href=\"deleteDNSSEC.php?sld=$sld&tld=$tld\">Show DNSSEC</a></td></tr>";
}
?>
  </table>
  <br />
  <a href="listDomains.php">Return to Domain List</a>
 </body>
</html>

