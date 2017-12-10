<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<interface-response>
    <GetAllDomains>
        <DomainDetail>
            <DomainName>example.com</DomainName>
            <DomainNameID>340691704</DomainNameID>
            <expiration-date>7/7/2012 8:37:00 AM</expiration-date>
            <lockstatus>Locked</lockstatus>
            <AutoRenew>No</AutoRenew>
        </DomainDetail>

        <DomainDetail>
            <DomainName>example.info</DomainName>
            <DomainNameID>152708845</DomainNameID>
            <expiration-date>2/4/2011 6:25:00 AM</expiration-date>
            <lockstatus>Not Locked</lockstatus>
            <AutoRenew>No</AutoRenew>
        </DomainDetail>

        <domaincount>3077</domaincount>
        <UserRequestStatus>DomainBox</UserRequestStatus>
    </GetAllDomains>
    <Command>GETALLDOMAINS</Command>
    <Language>eng</Language>
    <ErrCount>0</ErrCount>
    <ResponseCount>0</ResponseCount>
    <MinPeriod/>
    <MaxPeriod>10</MaxPeriod>
    <Server>SJL21WRESELLT01</Server>
    <Site>eNom</Site>
    <IsLockable/>
    <IsRealTimeTLD/>
    <TimeDifference>+0.00</TimeDifference>
    <ExecTime>74.456</ExecTime>
    <Done>true</Done>
    <RequestDateTime>12/8/2011 3:38:26 AM</RequestDateTime>
    <debug></debug>
</interface-response>
XML;

echo $xml;
