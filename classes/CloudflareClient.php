<?php

// CloudflareClient talks to the Cloudflare Registrar API (v4). Domains
// registered with Cloudflare must use Cloudflare's nameservers, and DNSSEC is
// managed on the zone rather than through the registrar, so those features are
// reported as unsupported. Cloudflare sells at cost and has no reseller
// pricing API, so pricing lookups return no data.
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

    // apiRequest calls the given registrar API path (relative to the account's
    // registrar/domains endpoint) and returns the decoded response.
    // Returns null if the request failed or Cloudflare reported an error.
    private function apiRequest(string $method, string $path = '', array $query = [], $body = null)
    {
        $url = "{$this->server}{$this->apiEndpoint}/accounts/" . rawurlencode($this->accountId) . "/registrar/domains{$path}";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $response = httpRequestJson($method, $url, ["Authorization: Bearer {$this->apiToken}"], $body);
        if ($response === false || empty($response['success'])) {
            return null;
        }
        return $response;
    }

    // GetDomain returns the raw Cloudflare registrar information for a single domain, or null on failure.
    public function GetDomain(string $domain)
    {
        $response = $this->apiRequest('GET', '/' . rawurlencode($domain));
        return $response['result'] ?? null;
    }

    // GetAllDomains will return information on all domains registered in the Cloudflare account.
    // The registrar API's page numbers start at 0, so paging is driven by the page number and
    // total_count the API reports back rather than by assuming a starting page.
    public function GetAllDomains(): array
    {
        $domainlist = [];
        $query = ['per_page' => 50];
        do {
            $response = $this->apiRequest('GET', '', $query);
            if ($response === null || empty($response['result'])) {
                break;
            }
            foreach ($response['result'] as $domain) {
                $d = new Domain();
                $d->registrar = 'Cloudflare';
                $d->name = $domain['name'] ?? '';
                $d->locked = !empty($domain['locked']);
                $d->expires = $domain['expires_at'] ?? '';
                $d->autorenew = !empty($domain['auto_renew']);
                $domainlist[] = $d;
            }
            $totalCount = (int)($response['result_info']['total_count'] ?? 0);
            $query['page'] = (int)($response['result_info']['page'] ?? 0) + 1;
        } while (count($domainlist) < $totalCount);
        return $domainlist;
    }

    // DomainLocked returns true if the domain is locked, false if it is unlocked.
    public function DomainLocked(string $domain): bool
    {
        $result = $this->GetDomain($domain);
        return !empty($result['locked']);
    }

    // ToggleLocked will toggle the current locked status for the given domain.
    public function ToggleLocked(string $domain): bool
    {
        $result = $this->GetDomain($domain);
        if ($result === null) {
            return false;
        }
        $response = $this->apiRequest('PUT', '/' . rawurlencode($domain), [], ['locked' => empty($result['locked'])]);
        return $response !== null;
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

    public function GetResellerPrice(string $tld): array
    {
        return [];
    }

    public function GetAllPrices(array $tldarray): array
    {
        return [];
    }
}
