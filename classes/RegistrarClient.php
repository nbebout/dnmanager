<?php

interface RegistrarClient {
    public function SetApiPath(string $path);

    public function GetAllDomains() : array;

    public function SupportsDnsSec() : bool;
    public function GetDnsSec(string $sld, string $tld) : array;
    public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool;
    public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool;

    public function GetDns(string $sld, string $tld) : array;
    public function ModifyNS(string $sld, string $tld, array $nameservers) : bool;

    public function GetResellerPrice(string $type, string $tld) : float;
}
