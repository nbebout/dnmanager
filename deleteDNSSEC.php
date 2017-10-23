<?php
include('config.php');
  // URL for API request
  $sld = $_GET['sld'];
  $tld = $_GET['tld'];
  $keytag = $_GET['keytag'];
  $algorithm = $_GET['algorithm'];
  $digesttype = $_GET['digesttype'];
  $digest = $_GET['digest'];
  $url = "https://$server/interface.asp?command=DeleteDnsSec&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld&keytag=$keytag&alg=$algorithm&digesttype=$digesttype&digest=$digest";
  // Load the API results into a SimpleXML object
  $xml2 = simplexml_load_file($url);
header("Location: showDNSSEC.php?sld=$sld&tld=$tld");
?>
