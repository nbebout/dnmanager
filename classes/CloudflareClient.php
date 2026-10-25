<?php

// CloudflareClient talks to the Cloudflare Registrar API (v4). Domains
// registered with Cloudflare must use Cloudflare's nameservers, and DNSSEC is
// managed on the zone rather than through the registrar, so those features are
// reported as unsupported. The zone's DNSSEC status can still be read with
// GetDnsSecStatuses. Cloudflare sells at cost and has no price list, so
// pricing is looked up with its domain check (see GetAllPrices).
class CloudflareClient implements RegistrarClient
{
    private $server; // Server host name including schema with no trailing slash
    private $accountId;
    private $apiToken;

    private $apiEndpoint = "/client/v4"; // API base path, must begin with a slash

    public function __construct(array $options)
    {
        $server = !empty($options['server']) ? $options['server'] : 'https://api.cloudflare.com';
        $accountId = $options['account-id'] ?? '';
        $apiToken = $options['apitoken'] ?? '';

        if (empty($accountId) || empty($apiToken)) {
            throw new Exception('Account id and api token required for cloudflare api');
        }

        $this->server = trim($server, '/');
        $this->accountId = $accountId;
        $this->apiToken = $apiToken;
    }

    public function GetRegistrarName(): string
    {
        return "Cloudflare";
    }

    // SetApiPath will set the $apiEndpoint variable. This should only be used when testing with a stub server.
    public function SetApiPath(string $path)
    {
        $this->apiEndpoint = rtrim('/' . ltrim($path, '/'), '/');
    }

    // registrarPath returns the API path of one of the account's registrar
    // endpoints (e.g. 'registrations'), or of a single domain under it when
    // $domain is given.
    private function registrarPath(string $endpoint, string $domain = ''): string
    {
        $path = '/accounts/' . rawurlencode($this->accountId) . '/registrar/' . $endpoint;
        return $domain === '' ? $path : $path . '/' . rawurlencode($domain);
    }

    // apiUrl returns the full URL of the given API path (relative to the API base path).
    private function apiUrl(string $path, array $query = []): string
    {
        $url = "{$this->server}{$this->apiEndpoint}{$path}";
        return empty($query) ? $url : $url . '?' . http_build_query($query);
    }

    // apiRequest calls the given API path (relative to the API base path) and
    // returns the decoded response.
    // Throws a RuntimeException if the request failed or Cloudflare reported an error.
    private function apiRequest(string $method, string $path, array $query = [], $body = null): array
    {
        $response = httpRequestJson($method, $this->apiUrl($path, $query), ["Authorization: Bearer {$this->apiToken}"], $body);
        return $this->checkResponse($response);
    }

    // checkResponse returns a decoded API response, or throws a RuntimeException
    // if the request failed ($response is false) or Cloudflare reported an error.
    private function checkResponse($response): array
    {
        if ($response === false) {
            throw new RuntimeException('Cloudflare API request failed');
        }
        if (empty($response['success'])) {
            $messages = array_column($response['errors'] ?? [], 'message');
            throw new RuntimeException('Cloudflare API error: ' . (implode('; ', $messages) ?: 'unknown error'));
        }
        return $response;
    }

    // GetDomain returns the raw Cloudflare registration for a single domain.
    public function GetDomain(string $domain): array
    {
        $response = $this->apiRequest('GET', $this->registrarPath('registrations', $domain));
        if (!is_array($response['result'] ?? null)) {
            throw new RuntimeException("Cloudflare returned no data for $domain");
        }
        return $response['result'];
    }

    // GetAllDomains will return information on all domains registered in the Cloudflare account.
    // The registrations list is paged by cursor: each response gives the cursor for the next
    // page, and an empty cursor means the last page. Throws rather than returning a partial
    // list if any page fails.
    public function GetAllDomains(): array
    {
        $domainlist = [];
        $query = ['per_page' => 50];
        $seenCursors = [];
        do {
            $response = $this->apiRequest('GET', $this->registrarPath('registrations'), $query);
            foreach ($response['result'] ?? [] as $registration) {
                $d = new Domain();
                $d->registrar = 'Cloudflare';
                $d->name = $registration['domain_name'] ?? '';
                $d->locked = !empty($registration['locked']);
                // Null while a registration or transfer is still pending.
                $d->expires = $registration['expires_at'] ?? '';
                $d->autorenew = !empty($registration['auto_renew']);
                $domainlist[] = $d;
            }
            $cursor = (string)($response['result_info']['cursor'] ?? '');
            if (isset($seenCursors[$cursor])) {
                throw new RuntimeException('Cloudflare returned the same page cursor twice');
            }
            $seenCursors[$cursor] = true;
            $query['cursor'] = $cursor;
        } while ($cursor !== '');
        return $domainlist;
    }

    // DomainLocked returns true if the domain is locked, false if it is unlocked. Throws if the
    // lock status could not be retrieved.
    public function DomainLocked(string $domain): bool
    {
        return !empty($this->GetDomain($domain)['locked']);
    }

    // SetLocked locks or unlocks the given domain. Throws on failure. The
    // registrations API can't change the lock, so this still uses the
    // deprecated registrar domains API.
    public function SetLocked(string $domain, bool $locked): bool
    {
        $this->apiRequest('PUT', $this->registrarPath('domains', $domain), [], ['locked' => $locked]);
        return true;
    }

    // getZoneIds returns the id of every zone in the account, keyed by the
    // lowercased zone name. Unlike the registrar API, the zones API's page
    // numbers start at 1. Throws if any page fails.
    private function getZoneIds(): array
    {
        $zoneIds = [];
        $query = ['account.id' => $this->accountId, 'per_page' => 50, 'page' => 1];
        do {
            $response = $this->apiRequest('GET', '/zones', $query);
            foreach ($response['result'] ?? [] as $zone) {
                if (isset($zone['name'], $zone['id'])) {
                    $zoneIds[strtolower($zone['name'])] = $zone['id'];
                }
            }
            $totalPages = (int)($response['result_info']['total_pages'] ?? 0);
            $query['page']++;
        } while ($query['page'] <= $totalPages);
        return $zoneIds;
    }

    // GetDnsSecStatuses returns the DNSSEC status of each given domain's zone,
    // keyed by the lowercased domain name. The status is one of Cloudflare's
    // values: active, pending, disabled, pending-disabled, or error. Domains
    // without a zone in the account are left out. The zones are looked up
    // concurrently. Needs an api token that can read zones and DNS. Throws if
    // a lookup fails.
    public function GetDnsSecStatuses(array $domains): array
    {
        $zoneIds = $this->getZoneIds();
        $urls = [];
        foreach ($domains as $domain) {
            $name = strtolower($domain);
            if (isset($zoneIds[$name])) {
                $urls[$name] = $this->apiUrl('/zones/' . rawurlencode($zoneIds[$name]) . '/dnssec');
            }
        }

        $statuses = [];
        foreach (httpGetJsonMulti($urls, ["Authorization: Bearer {$this->apiToken}"]) as $name => $response) {
            $response = $this->checkResponse($response);
            $statuses[$name] = (string)($response['result']['status'] ?? '');
        }
        return $statuses;
    }

    public function SupportsDnsSec(): bool
    {
        return false;
    }
    public function SupportsNameservers(): bool
    {
        return false;
    }
    public function SupportsToggleLocked(): bool
    {
        return true;
    }

    public function GetDnsSec(string $sld, string $tld): array
    {
        return [];
    }

    public function AddDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest): bool
    {
        return false;
    }

    public function DeleteDnsSec(string $sld, string $tld, string $keytag, int $alg, string $digesttype, string $digest): bool
    {
        return false;
    }

    public function GetDns(string $sld, string $tld): array
    {
        return [];
    }

    public function ModifyNS(string $sld, string $tld, array $nameservers): bool
    {
        return false;
    }

    // GetResellerPrice returns the register, renew, and transfer prices for a TLD.
    public function GetResellerPrice(string $tld): array
    {
        return $this->GetAllPrices([$tld])[$tld] ?? [];
    }

    // GetAllPrices returns the register, renew, and transfer prices for each TLD.
    // Cloudflare has no price list, but its domain check reports the price of any
    // registrable domain, so each TLD is priced by checking a random name in it
    // that's almost certainly unregistered. The check takes up to 20 domains per
    // request. Cloudflare charges a one year renewal for a transfer, so the
    // transfer price is the renewal price. TLDs Cloudflare doesn't sell (or can't
    // price through the API) are left out. Needs an api token with Registrar edit
    // permission. Throws if a check fails.
    public function GetAllPrices(array $tldarray): array
    {
        $tldsByDomain = [];
        foreach ($tldarray as $tld) {
            $tldsByDomain['dnmanager-' . bin2hex(random_bytes(6)) . '.' . strtolower($tld)] = $tld;
        }

        $prices = [];
        foreach (array_chunk(array_keys($tldsByDomain), 20) as $chunk) {
            $response = $this->apiRequest('POST', $this->registrarPath('domain-check'), [], ['domains' => $chunk]);
            foreach ($response['result']['domains'] ?? [] as $result) {
                $tld = $tldsByDomain[strtolower($result['name'] ?? '')] ?? null;
                // A premium name would be priced above the TLD's normal price.
                if ($tld === null || empty($result['registrable']) || ($result['tier'] ?? 'standard') !== 'standard') {
                    continue;
                }
                $pricing = $result['pricing'] ?? [];
                if (($pricing['currency'] ?? 'USD') !== 'USD' || !isset($pricing['registration_cost'], $pricing['renewal_cost'])) {
                    continue;
                }
                $prices[$tld] = [
                    'new' => (float)$pricing['registration_cost'],
                    'renew' => (float)$pricing['renewal_cost'],
                    'transfer' => (float)$pricing['renewal_cost'],
                ];
            }
        }
        return $prices;
    }
}
