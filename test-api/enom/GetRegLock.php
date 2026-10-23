<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<interface-response>
    <reg-lock>1</reg-lock>
    <Command>GETREGLOCK</Command>
    <ErrCount>0</ErrCount>
    <Server>ResellerTest</Server>
    <Site>enom</Site>
    <Done>true</Done>
    <debug/>
</interface-response>
XML;

echo $xml;
