<?php
  include('config.php');

  // URL for API request
  $sld = urlencode($_GET['sld']);
  $tld = urlencode($_GET['tld']);
  $keytag = urlencode($_GET['keytag']);
  $algorithm = urlencode($_GET['algorithm']);
  $digesttype = urlencode($_GET['digesttype']);
  $digest = urlencode($_GET['digest']);

  $url = "https://$server/interface.asp?command=DeleteDnsSec&uid=$username&pw=$password&responsetype=xml&sld=$sld&tld=$tld&keytag=$keytag&alg=$algorithm&digesttype=$digesttype&digest=$digest";

  // Call API URL
  simplexml_load_file($url);

  header("Location: manageDNSSEC.php?sld=$sld&tld=$tld");
