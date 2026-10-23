<?php

class NameCheapClient implements RegistrarClient
{
    private $server; // Server host name including schema with no trailing slash
    private $username;
    private $apiKey;
    private $clientIP;

    private $apiEndpoint = "/xml.response"; // API page to call, must begin with a slash

    public function __construct(array $options)
    {
        $server = $options['server'] ?? '';
        $username = $options['username'] ?? '';
        $apiKey = $options['apikey'] ?? '';
        $clientIP = $options['clientip'] ?? '';

        if (empty($server) || empty($username) || empty($apiKey) || empty($clientIP)) {
            throw new Exception('Server, username, api key, and client ip required for namecheap api');
        }
        // Namecheap checks ClientIp against the API whitelist, and only accepts IPv4.
        if (filter_var($clientIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new Exception("clientip must be this server's public IPv4 address");
        }

        $this->server = trim($server, '/');
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->clientIP = $clientIP;
    }

    public function GetRegistrarName(): string
    {
        return "Namecheap";
    }

    // SetApiPath will set the $apiEndpoint variable. This should only be used when testing with a stub server.
    public function SetApiPath(string $path)
    {
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        $this->apiEndpoint = $path;
    }

    // baseApiArgs returns an array of query parameters common to all NameCheap API calls.
    private function baseApiArgs(string $command): array
    {
        return [
            'Command' => $command,
            'ApiUser' => $this->username,
            'UserName' => $this->username,
            'ApiKey' => $this->apiKey,
            'ClientIp' => $this->clientIP
        ];
    }

    // commonApiArgs takes the baseApiArgs and adds sld and tld to the list of params.
    private function commonApiArgs(string $command, string $sld, string $tld): array
    {
        $data = $this->baseApiArgs($command);
        $data['SLD'] = $sld;
        $data['TLD'] = $tld;
        return $data;
    }

    // apiGet calls the given API command and returns the parsed response.
    // Throws a RuntimeException if the request failed or Namecheap reported an error.
    private function apiGet(array $queryData): SimpleXMLElement
    {
        $qs = http_build_query($queryData);
        $xml = httpGetXml("{$this->server}{$this->apiEndpoint}?$qs");
        if ($xml === false) {
            throw new RuntimeException("Namecheap API request failed ({$queryData['Command']})");
        }
        if ((string)$xml['Status'] !== 'OK') {
            $messages = array_map('strval', iterator_to_array($xml->Errors->Error, false));
            throw new RuntimeException('Namecheap API error: ' . (implode('; ', $messages) ?: 'unknown error'));
        }
        return $xml;
    }

    // GetAllDomains will return information on all domains owned by $this->username.
    // The list is paged, so pages are fetched until the reported total is reached.
    public function GetAllDomains(): array
    {
        $queryData = $this->baseApiArgs('namecheap.domains.getList');
        $queryData['PageSize'] = 100;
        $queryData['Page'] = 1;
        $domainlist = [];
        do {
            $xml = $this->apiGet($queryData);
            $domainlistXML = $xml->CommandResponse->DomainGetListResult->Domain;
            if (count($domainlistXML) === 0) {
                break;
            }
            foreach ($domainlistXML as $domain) {
                $attributes = $domain->attributes();
                $d = new Domain();
                $d->registrar = 'Namecheap';
                $d->name = (string)$attributes->Name;
                $d->locked = ((string)$attributes->IsLocked === 'true');
                $d->expires = (string)$attributes->Expires;
                $d->autorenew = ($attributes->AutoRenew == 'true');
                $domainlist[] = $d;
            }
            $totalItems = (int)$xml->CommandResponse->Paging->TotalItems;
            $queryData['Page']++;
        } while (count($domainlist) < $totalItems);
        return $domainlist;
    }

    // will return string true if domain is locked, false if domain is unlocked
    public function DomainLocked(string $domain): bool
    {
        $queryData2 = $this->baseApiArgs('namecheap.domains.getRegistrarLock');
        $queryData2['DomainName'] = $domain;
        $xml2 = $this->apiGet($queryData2);
        return (string)($xml2->CommandResponse->DomainGetRegistrarLockResult->attributes()->RegistrarLockStatus) === 'true';
    }

    // SetLocked locks or unlocks the given domain.
    public function SetLocked(string $domain, bool $locked): bool
    {
        $queryData = $this->baseApiArgs('namecheap.domains.setRegistrarLock');
        $queryData['DomainName'] = $domain;
        $queryData['LockAction'] = $locked ? "LOCK" : "UNLOCK";
        $this->apiGet($queryData);
        return true;
    }

    public function SupportsDnsSec(): bool
    {
        return false;
    }
    public function SupportsNameservers(): bool
    {
        return true;
    }
    public function SupportsToggleLocked(): bool
    {
        return true;
    }

    // GetDnsSec returns DNS Sec information about the given domain.
    public function GetDnsSec(string $sld, string $tld): array
    {
        return [];
    }

    // AddDnsSec will add a new DNS Sec record to the given domain.
    public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest): bool
    {
        return true;
    }

    // DeleteDnsSec will delete the give DNS Sec record for the given domain.
    public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest): bool
    {
        return true;
    }

    // GetDns returns information about the nameserves for the given domain.
    public function GetDns(string $sld, string $tld): array
    {
        $xml = $this->apiGet($this->commonApiArgs('namecheap.domains.dns.getList', $sld, $tld));
        return array_map('strval', iterator_to_array($xml->CommandResponse->DomainDNSGetListResult->Nameserver, false));
    }

    // ModifyNS updates the nameservers for a given domain. $nameservers should be an array of strings with the nameserver DNS entries.
    // NameCheap's API allows a Nameservers parameter up to 1200 characters long, this function limits nameservers to a max of 12
    // to align with the eNom client.
    public function ModifyNS(string $sld, string $tld, array $nameservers): bool
    {
        $queryData = $this->commonApiArgs('namecheap.domains.dns.setCustom', $sld, $tld);
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

        $queryData['Nameservers'] = implode(",", $nameservers);
        $this->apiGet($queryData);
        return true;
    }

    // pricingType takes a string identifier and returns Namecheap's string parameter for the type of reseller product
    private function pricingType(string $type): string
    {
        switch ($type) {
            case 'new':
                return 'REGISTER';
            case 'renew':
                return 'RENEW';
            case 'transfer':
                return 'TRANSFER';
        }
    }

    // GetResellerPrice returns product information about a product type. $type can be one of 'new', 'renew', or 'transfer'.
    public function GetResellerPrice(string $tld): array
    {
        $prices = [];
        $queryData = $this->baseApiArgs('namecheap.users.getPricing');
        $queryData['ProductName'] = $tld;
        $queryData['ProductType'] = 'DOMAIN';

        $qs = http_build_query($queryData);
        $url = "{$this->server}{$this->apiEndpoint}?$qs";
        $respxml = httpGetXml($url);
        if ($respxml === false) {
            throw new RuntimeException("Namecheap pricing request failed ($tld)");
        }
        $resultxml = $respxml->CommandResponse->UserGetPricingResult->ProductType;
        if (!isset($resultxml->ProductCategory)) {
            return $prices; // Namecheap has no pricing for this TLD
        }

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
                    $priceattributes = $price->attributes();
                    if ((int)$priceattributes['Duration'] === 1) {
                        $prices['renew'] = (float)$priceattributes->YourPrice + (float)$priceattributes->YourAdditonalCost;
                        break;
                    }
                }
            }
            if ($productCategory->attributes()['Name'] == 'transfer') {
                foreach ($productCategory->Product->Price as $price) {
                    $priceattributes = $price->attributes();
                    if ((int)$priceattributes['Duration'] === 1) {
                        $prices['transfer'] = (float)$priceattributes->YourPrice + (float)$priceattributes->YourAdditonalCost;
                        break;
                    }
                }
            }
        }
        return $prices;
    }

    public function GetAllPrices(array $tldarray): array
    {
        $prices = array();
        foreach ($tldarray as $tld) {
            $prices[$tld] = $this->GetResellerPrice($tld);
        }
        return $prices;
    }
}
