<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="OK">
    <Errors />
    <RequestedCommand>namecheap.domains.dns.getList</RequestedCommand>
    <CommandResponse Type="namecheap.domains.dns.getList">
    <DomainDNSGetListResult Domain="domain.com" IsUsingOurDNS="true">
        <Nameserver>dns1.name-servers.com</Nameserver>
        <Nameserver>dns2.name-servers.com</Nameserver>
    </DomainDNSGetListResult>
    </CommandResponse>
    <Server>SERVER-NAME</Server>
    <GMTTimeDifference>+5</GMTTimeDifference>
    <ExecutionTime>32.76</ExecutionTime>
</ApiResponse>
XML;

echo $xml;
