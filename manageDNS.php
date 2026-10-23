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
  if (!$clients[$registrar]->SupportsNameservers()) {
    rejectInvalidInput('Registrar does not support nameserver changes');
  }
  $nameservers = requireValidNameservers($_POST['ns'] ?? null);
  try {
    if (!$clients[$registrar]->ModifyNS($sld, $tld, $nameservers)) {
      setFlashError("Failed to update the nameservers for $sld.$tld");
    }
  } catch (Throwable $e) {
    setFlashError("Failed to update the nameservers for $sld.$tld: " . $e->getMessage());
  }
  redirectTo('manageDNS.php', ['sld' => $sld, 'tld' => $tld, 'registrar' => $registrar]);
}

$errors = takeFlashErrors();

// Only offer editing when the current nameservers loaded, so a failed lookup
// can't lead to replacing them blind.
$nslistLoaded = true;
try {
  $nslist = $clients[$registrar]->GetDns($sld, $tld);
} catch (Throwable $e) {
  $nslist = [];
  $nslistLoaded = false;
  $errors[] = "Failed to load the nameservers for $sld.$tld: " . $e->getMessage();
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

  <h3>Nameservers for <?= h("$sld.$tld") ?></h3>

  <form action="manageDNS.php" method="POST" style="display: none;" id="add-record-form">
    <input type="hidden" name="sld" value="<?= h($sld) ?>">
    <input type="hidden" name="tld" value="<?= h($tld) ?>">
    <input type="hidden" name="registrar" value="<?= h($registrar) ?>">
    <?= csrfInput() ?>

    <table id="ns-form-list">
      <tbody>
      <?php $i = 1;
      foreach ($nslist as $ns) : ?>
        <tr>
          <td>NS<?= $i ?>:</td>
          <td><input type="text" name="ns[]" value="<?= h($ns) ?>"></td>
        </tr>
      <?php $i++;
      endforeach; ?>
      </tbody>
    </table>
    <br>
    <button type="button" id="add-nameserver">Add Server</button>
    <button name="submit">Update</button>
  </form>

  <section id="ns-table" class="table-wrapper">
    <table>
      <tbody>
      <?php $i = 1;
      foreach ($nslist as $ns) : ?>
        <tr>
          <td>NS<?= $i ?>:</td>
          <td><?= h($ns) ?></td>
        </tr>
      <?php $i++;
      endforeach; ?>
      </tbody>
    </table>

    <?php if ($nslistLoaded) : ?>
      <br>
      <a href="#" id="add-record-link">Edit Nameservers</a>
    <?php endif; ?>
  </section>
  <br>
  <a href="index.php">Return to Domain List</a>
<script type="text/javascript">
  const nsForm = document.getElementById('add-record-form');
  const staticListTable = document.getElementById('ns-table');
  const toggleLink = document.getElementById('add-record-link');
  const nsFormList = document.getElementById('ns-form-list');
  toggleLink?.addEventListener('click', function(event) {
    event.preventDefault();
    nsForm.style.display = 'block';
    staticListTable.style.display = 'none';

    const focusElement = nsFormList.querySelectorAll('input[type="text"]')[0];
    if (!focusElement) return;
    focusElement.focus();
    focusElement.setSelectionRange(0, focusElement.value.length)
  });

  let numOfServers = <?= count($nslist) ?>;
  const maxNSServers = 12;
  const addNSButton = document.getElementById('add-nameserver');
  addNSButton.addEventListener('click', function() {
    if (numOfServers >= maxNSServers) return; // Only allow 12 servers
    numOfServers++;

    const newTextInput = document.createElement('tr');
    newTextInput.innerHTML = '<td>NS' + numOfServers + ':</td><td><input type="text" name="ns[]"></td>'
    nsFormList.appendChild(newTextInput);

    const nsInputs = nsFormList.querySelectorAll('input[type="text"]');
    nsInputs[nsInputs.length - 1].focus();
  });
</script>
</body>

</html>
