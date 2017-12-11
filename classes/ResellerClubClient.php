<?php

class ResellerClubClient implements RegistrarClient {
    const RESPONSE_TYPE = 'XML';

    private $server; // Server host name including schema with no trailing slash
    private $username;
    private $apikey;
    private $apiEndpoint = "/api/domains/"; // API page to call, must begin with a slash

    public function __construct(string $server, string $username, string $apikey) {
        if (empty($server) || empty($username) || empty($apikey)) {
            throw new Exception('Server, username, and apikey required for resellerclub api');
        }

        $this->server = trim($server, '/');
        $this->username = $username;
        $this->apikey = $apikey;
    }

    // SetApiPath will set the $apiEndpoint variable. This should only be used when testing with a stub server.
    public function SetApiPath(string $path) {
        if ($path[0] !== '/') {
            $path = '/'.$path;
        }
        $this->apiEndpoint = $path;
    }

    // baseApiArgs returns an array of query parameters common to all Enom API calls.
    private function baseApiArgs() : array {
        return [
            'auth-userid' => $this->username,
            'api-key' => $this->apikey
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
        $orders = array();
        $queryData = $this->baseApiArgs();
        $queryData['no-of-records'] = 10;
        $queryData['page-no'] = 1;
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}search.xml?$qs";
        $xml = simplexml_load_file($url);
        foreach ($xml->entry as $entry) {
          if ($entry->hashtable) {
            foreach ($entry->hashtable->entry as $element) {
              if ($element->string[0] == 'orders.orderid') {
                $orders[] = (int)$element->string[1];
              }
            }
          }
        }
        $domainlist = [];
        foreach ($orders as $order) {
          $queryData = $this->baseApiArgs();
          $queryData['order-id'] = $order;
          $queryData['options'] = 'OrderDetails';
          $qs = http_build_query($queryData);
          $url = "{$this->server}{$this->apiEndpoint}details.xml?$qs";
          $xml = simplexml_load_file($url);
          $d = new Domain();
          $d->registrar = 'ResellerClub';
          foreach ($xml->entry as $element) {
            if ($element->string[0] == 'domainname') { $d->name = (string)$element->string[1]; }
            if ($element->string[0] == 'endtime') { $d->expires = date("m/d/Y", (int)$element->string[1]); }
            if ($element->string[0] == 'orderstatus') {
              foreach ($element->vector as $vector) {
                if ($vector->string == 'transferlock') { $d->locked = true; }
                if ($vector->string == 'resellerlock') { $d->locked = true; }
              }
            }
          }
          $domainlist []= $d;
        }
      return $domainlist;
    }

    public function GetOrderID(string $domain) : int {
        $queryData = $this->baseApiArgs();
        $queryData['no-of-records'] = 10;
        $queryData['page-no'] = 1;
        $queryData['domain-name'] = urlencode($domain);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}search.xml?$qs";
        $xml = simplexml_load_file($url);
        foreach ($xml->entry as $entry) {
          if ($entry->hashtable) {
            foreach ($entry->hashtable->entry as $element) {
              if ($element->string[0] == 'orders.orderid') { return (int)$element->string[1]; }
            }
          }
        }
    }
              
    // will return string true if domain is locked, false if domain is unlocked
    public function DomainLocked(string $domain) : bool {
            $queryData = $this->baseApiArgs();
            $queryData['order-id'] = $this->GetOrderID($domain);
            $qs = http_build_query($queryData);
            $url = "{$this->server}{$this->apiEndpoint}locks.xml?$qs";
            $xml = simplexml_load_file($url);
            return ($xml->count() >= 1);
    }

    // Will toggle the current locked status for the given domain
    public function ToggleLocked(string $domain) : bool {
            if ($this->DomainLocked($domain)) { $command = 'enable-theft-protection'; }
            elseif (!$this->DomainLocked($domain)) { $command = 'disable-theft-protection'; }
            $queryData = $this->baseApiArgs();
            $queryData['order-id'] = $this->GetOrderID($domain);
            $qs = http_build_query($queryData);
            $url = "{$this->server}{$this->apiEndpoint}$command.xml?$qs";
 var_dump($url);
            $xml = simplexml_load_file($url);
            var_dump($xml);
            //return simplexml_load_file($url)->RRPCode == '200';
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
        return false;
    }
    public function SupportsNameservers() : bool {
        return false;
    }
    public function SupportsToggleLocked() : bool {
        return false;
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
}