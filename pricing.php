<?php
  include('config.php');

$tldarray = [com, net, org, mba, biz, cloud, info, mobi, name, us, zone, email, family];
sort($tldarray);
  // URL for API request
  //$premiumurl = "https://$server/interface.asp?command=PE_GetPremiumPricing&uid=$username&pw=$password&responsetype=xml&sld1=nb&tld1=zone&sld2=nb&tld2=mba&producttype=register";
  // Load the API results into a SimpleXML object
  //$xml = simplexml_load_file($premiumurl);
  //var_dump($xml);
echo "
<html>
  <head>
    <title>Domain Name Manager</title>
  </head>

  <body>
    <h1>Domain Name Manager</h1>
    <table>
      <tr>
        <th>TLD</th>
        <th>Register</th>
        <th>Renew</th>
        <th>Transfer</th>
      </tr>
      ";
foreach ($tldarray as $tld) {
	echo "<tr><td>$tld</td>";
	$newurl = "https://$server/interface.asp?command=PE_GETRESELLERPRICE&uid=$username&pw=$password&tld=$tld&ProductType=10&responsetype=xml";
        $renewurl = "https://$server/interface.asp?command=PE_GETRESELLERPRICE&uid=$username&pw=$password&tld=$tld&ProductType=16&responsetype=xml";
        $xferurl = "https://$server/interface.asp?command=PE_GETRESELLERPRICE&uid=$username&pw=$password&tld=$tld&ProductType=19&responsetype=xml";
	$newxmlprice = simplexml_load_file($newurl);
        $renewxmlprice = simplexml_load_file($renewurl);
        $xferxmlprice = simplexml_load_file($xferurl);
	$newprice = $newxmlprice->productprice->price;
        $renewprice = $renewxmlprice->productprice->price;
        $xferprice = $xferxmlprice->productprice->price;
	echo "<td>\$$newprice</td>";
	echo "<td>\$$renewprice</td>";
	echo "<td>\$$xferprice</td>";
	echo "</tr>";
}
echo "
      
  </body>
</html>";
?>
