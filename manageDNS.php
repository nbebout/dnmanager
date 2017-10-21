<?php
  require('config.php');

  // $_REQUEST contains $_POST, $_GET, and $_COOKIE
  $sld = urlencode($_REQUEST['sld']);
  $tld = urlencode($_REQUEST['tld']);

  if (!is_null($_POST['submit'])) {
    $nameservers = $_POST['ns'];
    $nsQueryStrings = [];

    $i = 1; // The count is out here because we don't know if all elements are valid
    foreach ($nameservers as $ns) {
      if ($i >= 13) break; // Enforce max of 12 servers. Ignore 13 and beyond if given.
      if (trim($ns) === '') continue;
      $ns = urlencode($ns);
      $nsQueryStrings []= "ns$i=$ns";
      $i++;
    }

    $nsQueryString = implode("&", $nsQueryStrings);

    $url = "https://$server/interface.asp?command=ModifyNS&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld&$nsQueryString";

    // Load the API results into a SimpleXML object
    simplexml_load_file($url);
  }

  $sld = 'example';
  $tld = 'com';
  // URL for API request
  $url = "https://$server/interface.asp?command=GetDns&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld";

  // Load the API results into a SimpleXML object
  $xml = simplexml_load_file($url);
  $nslist = $xml->dns;
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

    <h3>Nameservers for <?= "$sld.$tld" ?></h3>

    <form action="manageDNS.php" method="POST" style="display: none;" id="add-record-form">
      <input type="hidden" name="sld" value="<?= $sld ?>">
      <input type="hidden" name="tld" value="<?= $tld ?>">

      <table id="ns-form-list">
        <?php $i = 1; foreach ($nslist as $ns): ?>
        <tr>
          <td>NS<?= $i ?>:</td>
          <td><input type="text" name="ns[]" value="<?= $ns ?>"></td>
        </tr>
        <?php $i++; endforeach; ?>
      </table>
      <br>
      <button type="button" id="add-nameserver">Add Server</button>
      <button name="submit">Update</button>
    </form>

    <section id="ns-table">
      <table>
        <?php $i = 1; foreach ($nslist as $ns): ?>
        <tr>
          <td>NS<?= $i ?>: <?= $ns ?></td>
        </tr>
        <?php $i++; endforeach; ?>
      </table>

      <br>
      <a href="#" id="add-record-link">Edit Nameservers</a>
    </section>
    <br>
    <a href="index.php">Return to Domain List</a>
  </body>

  <script type="text/javascript">
    const nsForm = document.getElementById('add-record-form');
    const toggleLink = document.getElementById('add-record-link');
    const staticListTable = document.getElementById('ns-table');
    const addNSButton = document.getElementById('add-nameserver');
    const nsFormList = document.getElementById('ns-form-list');
    let numOfServers = <?= count($nslist) ?>;
    const maxNSServers = 12;

    toggleLink.addEventListener('click', function() {
      nsForm.style.display = 'block';
      staticListTable.style.display = 'none';
    });

    addNSButton.addEventListener('click', function() {
      if (numOfServers >= maxNSServers) return; // Only allow 12 servers
      numOfServers++;

      const newTextInput = document.createElement('tr');
      newTextInput.innerHTML = '<td>NS'+numOfServers+':</td><td><input type="text" name="ns[]"></td>'
      nsFormList.appendChild(newTextInput);
    });
  </script>
</html>
