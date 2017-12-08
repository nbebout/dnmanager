<?php

class NameCheapClient {
    const RESPONSE_TYPE = 'XML';

    private $server; // Server host name including schema with no trailing slash
    private $username;
    private $password;
//https://api.namecheap.com/xml.response?ApiUser=NEBEBOUT&ApiKey=90d89006f13f469fa5cf529a10571f1c&UserName=nebebout&Command=namecheap.domains.getList&ClientIp=192.168.1.109
    private $apiEndpoint = "/xml.response"; // API page to call, must begin with a slash

    public function __construct(string $server, string $username, string $password) {
        if (empty($server) || empty($username) || empty($password)) {
            throw new Exception('Server, username, and api key required for enom api');
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

    // baseApiArgs returns an array of query parameters common to all NameCheap API calls.
    private function baseApiArgs(string $command) : array {
        return [
            'Command' => $command,
            //'responsetype' => self::RESPONSE_TYPE,
            'ApiUser' => $this->username,
            'UserName' => $this->username,
            'ApiKey' => $this->password,
            'ClientIp' => '71.19.151.18'
        ];
    }

    // commonApiArgs takes the baseApiArgs and adds sld and tld to the list of params.
    private function commonApiArgs(string $command, string $sld, string $tld) : array {
        $data = $this->baseApiArgs($command);
        $data['SLD'] = urlencode($sld);
        $data['TLD'] = urlencode($tld);
        return $data;
    }
//https://api.namecheap.com/xml.response?ApiUser=NEBEBOUT&ApiKey=90d89006f13f469fa5cf529a10571f1c&UserName=nebebout&Command=namecheap.domains.getList&ClientIp=192.168.1.109
    // GetAllDomains will return information on all domains owned by $this->username.
    public function GetAllDomains() : SimpleXMLElement {
        $queryData = $this->baseApiArgs('namecheap.domains.getList');
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url);
    }

    // GetDnsSec returns DNS Sec information about the given domain.
    public function GetDnsSec(string $sld, string $tld) : SimpleXMLElement {
        $queryData = $this->commonApiArgs('GetDnsSec', $sld, $tld);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url);
    }

    // commonDnsSec calls the API using params and paths that are common between the DNS SEC endpoints.
    private function commonDnsSec(string $command, string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : SimpleXMLElement {
        $queryData = $this->commonApiArgs($command, $sld, $tld);
        $queryData['alg'] = $alg;
        $queryData['digest'] = urlencode($digest);
        $queryData['digesttype'] = urlencode($digesttype);
        $queryData['keytag'] = urlencode($keytag);

        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url);
    }

    // AddDnsSec will add a new DNS Sec record to the given domain.
    public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : SimpleXMLElement {
        return $this->commonDnsSec('AddDnsSec', $sld, $tld, $keytag, $alg, $digesttype, $digest);
    }

    // DeleteDnsSec will delete the give DNS Sec record for the given domain.
    public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : SimpleXMLElement {
        return $this->commonDnsSec('DeleteDnsSec', $sld, $tld, $keytag, $alg, $digesttype, $digest);
    }

    // GetDns returns information about the nameserves for the given domain.
    public function GetDns(string $sld, string $tld) : SimpleXMLElement {
        $queryData = $this->commonApiArgs('namecheap.domains.dns.getList', $sld, $tld);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url);
    }

    // ModifyNS updates the nameservers for a given domain. $nameservers should be an array of strings with the nameserver DNS entries.
    // NameCheap's API only allows up to 12 nameserver records. If this function is given more than 12, the rest are ignored.
    public function ModifyNS(string $sld, string $tld, array $nameservers) : SimpleXMLElement {
        $queryData = $this->commonApiArgs('namecheap.domains.dns.setCustom', $sld, $tld);

        $nslist = ''; // The count is out here because we don't know if all elements are valid
        $i = 1;
        foreach ($nameservers as $ns) {
            if ($i >= 13) break; // Enforce max of 12 servers. Ignore 13 and beyond if given.
            if (trim($ns) === '') continue;
            if ($i != 1) { $nslist .= ','; }
            $nslist .= $ns;
            $i++;
        }
	$queryData['Nameservers'] = $nslist;
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url);
    }

    // resellterTypeToInt takes a string identifier and returns enom's int parameter for the type of reseller product
    private function resellerTypeToInt(string $type) : int {
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
    public function GetResellerPrice(string $type, string $tld) : SimpleXMLElement {
        $queryData = $this->baseApiArgs('PE_GetResellerPrice');
        $queryData['tld'] = urlencode($tld);
        $queryData['ProductType'] = $this->resellerTypeToInt($type);

        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url);
    }
}
