<?php

class NameCheapClient implements RegistrarClient {
    private $server; // Server host name including schema with no trailing slash
    private $username;
    private $apiKey;
    private $clientIP;

    private $apiEndpoint = "/xml.response"; // API page to call, must begin with a slash

    public function __construct(string $server, string $username, string $apiKey) {
        if (empty($server) || empty($username) || empty($apiKey)) {
            throw new Exception('Server, username, and api key required for enom api');
        }

        $this->server = trim($server, '/');
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->clientIP = $_SERVER['REMOTE_ADDR'];
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
            'ApiUser' => $this->username,
            'UserName' => $this->username,
            'ApiKey' => $this->apiKey,
            'ClientIp' => $this->clientIP
        ];
    }

    // commonApiArgs takes the baseApiArgs and adds sld and tld to the list of params.
    private function commonApiArgs(string $command, string $sld, string $tld) : array {
        $data = $this->baseApiArgs($command);
        $data['SLD'] = urlencode($sld);
        $data['TLD'] = urlencode($tld);
        return $data;
    }

    // GetAllDomains will return information on all domains owned by $this->username.
    public function GetAllDomains() : array {
        $queryData = $this->baseApiArgs('namecheap.domains.getList');
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $xml = simplexml_load_file($url);
        $domainlistXML = $xml->CommandResponse->DomainGetListResult->Domain;

        $domainlist = [];
        foreach($domainlistXML as $domain) {
            $attributes = $domain->attributes();
            $d = new Domain();
            $d->registrar = 'Namecheap';
            $d->name = $attributes->Name;
            $d->locked = $this->DomainLocked($attributes->Name);
            $d->expires = $attributes->Expires;
            $d->autorenew = ($attributes->AutoRenew == 'true');
            $domainlist []= $d;
        }
        return $domainlist;
    }

    // will return string true if domain is locked, false if domain is unlocked
    public function DomainLocked(string $domain) : bool {
            $queryData2 = $this->baseApiArgs('namecheap.domains.getRegistrarLock');
            $queryData2['DomainName'] = urlencode($domain);
            $qs2 = http_build_query($queryData2);
            $url2 = "{$this->server}{$this->apiEndpoint}?$qs2";
            $xml2 = simplexml_load_file($url2);
            return (string)($xml2->CommandResponse->DomainGetRegistrarLockResult->attributes()->RegistrarLockStatus) === 'true';
    }

    // Will toggle the current locked status for the given domain
    public function ToggleLocked(string $domain) : bool {
            $split = explode('.', $domain);
            $queryData = $this->baseApiArgs('namecheap.domains.setRegistrarLock');
            $queryData['DomainName'] = $domain;
            $queryData['LockAction'] = ($this->DomainLocked($domain) === true ? "UNLOCK" : "LOCK");
            $qs = http_build_query($queryData);
            $url = "{$this->server}{$this->apiEndpoint}?$qs";
            $numerrors = simplexml_load_file($url)->Errors->Error->count();
            return !$numerrors;
    }

    public function SupportsDnsSec() : bool {
        return false;
    }

    // GetDnsSec returns DNS Sec information about the given domain.
    public function GetDnsSec(string $sld, string $tld) : array {
        return [];
    }

    // AddDnsSec will add a new DNS Sec record to the given domain.
    public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool {
        return true;
    }

    // DeleteDnsSec will delete the give DNS Sec record for the given domain.
    public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool {
        return true;
    }

    // GetDns returns information about the nameserves for the given domain.
    public function GetDns(string $sld, string $tld) : array {
        $queryData = $this->commonApiArgs('namecheap.domains.dns.getList', $sld, $tld);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return (array)(simplexml_load_file($url)->CommandResponse->DomainDNSGetListResult->Nameserver);
    }

    // ModifyNS updates the nameservers for a given domain. $nameservers should be an array of strings with the nameserver DNS entries.
    // NameCheap's API allows a Nameservers parameter up to 1200 characters long, this function limits nameservers to a max of 12
    // to align with the eNom client.
    public function ModifyNS(string $sld, string $tld, array $nameservers) : bool {
        $queryData = $this->commonApiArgs('namecheap.domains.dns.setCustom', $sld, $tld);
        // Trim all whitespace and remove empty entries
        $nameservers = array_map(function($item) { return trim($item); }, $nameservers);
        $nameservers = array_filter($nameservers, function($item) { return $item != ''; });
        // Enforce max number of 12 nameservers
        if (count($nameservers) > 12) {
            array_splice($nameservers, 12);
        }

	    $queryData['Nameservers'] = implode(",", $nameservers);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        return simplexml_load_file($url)->Errors->count == 0;
    }

    // pricingType takes a string identifier and returns Namecheap's string parameter for the type of reseller product
    private function pricingType(string $type) : string {
        switch($type) {
            case 'new':
                return 'REGISTER';
            case 'renew':
                return 'RENEW';
            case 'transfer':
                return 'TRANSFER';
        }
    }

    // GetResellerPrice returns product information about a product type. $type can be one of 'new', 'renew', or 'transfer'.
    public function GetResellerPrice(string $tld) : array {
        $queryData = $this->baseApiArgs('namecheap.users.getPricing');
        $queryData['ProductName'] = urlencode($tld);
        $queryData['ProductType'] = 'DOMAIN';

        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        if ($tld == 'name') { $prices['new'] = 999.99; $prices['renew'] = 999.99; $prices['transfer'] = 999.99; return $prices; }
        $resultxml = simplexml_load_file($url)->CommandResponse->UserGetPricingResult->ProductType;

        foreach ($resultxml->ProductCategory as $productCategory) {
          if ($productCategory->attributes()['Name'] == 'register') {
            foreach ($productCategory->Product->Price as $price) {
              $priceattributes = $price->attributes();
              if ((int)$priceattributes['Duration'] === 1) {
                $prices['new'] = (float)$priceattributes->YourPrice + (float)$priceattributes->YourAdditonalCost;
                break;
              }
            }
          }
          if ($productCategory->attributes()['Name'] == 'renew') { 
            foreach ($productCategory->Product->Price as $price) {
              if ((int)$priceattributes['Duration'] === 1) {
                $prices['renew'] = (float)$priceattributes->YourPrice + (float)$priceattributes->YourAdditonalCost;
                break;
              }
            }
          }
          if ($productCategory->attributes()['Name'] == 'transfer') {
            foreach ($productCategory->Product->Price as $price) {
              if ((int)$priceattributes['Duration'] === 1) {
                $prices['transfer'] = (float)$priceattributes->YourPrice + (float)$priceattributes->YourAdditonalCost;
                break;
              }
            }
          }
        }
        return $prices;
    }
}
