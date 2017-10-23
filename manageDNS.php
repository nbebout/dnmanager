<?php
  include('config.php');

  if ($_POST['submit']) {
    // URL for API request
    $sld = urlencode($_POST['sld']);
    $tld = urlencode($_POST['tld']);
    $ns1 = urlencode($_POST['ns1']);
    $ns2 = urlencode($_POST['ns2']);
    $ns3 = urlencode($_POST['ns3']);
    $ns4 = urlencode($_POST['ns4']);
    $ns5 = urlencode($_POST['ns5']);

    $url = "https://$server/interface.asp?command=ModifyNS&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld&ns1=$ns1&ns2=$ns2&ns3=$ns3&ns4=$ns4&ns5=$ns5";

    // Load the API results into a SimpleXML object
    simplexml_load_file($url);
  } else {
    $sld = urlencode($_GET['sld']);
    $tld = urlencode($_GET['tld']);
  }

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
  </head>

  <body>
    <h1>Domain Name Manager</h1>
    <div style="display: none;" id="add-record-form">
      <h3>Update nameservers for <?php echo "$sld.$tld"; ?></h3>

      <form action="manageDNS.php" method="post">
        <table>
          <tr>
            <td>NS1:</td>
            <td><input type="text" name="ns1"></td>
          </tr>
          <tr>
            <td>NS2:</td>
            <td><input type="text" name="ns2"></td>
          </tr>
          <tr>
            <td>NS3:</td>
            <td><input type="text" name="ns3"></td>
          </tr>
          <tr>
            <td>NS4:</td>
            <td><input type="text" name="ns4"></td>
          </tr>
          <tr>
            <td>NS5:</td>
            <td><input type="text" name="ns5"></td>
          </tr>
        </table>
        <br />
        <input type="hidden" name="sld" value="<?= $sld ?>">
        <input type="hidden" name="tld" value="<?= $tld ?>">
        <input type="submit" name="submit" value="Update">
      </form>
    </div>

    <h3>Nameservers for <?= "$sld.$tld" ?></h3>
    <table>

      <?php foreach ($nslist as $ns): ?>
      <tr>
        <td><?= $ns ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <br>

    <br />
    <a href="#" id="add-record-link">Update Nameservers</a><br /><br />
    <a href="index.php">Return to Domain List</a>
  </body>

  <script type="text/javascript">
    document.getElementById('add-record-link').addEventListener('click', function() {
      document.getElementById('add-record-form').style.display = 'block';
    });
  </script>
</html>
