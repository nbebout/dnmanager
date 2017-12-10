<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<interface-response>
    <dns>dns1.example.com</dns>
    <dns>dns2.example.com</dns>
    <dns>dns3.example.com</dns>
    <UseDNS>default</UseDNS>
    <HostsNumLimit>15</HostsNumLimit>
    <DNSRegistrySynced>True</DNSRegistrySynced>
    <RRPCodeGDNS>200</RRPCodeGDNS>
    <RRPText>Command completed successfully</RRPText>
    <Command>GETDNS</Command>
    <Language>eng</Language>
    <ErrCount>0</ErrCount>
    <ResponseCount>0</ResponseCount>
    <MinPeriod>1</MinPeriod>
    <MaxPeriod>10</MaxPeriod>
    <Server>SJL21WRESELLT01</Server>
    <Site>eNom</Site>
    <IsLockable>True</IsLockable>
    <IsRealTimeTLD>True</IsRealTimeTLD>
    <TimeDifference>+08.00</TimeDifference>
    <ExecTime>0.594</ExecTime>
    <Done>true</Done>
    <RequestDateTime>12/8/2011 3:58:52 AM</RequestDateTime>
    <debug></debug>
</interface-response>
XML;

echo $xml;
