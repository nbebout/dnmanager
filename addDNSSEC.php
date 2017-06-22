<?php
include('config.php');
if ($_POST['submit'])
{
  // URL for API request
  $sld = $_POST['sld'];
  $tld = $_POST['tld'];
  $keytag = $_POST['keytag'];
  $algorithm = $_POST['algorithm'];
  $digesttype = $_POST['digesttype'];
  $digest = $_POST['digest'];
  $url = "https://$server/interface.asp?command=AddDnsSec&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld&keytag=$keytag&alg=$algorithm&digesttype=$digesttype&digest=$digest";
  // Load the API results into a SimpleXML object
  $xml2 = simplexml_load_file($url);
}
if (!$_POST['submit'])
{
$sld = $_GET['sld'];
$tld = $_GET['tld'];
}
  // URL for API request
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
  <h3>Add DNSSEC record for <?php echo "$sld.$tld"; ?></h3>
  <form action="addDNSSEC.php" method="post">
   Key Tag: <input type="text" name="keytag" /><br />
   Algorithm: <input type="text" name="algorithm" /><br />
   Digest Type: <input type="text" name="digesttype" /><br />
   Digest: <input type="text" name="digest" /><br />
   <input type="hidden" name="sld" value="<?= "$sld" ?>" />
   <input type="hidden" name="tld" value="<?= "$tld" ?>" />
   <input type="submit" name="submit" value="Add" />
  </form>
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
