<?php

class EnomClient implements RegistrarClient {
    const RESPONSE_TYPE = 'XML';

    private $server; // Server host name including schema with no trailing slash
    private $username;
    private $password;
    private $apiEndpoint = "/interface.asp"; // API page to call, must begin with a slash

    public function __construct(string $server, string $username, string $password) {
        if (empty($server) || empty($username) || empty($password)) {
            throw new Exception('Server, username, and password required for enom api');
        }

        $this->server = trim($server, '/');
        $this->username = $username;
        $this->password = $password;
    }

    // SetApiPath will set the $apiEndpoint variable. This should only be used when testing with a stub server.
    public function SetApiPath(string $path) {
        if ($path[0] !== '/') {
            $path = '/'.$path;
        }
        $this->apiEndpoint = $path;
    }

    // baseApiArgs returns an array of query parameters common to all Enom API calls.
    private function baseApiArgs(string $command) : array {
        return [
            'command' => $command,
            'responsetype' => self::RESPONSE_TYPE,
            'uid' => $this->username,
            'pw' => $this->password
        ];
    }

    // commonApiArgs takes the baseApiArgs and adds sld and tld to the list of params.
    private function commonApiArgs(string $command, string $sld, string $tld) : array {
        $data = $this->baseApiArgs($command);
        $data['sld'] = urlencode($sld);
        $data['tld'] = urlencode($tld);
        return $data;
    }

    // GetAllDomains will return information on all domains owned by $this->username.
    public function GetAllDomains() : array {
        $queryData = $this->baseApiArgs('GetAllDomains');
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $xml = simplexml_load_file($url);
        $domainlistXML = $xml->GetAllDomains->DomainDetail;

        $domainlist = [];
        foreach($domainlistXML as $domain) {
            $d = new Domain();
            $d->registrar = 'eNom';
            $d->name = $domain->DomainName;
            $d->locked = ($domain->lockstatus == 'Locked');
            $d->expires = $domain->{'expiration-date'};
            $d->autorenew = ($domain->AutoRenew == 'Yes');
            $domainlist []= $d;
        }
        return $domainlist;
    }

    // will return string true if domain is locked, false if domain is unlocked
    public function DomainLocked(string $domain) : bool {
            $split = explode('.', $domain);
            $queryData2 = $this->commonApiArgs('GetRegLock', $split[0], $split[1]);
            $qs2 = http_build_query($queryData2);
            $url2 = "{$this->server}{$this->apiEndpoint}?$qs2";
            $xml2 = simplexml_load_file($url2);
            return (string)($xml2->{'reg-lock'}) === '1';
    }

    // Will toggle the current locked status for the given domain
    public function ToggleLocked(string $domain) : bool {
            $split = explode('.', $domain);
            $queryData = $this->commonApiArgs('SetRegLock', $split[0], $split[1]);
            $queryData['UnlockRegistrar'] = $this->DomainLocked($domain);
            $qs = http_build_query($queryData);
            $url = "{$this->server}{$this->apiEndpoint}?$qs";
            return simplexml_load_file($url)->RRPCode == '200';
    }

    // GetDnsSec returns DNS Sec information about the given domain.
    public function GetDnsSec(string $sld, string $tld) : array {
        $queryData = $this->commonApiArgs('GetDnsSec', $sld, $tld);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $xml = simplexml_load_file($url);
        $keylistXML = $xml->DnsSecData->KeyData;

        $keylist = [];
        foreach ($keylistXML as $key) {
            $k = new DNSSecKey();
            $k->keyTag = $key->KeyTag;
            $k->algorithm = $key->Algorithm;
            $k->digestType = $key->DigestType;
            $k->digest = $key->Digest;
            $keylist []= $k;
        }
        return $keylist;
    }

    public function SupportsDnsSec() : bool {
        return true;
    }
    public function SupportsNameservers() : bool {
        return true;
    }
    public function SupportsToggleLocked() : bool {
        return true;
    }

    // commonDnsSec calls the API using params and paths that are common between the DNS SEC endpoints.
    private function commonDnsSec(string $command, string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : SimpleXMLElement {
        $queryData = $this->commonApiArgs($command, $sld, $tld);
        $queryData['alg'] = urlencode($alg);
        $queryData['digest'] = urlencode($digest);
        $queryData['digesttype'] = urlencode($digesttype);
        $queryData['keytag'] = urlencode($keytag);

        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url);
    }

    // AddDnsSec will add a new DNS Sec record to the given domain.
    public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool {
        return $this->commonDnsSec('AddDnsSec', $sld, $tld, $keytag, $alg, $digesttype, $digest)->RRPCode == '200';
    }

    // DeleteDnsSec will delete the give DNS Sec record for the given domain.
    public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool {
        return $this->commonDnsSec('DeleteDnsSec', $sld, $tld, $keytag, $alg, $digesttype, $digest)->RRPCode == '200';
    }

    // GetDns returns information about the nameserves for the given domain.
    public function GetDns(string $sld, string $tld) : array {
        $queryData = $this->commonApiArgs('GetDns', $sld, $tld);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return (array)(simplexml_load_file($url)->dns);
    }

    // ModifyNS updates the nameservers for a given domain. $nameservers should be an array of strings with the nameserver DNS entries.
    // Enom's API only allows up to 12 nameserver records. If this function is given more than 12, the rest are ignored.
    public function ModifyNS(string $sld, string $tld, array $nameservers) : bool {
        $queryData = $this->commonApiArgs('ModifyNS', $sld, $tld);

        $i = 1; // The count is out here because we don't know if all elements are valid
        foreach ($nameservers as $ns) {
            if ($i >= 13) break; // Enforce max of 12 servers. Ignore 13 and beyond if given.
            if (trim($ns) === '') continue;

            $queryData['ns'.$i] = urlencode($ns);
            $i++;
        }

        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url)->RRPCode == '200';
    }

    // resellterTypeToInt takes a string identifier and returns enom's int parameter for the type of reseller product
    private function pricingType(string $type) : int {
        switch($type) {
            case 'new':
                return 10;
            case 'renew':
                return 16;
            case 'transfer':
                return 19;
        }
    }

    // GetResellerPrice returns product information about a product type. $type can be one of 'new', 'renew', or 'transfer'.
    public function GetResellerPrice(string $tld) : array {
        $queryData = $this->baseApiArgs('PE_GetResellerPrice');
        $queryData['tld'] = urlencode($tld);

        $queryData['ProductType'] = $this->pricingType('new');
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $prices['new'] = (float)(simplexml_load_file($url)->productprice->price);

        $queryData['ProductType'] = $this->pricingType('renew');
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $prices['renew'] = (float)(simplexml_load_file($url)->productprice->price);

        $queryData['ProductType'] = $this->pricingType('transfer');
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $prices['transfer'] = (float)(simplexml_load_file($url)->productprice->price);
 
        return $prices;
    }

    public function GetAllPrices(array $tldarray) : array {
        $prices = array();
        foreach ($tldarray as $tld) {
          $prices[$tld] = $this->GetResellerPrice($tld);
        }
        return $prices;
    }

    // GetRenewalPrice returns product information about only renewals
    public function GetRenewalPrice(string $tld) : array {
        $queryData = $this->baseApiArgs('PE_GetResellerPrice');
        $queryData['tld'] = urlencode($tld);

        $queryData['ProductType'] = $this->pricingType('renew');
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $prices['renew'] = (float)(simplexml_load_file($url)->productprice->price);

	return $prices;
    }

    public function GetAllRenewalPrices(array $tldarray) : array {
        $prices = array();
        foreach ($tldarray as $tld) {
          $prices[$tld] = $this->GetRenewalPrice($tld);
        }

        return $prices;
    }

}
