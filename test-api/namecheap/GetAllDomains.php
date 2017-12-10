<?php
header('Content-Type: application/xml');

$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="OK">
    <Errors />
    <RequestedCommand>namecheap.domains.getList</RequestedCommand>
    <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
        <Domain ID="127" Name="domain1.com" User="owner" Created="02/15/2016" Expires="02/15/2022" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="ENABLED" IsPremium="true" IsOurDNS="true"/>
        <Domain ID="381" Name="domain2.com" User="owner" Created="04/28/2016" Expires="04/28/2023" IsExpired="false" IsLocked="false" AutoRenew="true" WhoisGuard="NOTPRESENT" IsPremium="false" IsOurDNS="true"/>
        <Domain ID="385" Name="domain3.com" User="owner" Created="05/22/2016" Expires="05/22/2023" IsExpired="false" IsLocked="false" AutoRenew="true" WhoisGuard="ENABLED" IsPremium="false" IsOurDNS="false"/>
    </DomainGetListResult>
    <Paging>
        <TotalItems>2</TotalItems>
        <CurrentPage>1</CurrentPage>
        <PageSize>10</PageSize>
    </Paging>
    </CommandResponse>
    <Server>SERVER-NAME</Server>
    <GMTTimeDifference>+5</GMTTimeDifference>
    <ExecutionTime>0.078</ExecutionTime>
</ApiResponse>
XML;

echo $xml;
