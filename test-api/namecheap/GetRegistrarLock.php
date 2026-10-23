<?php
header('Content-Type: application/xml');

$domain = htmlspecialchars($_REQUEST['DomainName'] ?? '', ENT_QUOTES | ENT_XML1);

$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="OK">
    <Errors />
    <RequestedCommand>namecheap.domains.getRegistrarLock</RequestedCommand>
    <CommandResponse Type="namecheap.domains.getRegistrarLock">
    <DomainGetRegistrarLockResult Domain="$domain" RegistrarLockStatus="false" />
    </CommandResponse>
    <Server>SERVER-NAME</Server>
    <GMTTimeDifference>+5</GMTTimeDifference>
    <ExecutionTime>0.012</ExecutionTime>
</ApiResponse>
XML;

echo $xml;
