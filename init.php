<?php
$config = [];
require_once('config.php');
require_once('classes/init.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clients = [];

// Setup enom client
if (!empty($config['enom']['server'])) {
    $enomClient = new EnomClient($config['enom']['server'], $config['enom']['username'], $config['enom']['password']);
    if (defined('TESTING') && isset($config['enom']['api_path'])) {
        $enomClient->SetApiPath($config['enom']['api_path']);
    }
    $clients['enom'] = $enomClient;
}

// Setup Namecheap client
if (!empty($config['namecheap']['server'])) {
    $namecheapClient = new NameCheapClient($config['namecheap']);
    if (defined('TESTING') && isset($config['namecheap']['api_path'])) {
        $namecheapClient->SetApiPath($config['namecheap']['api_path']);
    }
    $clients['namecheap'] = $namecheapClient;
}

// Setup ResellerClub client
if (!empty($config['resellerclub']['server'])) {
    $resellerclubClient = new ResellerClubClient($config['resellerclub']['server'], $config['resellerclub']['username'], $config['resellerclub']['apikey'], $config['resellerclub']['customer-id']);
    if (defined('TESTING') && isset($config['resellerclub']['api_path'])) {
        $resellerclubClient->SetApiPath($config['resellerclub']['api_path']);
    }
    $clients['resellerclub'] = $resellerclubClient;
}

function sortDomainsByName(array &$domains)
{
    usort($domains, function ($a, $b): int {
        return strcmp($a->name, $b->name);
    });
}

function sortDomainsByExpires(array &$domains)
{
    usort($domains, function ($a, $b): int {
        return (strtotime($a->expires) < strtotime($b->expires)) ? -1 : 1;
    });
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function buildUrl(string $path, array $params = []): string
{
    if (empty($params)) {
        return h($path);
    }

    return h($path . '?' . http_build_query($params));
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function requirePostRequest(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }
}

function requireValidCsrfToken(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !is_string($sessionToken) || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function rejectInvalidInput(string $message): void
{
    http_response_code(400);
    exit($message);
}

function requireValidRegistrar($registrar): string
{
    global $clients;

    if (!is_string($registrar) || !isset($clients[$registrar])) {
        rejectInvalidInput('Invalid registrar');
    }

    return $registrar;
}

function requireValidDomainPart($value, string $field): string
{
    if (!is_string($value) || $value === '' || strlen($value) > 63 || !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $value)) {
        rejectInvalidInput("Invalid $field");
    }

    return $value;
}

function requireValidDomain($domain): string
{
    if (!is_string($domain) || $domain === '' || strlen($domain) > 253 || substr_count($domain, '.') < 1) {
        rejectInvalidInput('Invalid domain');
    }

    $labels = explode('.', $domain);
    foreach ($labels as $label) {
        requireValidDomainPart($label, 'domain');
    }

    return $domain;
}

function requireValidTld($tld): string
{
    if (!is_string($tld) || $tld === '') {
        rejectInvalidInput('Invalid tld');
    }

    foreach (explode('.', $tld) as $label) {
        requireValidDomainPart($label, 'tld');
    }

    return $tld;
}

function splitDomain(string $domain): array
{
    $domain = requireValidDomain($domain);
    $labels = explode('.', strtolower($domain));

    // Registrars need the registrable name (SLD) and the complete public
    // suffix (TLD). Keep the common multi-label suffixes together rather than
    // treating only the final label as the TLD.
    $multiLabelSuffixes = [
        'co.uk', 'org.uk', 'me.uk', 'ac.uk', 'gov.uk', 'net.uk',
        'com.au', 'net.au', 'org.au', 'edu.au', 'gov.au',
        'co.nz', 'net.nz', 'org.nz', 'co.jp', 'co.kr', 'co.in',
        'com.br', 'com.cn', 'com.mx', 'com.sg', 'com.tr', 'com.tw',
    ];
    $suffix = end($labels);
    $lastTwo = count($labels) >= 2 ? implode('.', array_slice($labels, -2)) : '';
    if (in_array($lastTwo, $multiLabelSuffixes, true)) {
        $suffix = $lastTwo;
        $sldLabels = array_slice($labels, 0, -2);
    } else {
        $sldLabels = array_slice($labels, 0, -1);
    }

    if (count($sldLabels) === 0) {
        rejectInvalidInput('Invalid domain');
    }

    // A registrar API expects the registrable label as SLD. Reject domains
    // with an extra label rather than silently sending an ambiguous value.
    if (count($sldLabels) !== 1) {
        rejectInvalidInput('Invalid registrable domain');
    }

    return [$sldLabels[0], $suffix];
}

function requireValidNameservers($value): array
{
    if (!is_array($value) || count($value) > 12) {
        rejectInvalidInput('Invalid nameservers');
    }

    $nameservers = [];
    foreach ($value as $nameserver) {
        if (!is_string($nameserver)) {
            rejectInvalidInput('Invalid nameserver');
        }

        $nameserver = strtolower(trim($nameserver));
        if ($nameserver === '') {
            continue;
        }
        requireValidDomain($nameserver);
        $nameservers[] = $nameserver;
    }

    if (count($nameservers) === 0) {
        rejectInvalidInput('At least one nameserver is required');
    }

    return $nameservers;
}

function requireValidDnssecInput($keytag, $algorithm, $digesttype, $digest): array
{
    if (!is_string($keytag) || filter_var($keytag, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]) === false) {
        rejectInvalidInput('Invalid DNSSEC key tag');
    }
    if (!is_string($algorithm) || filter_var($algorithm, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 255]]) === false) {
        rejectInvalidInput('Invalid DNSSEC algorithm');
    }
    if (!is_string($digesttype) || filter_var($digesttype, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 255]]) === false) {
        rejectInvalidInput('Invalid DNSSEC digest type');
    }
    if (!is_string($digest) || strlen($digest) === 0 || strlen($digest) > 512 || !preg_match('/^[A-Fa-f0-9]+$/', $digest)) {
        rejectInvalidInput('Invalid DNSSEC digest');
    }

    return [$keytag, (int)$algorithm, $digesttype, strtoupper($digest)];
}
