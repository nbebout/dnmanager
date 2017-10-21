<?php
require_once('init.php');

// $_REQUEST contains $_POST, $_GET, and $_COOKIE
$sld = urlencode($_REQUEST['sld']);
$tld = urlencode($_REQUEST['tld']);

if ($_POST['submit']) {
  // URL for API request
  $keytag = $_POST['keytag'];
  $algorithm = $_POST['algorithm'];
  $digesttype = $_POST['digesttype'];
  $digest = $_POST['digest'];

  $enomClient->AddDnsSec($sld, $tld, $keytag, intval($algorithm), $digesttype, $digest);
}

// Load the API results into a SimpleXML object
$xml = $enomClient->GetDnsSec($sld, $tld);
$keylist = $xml->DnsSecData->KeyData;
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

    <div style="display: none;" id="add-record-form">
      <h3>Add DNSSEC record for <?php echo "$sld.$tld"; ?></h3>

      <form action="manageDNSSEC.php" method="post">
        <table>
          <tr>
            <td>Key Tag:</td>
            <td><input type="text" name="keytag"></td>
          </tr>
          <tr>
            <td>Algorithm:</td>
            <td><input type="text" name="algorithm"></td>
          </tr>
          <tr>
            <td>Digest Type:</td>
            <td><input type="text" name="digesttype"></td>
          </tr>
          <tr>
            <td>Digest:</td>
            <td><input type="text" name="digest"></td>
          </tr>
        </table>
        <br />
        <input type="hidden" name="sld" value="<?= $sld ?>">
        <input type="hidden" name="tld" value="<?= $tld ?>">
        <input type="submit" name="submit" value="Add">
      </form>
    </div>

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
    <br>

    <a href="#" id="add-record-link">Add DNSSEC record</a><br />
    <br />
    <a href="index.php">Return to Domain List</a>
  </body>

  <script type="text/javascript">
    document.getElementById('add-record-link').addEventListener('click', function() {
      document.getElementById('add-record-form').style.display = 'block';
    });
  </script>
</html>
