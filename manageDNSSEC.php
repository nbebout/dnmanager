<?php
require_once('init.php');

// Use POST values for updates and GET values for read-only navigation.
$request = isset($_POST['submit']) ? $_POST : $_GET;
$sld = requireValidDomainPart($request['sld'] ?? '', 'sld');
$tld = requireValidTld($request['tld'] ?? '');
$registrar = requireValidRegistrar($request['registrar'] ?? '');

// Changes redirect back to this page afterwards, so refreshing it doesn't
// resubmit them. Errors are carried across the redirect as flash errors.
if (isset($_POST['submit'])) {
  requireValidCsrfToken();
  [$keytag, $algorithm, $digesttype, $digest] = requireValidDnssecInput(
    $_POST['keytag'] ?? null,
    $_POST['algorithm'] ?? null,
    $_POST['digesttype'] ?? null,
    isset($_POST['digest']) && is_string($_POST['digest']) ? str_replace(' ', '', $_POST['digest']) : null
  );

  if (!$clients[$registrar]->SupportsDnsSec()) {
    rejectInvalidInput('Registrar does not support DNSSEC');
  }
  try {
    if (!$clients[$registrar]->AddDnsSec($sld, $tld, $keytag, $algorithm, $digesttype, $digest)) {
      setFlashError("Failed to add the DNSSEC record for $sld.$tld");
    }
  } catch (Throwable $e) {
    setFlashError("Failed to add the DNSSEC record for $sld.$tld: " . $e->getMessage());
  }
  redirectTo('manageDNSSEC.php', ['sld' => $sld, 'tld' => $tld, 'registrar' => $registrar]);
}

$errors = takeFlashErrors();

try {
  $keylist = $clients[$registrar]->GetDnsSec($sld, $tld);
} catch (Throwable $e) {
  $keylist = [];
  $errors[] = "Failed to load the DNSSEC records for $sld.$tld: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Domain Name Manager</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <h1>Domain Name Manager</h1>
  <?php foreach ($errors as $error) : ?>
    <p class="error"><?= h($error) ?></p>
  <?php endforeach; ?>

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
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Key Tag</th>
      <th>Algorithm</th>
      <th>Digest Type</th>
      <th>Digest</th>
          <th>Delete</th>
        </tr>
      </thead>
      <tbody>
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
      </tbody>
    </table>
  </div>
  <br>

  <a href="#" id="add-record-link">Add DNSSEC record</a><br />
  <br />
  <a href="index.php">Return to Domain List</a>
<script type="text/javascript">
  document.getElementById('add-record-link').addEventListener('click', function(event) {
    event.preventDefault();
    document.getElementById('add-record-form').style.display = 'block';
  });
</script>
</body>

</html>
