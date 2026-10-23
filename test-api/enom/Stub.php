<?php
$command = $_REQUEST['command'];

$stubs = ['GetAllDomains', 'GetDns', 'ModifyNS', 'GetDnsSec', 'AddDnsSec', 'DeleteDnsSec', 'PE_GetResellerPrice', 'GetRegLock', 'SetRegLock'];

if (in_array($command, $stubs)) {
    include($command . '.php');
}
