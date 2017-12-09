<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<interface-response>
    <RRPCode>200</RRPCode>
    <RRPText>Command completed successfully</RRPText>
    <Command>MODIFYNS</Command>
    <ErrCount>0</ErrCount>
    <Server>ResellerTest</Server>
    <Site>enom</Site>
    <Done>true</Done>
    <debug></debug>
</interface-response>
XML;

echo $xml;
