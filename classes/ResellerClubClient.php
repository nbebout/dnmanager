<?php

class ResellerClubClient implements RegistrarClient
{
  private $server; // Server host name including schema with no trailing slash
  private $username;
  private $apikey;
  private $apiEndpoint = "/api/domains/"; // API page to call, must begin with a slash
  private $customerid;

  public function __construct(string $server, string $username, string $apikey, string $customerid)
  {
    if (empty($server) || empty($username) || empty($apikey) || empty($customerid)) {
      throw new Exception('Server, username, and apikey required for resellerclub api');
    }

    $this->server = trim($server, '/');
    $this->username = $username;
    $this->apikey = $apikey;
    $this->customerid = $customerid;
  }

  public function GetRegistrarName(): string
  {
    return "ResellerClub";
  }

  // SetApiPath will set the $apiEndpoint variable. This should only be used when testing with a stub server.
  public function SetApiPath(string $path)
  {
    if ($path[0] !== '/') {
      $path = '/' . $path;
    }
    $this->apiEndpoint = $path;
  }

  // baseApiArgs returns an array of query parameters common to all Enom API calls.
  private function baseApiArgs(): array
  {
    return [
      'auth-userid' => $this->username,
      'api-key' => $this->apikey
    ];
  }

  // commonApiArgs takes the baseApiArgs and adds sld and tld to the list of params.
  private function commonApiArgs(string $command, string $sld, string $tld): array
  {
    $data = $this->baseApiArgs();
    $data['sld'] = $sld;
    $data['tld'] = $tld;
    return $data;
  }

  // apiCall calls the given API command (e.g. "details.xml") and returns the parsed response.
  // $extraQuery is an already encoded query string appended to $queryData.
  // Throws a RuntimeException if the request failed or ResellerClub reported an error.
  private function apiCall(string $command, array $queryData, bool $post = false, string $extraQuery = ''): SimpleXMLElement
  {
    $qs = http_build_query($queryData);
    $url = "{$this->server}{$this->apiEndpoint}$command?$qs";
    if ($extraQuery !== '') {
      $url .= "&$extraQuery";
    }
    return $this->checkResponse($post ? httpPostXml($url) : httpGetXml($url), $command);
  }

  // checkResponse returns a parsed API response, or throws a RuntimeException
  // if the $command request failed ($xml is false) or ResellerClub reported an error.
  private function checkResponse($xml, string $command): SimpleXMLElement
  {
    if ($xml === false) {
      throw new RuntimeException("ResellerClub API request failed ($command)");
    }
    if (strcasecmp((string)$this->entryValue($xml, 'status'), 'error') === 0) {
      throw new RuntimeException('ResellerClub API error: ' . ($this->entryValue($xml, 'message') ?? 'unknown error'));
    }
    return $xml;
  }

  // entryValue returns the value of the top level <entry> with the given key, or null if there is none.
  private function entryValue(SimpleXMLElement $xml, string $key): ?string
  {
    foreach ($xml->entry as $entry) {
      if ((string)$entry->string[0] === $key) {
        return (string)$entry->string[1];
      }
    }
    return null;
  }

  // GetAllDomains will return information on all domains owned by $this->username.
  // The search results are paged, so pages are fetched until the reported total is reached.
  // Each search result normally carries the domain name, expiry, and lock flags; an order
  // missing any of them falls back to a details lookup.
  public function GetAllDomains(): array
  {
    $orders = array();
    $queryData = $this->baseApiArgs();
    $queryData['no-of-records'] = 100;
    $queryData['page-no'] = 1;
    $queryData['customer-id'] = $this->customerid;
    do {
      $xml = $this->apiCall('search.xml', $queryData);
      $pageCount = 0;
      foreach ($xml->entry as $entry) {
        if ($entry->hashtable) {
          $fields = [];
          foreach ($entry->hashtable->entry as $element) {
            $fields[(string)$element->string[0]] = (string)$element->string[1];
          }
          if (isset($fields['orders.orderid'])) {
            $orders[] = $fields;
            $pageCount++;
          }
        }
      }
      $total = (int)$this->entryValue($xml, 'recsindb');
      $queryData['page-no']++;
    } while ($pageCount > 0 && count($orders) < $total);

    $domainlist = [];
    foreach ($orders as $fields) {
      if (isset($fields['entity.description'], $fields['orders.endtime'], $fields['orders.transferlock'], $fields['orders.resellerlock'])) {
        $d = new Domain();
        $d->registrar = 'ResellerClub';
        $d->name = $fields['entity.description'];
        // endtime is a Unix timestamp; keep it in UTC so the server's timezone can't shift the date.
        $d->expires = gmdate('c', (int)$fields['orders.endtime']);
        $d->locked = ($fields['orders.transferlock'] === 'true' || $fields['orders.resellerlock'] === 'true');
        $d->autorenew = (($fields['orders.autorenew'] ?? '') === 'true');
      } else {
        $d = $this->domainFromDetails((int)$fields['orders.orderid']);
      }
      if ($d !== null) {
        $domainlist[] = $d;
      }
    }
    return $domainlist;
  }

  // domainFromDetails looks up a domain order's details. Returns null if the order has no expiry.
  private function domainFromDetails(int $order): ?Domain
  {
    $queryData = $this->baseApiArgs();
    $queryData['order-id'] = $order;
    $queryData['options'] = 'OrderDetails';
    $xml = $this->apiCall('details.xml', $queryData);
    $d = new Domain();
    $d->registrar = 'ResellerClub';
    $hasEndTime = false;
    foreach ($xml->entry as $element) {
      if ($element->string[0] == 'domainname') {
        $d->name = (string)$element->string[1];
      }
      if ($element->string[0] == 'endtime') {
        // endtime is a Unix timestamp; keep it in UTC so the server's timezone can't shift the date.
        $d->expires = gmdate('c', (int)$element->string[1]);
        $hasEndTime = true;
      }
      if ($element->string[0] == 'orderstatus') {
        foreach ($element->vector as $vector) {
          if ($vector->string == 'transferlock' || $vector->string == 'resellerlock') {
            $d->locked = true;
          }
        }
      }
    }
    return $hasEndTime ? $d : null;
  }

  private function GetOrderID(string $domain): int
  {
    $queryData = $this->baseApiArgs();
    $queryData['no-of-records'] = 10;
    $queryData['page-no'] = 1;
    $queryData['domain-name'] = $domain;
    $xml = $this->apiCall('search.xml', $queryData);
    foreach ($xml->entry as $entry) {
      if ($entry->hashtable) {
        foreach ($entry->hashtable->entry as $element) {
          if ($element->string[0] == 'orders.orderid') {
            return (int)$element->string[1];
          }
        }
      }
    }
    throw new RuntimeException("ResellerClub has no order for $domain");
  }

  // will return string true if domain is locked, false if domain is unlocked
  public function DomainLocked(string $domain): bool
  {
    $queryData = $this->baseApiArgs();
    $queryData['order-id'] = $this->GetOrderID($domain);
    $xml = $this->apiCall('locks.xml', $queryData);
    return ($xml->count() >= 1);
  }

  // SetLocked turns theft protection on or off for the given domain.
  public function SetLocked(string $domain, bool $locked): bool
  {
    $command = $locked ? 'enable-theft-protection' : 'disable-theft-protection';
    $queryData = $this->baseApiArgs();
    $queryData['order-id'] = $this->GetOrderID($domain);
    $xml = $this->apiCall("$command.xml", $queryData, true);
    foreach ($xml->entry as $entry) {
      if ($entry->string[0] == 'actionstatus') {
        return $entry->string[1] == 'Success';
      }
    }
    return false;
  }

  // GetDnsSec returns DNS Sec information about the given domain.
  public function GetDnsSec(string $sld, string $tld): array
  {
    $queryData = $this->baseApiArgs();
    $queryData['order-id'] = $this->GetOrderID("$sld.$tld");
    $queryData['options'] = 'DNSSECDetails';
    $xml = $this->apiCall('details.xml', $queryData);
    $keylist = [];
    foreach ($xml->entry as $entry) {
      if ($entry->string == 'dnssec') {
        foreach ($entry->list->hashtable as $hashtable) {
          $k = new DNSSecKey();
          foreach ($hashtable->entry as $element) {
            switch ($element->string[0]) {
              case 'algorithm':
                $k->algorithm = (int)$element->string[1];
                break;
              case 'digesttype':
                $k->digestType = (int)$element->string[1];
                break;
              case 'keytag':
                $k->keyTag = (int)$element->string[1];
                break;
              case 'digest':
                $k->digest = (string)$element->string[1];
                break;
            }
          }
          $keylist[] = $k;
        }
      }
    }
    return $keylist;
  }

  public function SupportsDnsSec(): bool
  {
    return true;
  }
  public function SupportsNameservers(): bool
  {
    return true;
  }
  public function SupportsToggleLocked(): bool
  {
    return true;
  }

  // commonDnsSec calls the API using params and paths that are common between the DNS SEC endpoints.
  private function commonDnsSec(string $command, string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest): SimpleXMLElement
  {
    $queryData = $this->baseApiArgs();
    $queryData['order-id'] = $this->GetOrderID("$sld.$tld");
    $queryData['attr-name1'] = 'keytag';
    $queryData['attr-value1'] = $keytag;
    $queryData['attr-name2'] = 'algorithm';
    $queryData['attr-value2'] = $alg;
    $queryData['attr-name3'] = 'digesttype';
    $queryData['attr-value3'] = $digesttype;
    $queryData['attr-name4'] = 'digest';
    $queryData['attr-value4'] = $digest;

    return $this->apiCall("$command.xml", $queryData, true);
  }

  // AddDnsSec will add a new DNS Sec record to the given domain.
  public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest): bool
  {
    $xml = $this->commonDnsSec('add-dnssec', $sld, $tld, $keytag, $alg, $digesttype, $digest);
    return $this->entryValue($xml, 'actionstatus') === 'Success';
  }

  // DeleteDnsSec will delete the give DNS Sec record for the given domain.
  public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest): bool
  {
    $xml = $this->commonDnsSec('del-dnssec', $sld, $tld, $keytag, $alg, $digesttype, $digest);
    return $this->entryValue($xml, 'actionstatus') === 'Success';
  }

  // GetDns returns information about the nameserves for the given domain.
  public function GetDns(string $sld, string $tld): array
  {
    $queryData = $this->baseApiArgs();
    $queryData['order-id'] = $this->GetOrderID("$sld.$tld");
    $queryData['options'] = 'NsDetails';
    $xml = $this->apiCall('details.xml', $queryData);
    $nameservers = array();
    foreach ($xml->entry as $entry) {
      if (substr($entry->string[0], 0, 2) == 'ns') {
        $nameservers[] = (string)$entry->string[1];
      }
    }
    return $nameservers;
  }

  // ModifyNS updates the nameservers for a given domain. $nameservers should be an array of strings with the nameserver DNS entries.
  // Enom's API only allows up to 12 nameserver records. If this function is given more than 12, the rest are ignored.
  public function ModifyNS(string $sld, string $tld, array $nameservers): bool
  {
    $queryData = $this->baseApiArgs();
    $queryData['order-id'] = $this->GetOrderID("$sld.$tld");
    // Trim all whitespace and remove empty entries
    $nameservers = array_map(function ($item) {
      return trim($item);
    }, $nameservers);
    $nameservers = array_filter($nameservers, function ($item) {
      return $item != '';
    });
    // Enforce max number of 12 nameservers
    if (count($nameservers) > 12) {
      array_splice($nameservers, 12);
    }

    $nsQuery = implode('&', array_map(function (string $nameserver): string {
      return http_build_query(['ns' => $nameserver]);
    }, $nameservers));

    $xml = $this->apiCall('modify-ns.xml', $queryData, true, $nsQuery);
    return $this->entryValue($xml, 'actionstatus') === 'Success';
  }

  // resellterTypeToInt takes a string identifier and returns enom's int parameter for the type of reseller product
  private function pricingType(string $type): int
  {
    return 0;
  }

  // GetResellerPrice returns product information about a product type. $type can be one of 'new', 'renew', or 'transfer'.
  public function GetResellerPrice(string $tld): array
  {
    $queryData = $this->baseApiArgs();
    $qs = http_build_query($queryData);
    $url = "{$this->server}/api/products/reseller-cost-price.xml?$qs";
    $xml = $this->checkResponse(httpGetXml($url), 'reseller-cost-price.xml');
    $tldtolookfor = "dot$tld";
    if ($tld == 'biz') {
      $tldtolookfor = 'dombiz';
    }
    if ($tld == 'com') {
      $tldtolookfor = 'domcno';
    }
    if ($tld == 'info') {
      $tldtolookfor = 'dominfo';
    }
    if ($tld == 'org') {
      $tldtolookfor = 'domorg';
    }
    if ($tld == 'us') {
      $tldtolookfor = 'domus';
    }
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

  private function WhatToSearchFor(string $tld): string
  {
    $donutsgroup1 = ['agency', 'business', 'center', 'city', 'company', 'directory', 'education', 'email', 'equipment', 'exposed', 'football', 'fyi', 'gallery', 'graphics', 'gratis', 'institute', 'international', 'lighting', 'management', 'network', 'photography', 'photos', 'reisen', 'report', 'run', 'schule', 'soccer', 'solutions', 'supplies', 'supply', 'support', 'systems', 'technology', 'tips', 'today'];
    $donutsgroup2 = ['academy', 'associates', 'bargains', 'bike', 'boutique', 'builders', 'cab', 'cafe', 'camera', 'camp', 'cards', 'care', 'cash', 'catering', 'chat', 'cheap', 'church', 'cleaning', 'clothing', 'coffee', 'community', 'computer', 'construction', 'contractors', 'cool', 'deals', 'digital', 'direct', 'discount', 'dog', 'domains', 'enterprises', 'estate', 'events', 'exchange', 'express', 'fail', 'farm', 'fish', 'fitness', 'florist', 'foundation', 'gifts', 'glass', 'gripe', 'guide', 'guru', 'house', 'immo', 'industries', 'kitchen', 'land', 'life', 'limited', 'marketing', 'mba', 'media', 'money', 'parts', 'place', 'plumbing', 'plus', 'productions', 'properties', 'rentals', 'repair', 'sarl', 'school', 'services', 'shoes', 'show', 'singles', 'solar', 'style', 'team', 'tools', 'town', 'toys', 'training', 'vacations', 'vision', 'watch', 'works', 'world', 'wtf', 'zone'];
    $donutsgroup3 = ['apartments', 'bingo', 'capital', 'careers', 'claims', 'clinic', 'coach', 'codes', 'condos', 'coupons', 'cruises', 'dating', 'delivery', 'dental', 'diamonds', 'engineering', 'expert', 'finance', 'financial', 'flights', 'fund', 'furniture', 'golf', 'healthcare', 'hockey', 'holdings', 'holiday', 'insure', 'jewelry', 'lease', 'legal', 'limo', 'maison', 'memorial', 'partners', 'pizza', 'recipes', 'restaurant', 'surgery', 'tax', 'taxi', 'tennis', 'theater', 'tienda', 'tours', 'university', 'ventures', 'viajes', 'villas', 'voyage'];
    $dom = ['biz', 'info', 'org', 'us'];
    if (in_array($tld, $donutsgroup1)) {
      return 'donutsgroup1';
    } else if (in_array($tld, $donutsgroup2)) {
      return 'donutsgroup2';
    } else if (in_array($tld, $donutsgroup3)) {
      return 'donutsgroup3';
    } else if (in_array($tld, $dom)) {
      return "dom$tld";
    } else if ($tld == 'com') {
      return 'domcno';
    }
    return "dot$tld";
  }

  public function GetAllPrices(array $tldarray): array
  {
    $queryData = $this->baseApiArgs();
    $qs = http_build_query($queryData);
    $url = "{$this->server}/api/products/reseller-cost-price.xml?$qs";
    $xml = $this->checkResponse(httpGetXml($url), 'reseller-cost-price.xml');
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
