<?php

class ResellerClubClient implements RegistrarClient {
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
                if ($vector->string == 'transferlock' || $vector->string == 'resellerlock') {
                  $d->locked = true;
                }
              }
            }
          }
          $domainlist []= $d;
        }
      return $domainlist;
    }

    private function GetOrderID(string $domain) : int {
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
            $command = $this->DomainLocked($domain) ? 'disable-theft-protection' : 'enable-theft-protection';
            $queryData = $this->baseApiArgs();
            $queryData['order-id'] = $this->GetOrderID($domain);
            $qs = http_build_query($queryData);
            $url = "{$this->server}{$this->apiEndpoint}$command.xml?$qs";
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, []);
            $curl_response = curl_exec($curl);
            $xml = simplexml_load_string($curl_response);
            foreach ($xml->entry as $entry) {
              if ($entry->string[0] == 'actionstatus') {
                return $entry->string[1] == 'Success';
              }
            }
            return false;
    }

    // GetDnsSec returns DNS Sec information about the given domain.
    public function GetDnsSec(string $sld, string $tld) : array {
        return [];
    }

    public function SupportsDnsSec() : bool {
        return false;
    }
    public function SupportsNameservers() : bool {
        return true;
    }
    public function SupportsToggleLocked() : bool {
        return true;
    }

    // AddDnsSec will add a new DNS Sec record to the given domain.
    public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool {
        return false;
    }

    // DeleteDnsSec will delete the give DNS Sec record for the given domain.
    public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest) : bool {
        return false;
    }

    // GetDns returns information about the nameserves for the given domain.
    public function GetDns(string $sld, string $tld) : array {
          $queryData = $this->baseApiArgs();
          $queryData['order-id'] = $this->GetOrderID("$sld.$tld");
          $queryData['options'] = 'NsDetails';
          $qs = http_build_query($queryData);
          $url = "{$this->server}{$this->apiEndpoint}details.xml?$qs";
          $xml = simplexml_load_file($url);
          $nameservers = array();
          foreach ($xml->entry as $entry) {
            if (substr($entry->string[0], 0, 2) == 'ns') { $nameservers[] = (string)$entry->string[1]; }
          }
        return $nameservers;
    }

    // ModifyNS updates the nameservers for a given domain. $nameservers should be an array of strings with the nameserver DNS entries.
    // Enom's API only allows up to 12 nameserver records. If this function is given more than 12, the rest are ignored.
    public function ModifyNS(string $sld, string $tld, array $nameservers) : bool {
        $queryData = $this->baseApiArgs();
        $queryData['order-id'] = $this->GetOrderID("$sld.$tld");
        // Trim all whitespace and remove empty entries
        $nameservers = array_map(function($item) { return trim($item); }, $nameservers);
        $nameservers = array_filter($nameservers, function($item) { return $item != ''; });
        // Enforce max number of 12 nameservers
        if (count($nameservers) > 12) {
            array_splice($nameservers, 12);
        }

       // $queryData['ns'] = implode(",", $nameservers);
        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}modify-ns.xml?$qs";
        foreach ($nameservers as $nameserver) {
          $url .= "&ns=$nameserver";
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, []);
        $curl_response = curl_exec($curl);
        $xml = simplexml_load_string($curl_response);
        foreach ($xml->entry as $entry) {
          if ($entry->string[0] == 'actionstatus') {
            return $entry->string[1] == 'Success';
          }
        }
        return false;
    }

    // resellterTypeToInt takes a string identifier and returns enom's int parameter for the type of reseller product
    private function pricingType(string $type) : int {
        return 0;
    }

    // GetResellerPrice returns product information about a product type. $type can be one of 'new', 'renew', or 'transfer'.
    public function GetResellerPrice(string $tld) : array {
          $queryData = $this->baseApiArgs();
          $qs = http_build_query($queryData);
          $url = "{$this->server}/api/products/reseller-cost-price.xml?$qs";
          $xml = simplexml_load_file($url);
          $tldtolookfor = "dot$tld";
          if ($tld == 'biz') { $tldtolookfor = 'dombiz'; }
          if ($tld == 'com') { $tldtolookfor = 'domcno'; }
          if ($tld == 'info') { $tldtolookfor = 'dominfo'; }
          if ($tld == 'org') { $tldtolookfor = 'domorg'; }
          if ($tld == 'us') { $tldtolookfor = 'domus'; }
          $prices = array();
          foreach ($xml->entry as $entry) {
            if ($entry->string == "$tldtolookfor") {
              foreach ($entry->hashtable->entry as $hashtableentry) {
                if ($hashtableentry->string == 'addnewdomain') {
                  $prices['new'] = (float)$hashtableentry->hashtable->entry->string[1];
                }
                if ($hashtableentry->string == 'renewdomain') {
                  $prices['renew'] = (float)$hashtableentry->hashtable->entry->string[1];
                }
                if ($hashtableentry->string == 'addtransferdomain') {
                  $prices['transfer'] = (float)$hashtableentry->hashtable->entry->string[1];
                }
              }
            }
          } 

        return $prices;
    }

    private function WhatToSearchFor(string $tld) : string {
            $donutsgroup1 = ['agency', 'business', 'center', 'city', 'company', 'directory', 'education', 'email', 'equipment', 'exposed', 'football', 'fyi', 'gallery', 'graphics', 'gratis', 'institute', 'international', 'lighting', 'management', 'network', 'photography', 'photos', 'reisen', 'report', 'run', 'schule', 'soccer', 'solutions', 'supplies', 'supply', 'support', 'systems', 'technology', 'tips', 'today'];
            $donutsgroup2 = ['academy', 'associates', 'bargains', 'bike', 'boutique', 'builders', 'cab', 'cafe', 'camera', 'camp', 'cards', 'care', 'cash', 'catering', 'chat', 'cheap', 'church', 'cleaning', 'clothing', 'coffee', 'community', 'computer', 'construction', 'contractors', 'cool', 'deals', 'digital', 'direct', 'discount', 'dog', 'domains', 'enterprises', 'estate', 'events', 'exchange', 'express', 'fail', 'farm', 'fish', 'fitness', 'florist', 'foundation', 'gifts', 'glass', 'gripe', 'guide', 'guru', 'house', 'immo', 'industries', 'kitchen', 'land', 'life', 'limited', 'marketing', 'mba', 'media', 'money', 'parts', 'place', 'plumbing', 'plus', 'productions', 'properties', 'rentals', 'repair', 'sarl', 'school', 'services', 'shoes', 'show', 'singles', 'solar', 'style', 'team', 'tools', 'town', 'toys', 'training', 'vacations', 'vision', 'watch', 'works', 'world', 'wtf', 'zone'];
            $donutsgroup3 = ['apartments', 'bingo', 'capital', 'careers', 'claims', 'clinic', 'coach', 'codes', 'condos', 'coupons', 'cruises', 'dating', 'delivery', 'dental', 'diamonds', 'engineering', 'expert', 'finance', 'financial', 'flights', 'fund', 'furniture', 'golf', 'healthcare', 'hockey', 'holdings', 'holiday', 'insure', 'jewelry', 'lease', 'legal', 'limo', 'maison', 'memorial', 'partners', 'pizza', 'recipes', 'restaurant', 'surgery', 'tax', 'taxi', 'tennis', 'theater', 'tienda', 'tours', 'university', 'ventures', 'viajes', 'villas', 'voyage'];
            $dom = ['biz', 'info', 'org', 'us'];
            if (in_array($tld, $donutsgroup1)) { return 'donutsgroup1'; }
            else if (in_array($tld, $donutsgroup2)) { return 'donutsgroup2'; }
            else if (in_array($tld, $donutsgroup3)) { return 'donutsgroup3'; }
            else if (in_array($tld, $dom)) { return "dom$tld"; }
            else if ($tld == 'com') { return 'domcno'; }
            return "dot$tld";
    }

    public function GetAllPrices(array $tldarray) : array {
          $queryData = $this->baseApiArgs();
          $qs = http_build_query($queryData);
          $url = "{$this->server}/api/products/reseller-cost-price.xml?$qs";
          $xml = simplexml_load_file($url);
          $prices = array();
          foreach ($tldarray as $tld) {
            foreach ($xml->entry as $entry) {
              if ($entry->string == $this->WhatToSearchFor($tld)) {
                foreach ($entry->hashtable->entry as $hashtableentry) {
                  if ($hashtableentry->string == 'addnewdomain') {
                    $prices[$tld]['new'] = (float)$hashtableentry->hashtable->entry->string[1];
                  }
                  if ($hashtableentry->string == 'renewdomain') {
                    $prices[$tld]['renew'] = (float)$hashtableentry->hashtable->entry->string[1];
                  }
                  if ($hashtableentry->string == 'addtransferdomain') {
                    $prices[$tld]['transfer'] = (float)$hashtableentry->hashtable->entry->string[1];
                  }
                }
              }
            }
          }

      return $prices;
    }
}
