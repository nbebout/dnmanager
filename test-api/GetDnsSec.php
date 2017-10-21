<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<interface-response>
    <DnsSecData>
        <KeyData>
            <Algorithm><![CDATA[253]]></Algorithm>
            <Digest><![CDATA[54F42A9ACB3BFCE44B416AC83735D3405EEA825A]]></Digest>
            <DigestType><![CDATA[1]]></DigestType>
            <KeyTag><![CDATA[32121]]></KeyTag>
        </KeyData>
        <Result>
            <ResponseCode>200</ResponseCode>
            <ResponseDetails><![CDATA[]]></ResponseDetails>
            <ResponseMessage><![CDATA[Command completed successfully]]></ResponseMessage>
        </Result>
    </DnsSecData>
    <MaxSigLife/>
    <Success>True</Success>
    <DnsSecDataCount>1</DnsSecDataCount>
    <Command>GETDNSSEC</Command>
    <APIType>API.NET</APIType>
    <Language>eng</Language>
    <ErrCount>0</ErrCount>
    <ResponseCount>0</ResponseCount>
    <MinPeriod>1</MinPeriod>
    <MaxPeriod>10</MaxPeriod>
    <Server>sjl21wresell01</Server>
    <Site>eNom</Site>
    <IsLockable/>
    <IsRealTimeTLD/>
    <TimeDifference>+0.00</TimeDifference>
    <ExecTime>0.547</ExecTime>
    <Done>true</Done>
    <TrackingKey>7f2045f8-f5cc-490e-91b4-aed12bd703fc</TrackingKey>
    <RequestDateTime>7/16/2014 2:01:11 PM</RequestDateTime>
    <debug/>
</interface-response>
XML;

echo $xml;
