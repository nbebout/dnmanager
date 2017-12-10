<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<interface-response>
    <productprice>
        <price>8.95</price>
        <productenabled>True</productenabled>
    </productprice>
    <Command>PE_GETRESELLERPRICE</Command>
    <ErrCount>0</ErrCount>
    <Server>ResellerTest</Server>
    <Site>enom</Site>
    <Done>true</Done>
    <debug/>
</interface-response>
XML;

echo $xml;
