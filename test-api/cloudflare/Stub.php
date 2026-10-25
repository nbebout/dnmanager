<?php
header('Content-Type: application/json');

// Routes /accounts/{account_id}/registrar/registrations[/{domain}],
// /accounts/{account_id}/registrar/domains[/{domain}] (the deprecated API,
// still used for lock changes), /accounts/{account_id}/registrar/domain-check,
// /zones, and /zones/{zone_id}/dnssec via PATH_INFO.
$path = $_SERVER['PATH_INFO'] ?? '';

$domains = [
    ['name' => 'domain4.com', 'expires_at' => '2027-03-14T00:00:00Z', 'locked' => true, 'auto_renew' => true, 'current_registrar' => 'Cloudflare'],
    ['name' => 'domain5.net', 'expires_at' => '2026-12-01T00:00:00Z', 'locked' => false, 'auto_renew' => false, 'current_registrar' => 'Cloudflare'],
    ['name' => 'domain6.org', 'expires_at' => null, 'locked' => true, 'auto_renew' => true, 'current_registrar' => 'Cloudflare', 'status' => 'transfer_pending'],
];

// registration converts a domain to the registrations API's format.
function registration($domain)
{
    return [
        'domain_name' => $domain['name'],
        'expires_at' => $domain['expires_at'],
        'created_at' => '2024-01-01T00:00:00Z',
        'locked' => $domain['locked'],
        'auto_renew' => $domain['auto_renew'],
        'privacy_mode' => 'redaction',
        'status' => isset($domain['status']) ? $domain['status'] : 'active',
    ];
}

$zones = [
    ['id' => 'zone4', 'name' => 'domain4.com', 'dnssec' => 'active'],
    ['id' => 'zone5', 'name' => 'domain5.net', 'dnssec' => 'disabled'],
];

if ($path === '/zones') {
    $result = array_map(function ($zone) {
        return ['id' => $zone['id'], 'name' => $zone['name']];
    }, $zones);
    $resultInfo = ['page' => 1, 'per_page' => 50, 'count' => count($zones), 'total_count' => count($zones), 'total_pages' => 1];
    echo json_encode(['success' => true, 'errors' => [], 'messages' => [], 'result' => $result, 'result_info' => $resultInfo]);
} elseif (preg_match('#^/zones/([^/]+)/dnssec$#', $path, $matches)) {
    foreach ($zones as $zone) {
        if ($zone['id'] === $matches[1]) {
            echo json_encode(['success' => true, 'errors' => [], 'messages' => [], 'result' => ['status' => $zone['dnssec']]]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => [['code' => 1001, 'message' => 'Zone not found']], 'messages' => [], 'result' => null]);
} elseif (preg_match('#^/accounts/[^/]+/registrar/registrations/?$#', $path)) {
    // The cursor is the offset of the page, so paging can be exercised by
    // asking for fewer domains per page than there are.
    $perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 20;
    $offset = isset($_GET['cursor']) ? (int)$_GET['cursor'] : 0;
    $result = array_map('registration', array_slice($domains, $offset, $perPage));
    $cursor = $offset + $perPage < count($domains) ? (string)($offset + $perPage) : '';
    $resultInfo = ['count' => count($result), 'cursor' => $cursor, 'per_page' => $perPage];
    echo json_encode(['success' => true, 'errors' => [], 'messages' => [], 'result' => $result, 'result_info' => $resultInfo]);
} elseif (preg_match('#^/accounts/[^/]+/registrar/registrations/([^/]+)$#', $path, $matches)) {
    foreach ($domains as $domain) {
        if ($domain['name'] === $matches[1]) {
            echo json_encode(['success' => true, 'errors' => [], 'messages' => [], 'result' => registration($domain)]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => [['code' => 10000, 'message' => 'Domain not found']], 'messages' => [], 'result' => null]);
} elseif (preg_match('#^/accounts/[^/]+/registrar/domain-check$#', $path)) {
    // Prices a few TLDs; any other TLD is reported as unsupported.
    $tldPrices = ['com' => ['10.44', '10.44'], 'net' => ['11.84', '11.84'], 'org' => ['7.50', '10.11']];
    $body = json_decode(file_get_contents('php://input'), true);
    $result = [];
    foreach (isset($body['domains']) ? $body['domains'] : [] as $name) {
        $tld = substr($name, strpos($name, '.') + 1);
        if (isset($tldPrices[$tld])) {
            $pricing = ['currency' => 'USD', 'registration_cost' => $tldPrices[$tld][0], 'renewal_cost' => $tldPrices[$tld][1]];
            $result[] = ['name' => $name, 'registrable' => true, 'tier' => 'standard', 'pricing' => $pricing];
        } else {
            $result[] = ['name' => $name, 'registrable' => false, 'tier' => 'standard', 'reason' => 'extension_not_supported_via_api'];
        }
    }
    echo json_encode(['success' => true, 'errors' => [], 'messages' => [], 'result' => ['domains' => $result]]);
} elseif (preg_match('#^/accounts/[^/]+/registrar/domains/?$#', $path)) {
    $result = $domains;
    $resultInfo = ['page' => 0, 'per_page' => 50, 'count' => count($domains), 'total_count' => count($domains), 'total_pages' => 1];
    echo json_encode(['success' => true, 'errors' => [], 'messages' => [], 'result' => $result, 'result_info' => $resultInfo]);
} elseif (preg_match('#^/accounts/[^/]+/registrar/domains/([^/]+)$#', $path, $matches)) {
    foreach ($domains as $domain) {
        if ($domain['name'] === $matches[1]) {
            echo json_encode(['success' => true, 'errors' => [], 'messages' => [], 'result' => $domain]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => [['code' => 1000, 'message' => 'Domain not found']], 'messages' => [], 'result' => null]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => [['code' => 7003, 'message' => 'No route']], 'messages' => [], 'result' => null]);
}
