<?php
$command = $_REQUEST['Command'];

$stubs = [
    'namecheap.domains.getList' => 'GetAllDomains',
    'namecheap.domains.dns.getList' => 'GetDns',
    'namecheap.domains.dns.setCustom' => 'ModifyNS',
    'namecheap.domains.getRegistrarLock' => 'GetRegistrarLock',
    'namecheap.domains.setRegistrarLock' => 'SetRegistrarLock',
    'namecheap.users.getPricing' => 'GetPricing'
];

if (array_key_exists($command, $stubs)) {
    include($stubs[$command] . '.php');
}
