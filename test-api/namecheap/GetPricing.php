<?php
header('Content-Type: application/xml');

$tld = htmlspecialchars($_REQUEST['ProductName'] ?? '', ENT_QUOTES | ENT_XML1);

// Namecheap's API spells the attribute "YourAdditonalCost".
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="OK">
    <Errors />
    <RequestedCommand>namecheap.users.getPricing</RequestedCommand>
    <CommandResponse Type="namecheap.users.getPricing">
    <UserGetPricingResult>
        <ProductType Name="domains">
            <ProductCategory Name="register">
                <Product Name="$tld">
                    <Price Duration="1" DurationType="YEAR" Price="10.28" YourPrice="10.28" YourAdditonalCost="0.18" Currency="USD" />
                    <Price Duration="2" DurationType="YEAR" Price="20.56" YourPrice="20.56" YourAdditonalCost="0.36" Currency="USD" />
                </Product>
            </ProductCategory>
            <ProductCategory Name="renew">
                <Product Name="$tld">
                    <Price Duration="1" DurationType="YEAR" Price="13.98" YourPrice="13.98" YourAdditonalCost="0.18" Currency="USD" />
                </Product>
            </ProductCategory>
            <ProductCategory Name="transfer">
                <Product Name="$tld">
                    <Price Duration="1" DurationType="YEAR" Price="9.58" YourPrice="9.58" YourAdditonalCost="0.18" Currency="USD" />
                </Product>
            </ProductCategory>
        </ProductType>
    </UserGetPricingResult>
    </CommandResponse>
    <Server>SERVER-NAME</Server>
    <GMTTimeDifference>+5</GMTTimeDifference>
    <ExecutionTime>0.034</ExecutionTime>
</ApiResponse>
XML;

echo $xml;
