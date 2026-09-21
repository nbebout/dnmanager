<?php
require_once('init.php');

// Use POST values for updates and GET values for read-only navigation.
$request = isset($_POST['submit']) ? $_POST : $_GET;
$sld = requireValidDomainPart($request['sld'] ?? '', 'sld');
$tld = requireValidTld($request['tld'] ?? '');
$registrar = requireValidRegistrar($request['registrar'] ?? '');

if (isset($_POST['submit'])) {
  requireValidCsrfToken();
  // URL for API request
  [$keytag, $algorithm, $digesttype, $digest] = requireValidDnssecInput(
    $_POST['keytag'] ?? null,
    $_POST['algorithm'] ?? null,
    $_POST['digesttype'] ?? null,
    isset($_POST['digest']) && is_string($_POST['digest']) ? str_replace(' ', '', $_POST['digest']) : null
  );

  if (!$clients[$registrar]->SupportsDnsSec()) {
    rejectInvalidInput('Registrar does not support DNSSEC');
  }
  $clients[$registrar]->AddDnsSec($sld, $tld, $keytag, $algorithm, $digesttype, $digest);
}


$keylist = $clients[$registrar]->GetDnsSec($sld, $tld);
?>
<!DOCTYPE html>
<html>

<head>
  <title>Domain Name Manager</title>
  <style>
    table,
    th,
    td {
      border: 1px solid black;
      padding: 4px;
    }

    body {
      font-family: sans-serif;
    }

    a,
    a:link,
    a:visited,
    a:hover {
      color: black;
    }

    .inline-action-form {
      display: inline;
      margin: 0;
    }

    .link-button {
      background: none;
      border: none;
      color: black;
      cursor: pointer;
      font: inherit;
      padding: 0;
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <h1>Domain Name Manager</h1>

  <div style="display: none;" id="add-record-form">
    <h3>Add DNSSEC record for <?= h("$sld.$tld") ?></h3>

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
      <input type="hidden" name="sld" value="<?= h($sld) ?>">
      <input type="hidden" name="tld" value="<?= h($tld) ?>">
      <input type="hidden" name="registrar" value="<?= h($registrar) ?>">
      <?= csrfInput() ?>
      <input type="submit" name="submit" value="Add">
    </form>
  </div>

  <h3>DNSSEC records for <?= h("$sld.$tld") ?></h3>
  <table>
    <tr>
      <th>Key Tag</th>
      <th>Algorithm</th>
      <th>Digest Type</th>
      <th>Digest</th>
      <th>Delete</th>
    </tr>

    <?php foreach ($keylist as $key) : ?>
      <tr>
        <td><?= h($key->keyTag) ?></td>
        <td><?= h($key->algorithm) ?></td>
        <td><?= h($key->digestType) ?></td>
        <td><?= h($key->digest) ?></td>
        <td>
          <form action="deleteDNSSEC.php" method="post" class="inline-action-form">
            <input type="hidden" name="sld" value="<?= h($sld) ?>">
            <input type="hidden" name="tld" value="<?= h($tld) ?>">
            <input type="hidden" name="keytag" value="<?= h($key->keyTag) ?>">
            <input type="hidden" name="algorithm" value="<?= h($key->algorithm) ?>">
            <input type="hidden" name="digesttype" value="<?= h($key->digestType) ?>">
            <input type="hidden" name="digest" value="<?= h($key->digest) ?>">
            <input type="hidden" name="registrar" value="<?= h($registrar) ?>">
            <?= csrfInput() ?>
            <button type="submit" class="link-button">Delete</button>
          </form>
        </td>
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
