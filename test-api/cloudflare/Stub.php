<?php
header('Content-Type: application/json');

// Routes /accounts/{account_id}/registrar/domains[/{domain}], /zones, and
// /zones/{zone_id}/dnssec via PATH_INFO.
$path = $_SERVER['PATH_INFO'] ?? '';

$domains = [
    ['name' => 'domain4.com', 'expires_at' => '2027-03-14T00:00:00Z', 'locked' => true, 'auto_renew' => true, 'current_registrar' => 'Cloudflare'],
    ['name' => 'domain5.net', 'expires_at' => '2026-12-01T00:00:00Z', 'locked' => false, 'auto_renew' => false, 'current_registrar' => 'Cloudflare'],
];

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
