<?php
require_once('init.php');

[$domains, $errors] = getAllDomains($clients);
foreach ($domains as $domain) {
  if (!isValidDomain((string)$domain->name)) {
    $errors[] = $domain->registrar . ": couldn't parse domain name '" . $domain->name . "'";
  }
}
[$dnssecStatuses, $dnssecErrors] = getDnsSecStatuses($clients, $domains);
$errors = array_merge($clientErrors, takeFlashErrors(), $errors, $dnssecErrors);

if (!isset($_REQUEST['sortBy'])) {
  $_REQUEST['sortBy'] = 'domain';
}

switch ($_REQUEST['sortBy']) {
  case "expires":
    sortDomainsByExpires($domains);
    break;
  case "domain":
  default:
    sortDomainsByName($domains);
    break;
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
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
      <th><a href="index.php?sortBy=domain">Domain Name</a></th>
      <th>Registrar</th>
      <th><a href="index.php?sortBy=expires">Expiration Date</a></th>
      <th>Auto Renew</th>
      <th>Locked</th>
      <th>DNSSEC</th>
          <th>Nameservers</th>
        </tr>
      </thead>
      <tbody>
    <?php foreach ($domains as $domain) : ?>
      <?php
      // A name that can't be split still gets listed, just without the
      // actions that need a valid name.
      try {
        [$sld, $tld] = splitDomain($domain->name);
      } catch (InvalidArgumentException $e) {
        $sld = $tld = null;
      }
      ?>
          <?php $registrarKey = strtolower($domain->registrar); ?>
          <tr>
            <td><?= h($domain->name) ?></td>
            <td><?= h($domain->registrar) ?></td>
            <td><?= h(formatDate($domain->expires)) ?></td>
            <td><?= $domain->autorenew ? 'Yes' : 'No' ?></td>
            <td>
              <?php if ($sld !== null && $clients[$registrarKey]->SupportsToggleLocked()) : ?>
                <form action="toggleLockStatus.php" method="post" class="inline-action-form">
                  <input type="hidden" name="domain" value="<?= h($domain->name) ?>">
                  <input type="hidden" name="registrar" value="<?= h($registrarKey) ?>">
                  <input type="hidden" name="locked" value="<?= $domain->locked ? '0' : '1' ?>">
                  <?= csrfInput() ?>
                  <button type="submit" class="link-button"><?= $domain->locked ? 'Yes' : 'No' ?></button>
                </form>
              <?php else : ?>
                <?= $domain->locked ? 'Yes' : 'No' ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($sld !== null && $clients[$registrarKey]->SupportsDnsSec()) : ?>
                <a href="<?= buildUrl('manageDNSSEC.php', ['sld' => $sld, 'tld' => $tld, 'registrar' => $registrarKey]) ?>">Edit</a>
              <?php elseif (isset($dnssecStatuses[$registrarKey][strtolower($domain->name)])) : ?>
                <?= h(formatDnsSecStatus($dnssecStatuses[$registrarKey][strtolower($domain->name)])) ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($sld !== null && $clients[$registrarKey]->SupportsNameservers()) : ?>
                <a href="<?= buildUrl('manageDNS.php', ['sld' => $sld, 'tld' => $tld, 'registrar' => $registrarKey]) ?>">Edit</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

</html>
