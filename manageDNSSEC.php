<?php
require_once('init.php');

// $_REQUEST contains $_POST, $_GET, and $_COOKIE
$sld = $_REQUEST['sld'];
$tld = $_REQUEST['tld'];
$registrar = $_REQUEST['registrar'];

if (isset($_POST['submit'])) {
  // URL for API request
  $keytag = $_POST['keytag'];
  $algorithm = $_POST['algorithm'];
  $digesttype = $_POST['digesttype'];
  $digest = str_replace(' ', '', $_POST['digest']);

  if (isset($clients[$registrar])) {
    $keylist = $clients[$registrar]->AddDnsSec($sld, $tld, $keytag, intval($algorithm), $digesttype, $digest);
  }
}


$keylist = [];
if (isset($clients[$registrar])) {
  $keylist = $clients[$registrar]->GetDnsSec($sld, $tld);
}
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
        <input type="hidden" name="registrar" value="<?= $registrar ?>">
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
        <td><?= $key->keyTag ?></td>
        <td><?= $key->algorithm ?></td>
        <td><?= $key->digestType ?></td>
        <td><?= $key->digest ?></td>
        <td><a href="deleteDNSSEC.php?sld=<?= urlencode($sld) ?>&tld=<?= urlencode($tld) ?>&keytag=<?= urlencode($key->keyTag) ?>&algorithm=<?= urlencode($key->algorithm) ?>&digesttype=<?= urlencode($key->digestType) ?>&digest=<?= urlencode($key->digest) ?>&registrar=<?= urlencode($registrar) ?>">Delete</a></td>
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
