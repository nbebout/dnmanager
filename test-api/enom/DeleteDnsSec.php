<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<interface-response>
    <Success>True</Success>
    <DnsSecData>
        <Result>
            <ResponseCode>200</ResponseCode>
            <ResponseDetails></ResponseDetails>
            <ResponseMessage><![CDATA[Command completed successfully]]></ResponseMessage>
        </Result>
    </DnsSecData>
    <Command>DELETEDNSSEC</Command>
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
    <ExecTime>0.156</ExecTime>
    <Done>true</Done>
    <TrackingKey>0e39d3b7-0999-4e72-8f6a-feb692b69d59</TrackingKey>
    <RequestDateTime>7/16/2014 2:05:42 PM</RequestDateTime>
    <debug/>
</interface-response>
XML;

echo $xml;
