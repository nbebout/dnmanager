<?php

// Http.php centralizes outbound HTTP calls so every registrar API request has
// an explicit, short timeout. Without this, a slow/hanging registrar API call
// can block a PHP request for the full default_socket_timeout (60s) *per
// call*, and pages like pricing.php/renewinfo.php make dozens of calls in a
// single request - easily exceeding an upstream proxy/load balancer's
// gateway timeout and producing 502/504 errors.

const HTTP_DEFAULT_TIMEOUT_SECONDS = 8;

// httpGetXml fetches $url with a bounded timeout and parses the response as
// XML. Returns false on any transport or parse failure (mirrors the failure
// behavior of simplexml_load_file so existing ->truthy checks keep working),
// callers should still check for false before accessing properties.
function httpGetXml(string $url, int $timeoutSeconds = HTTP_DEFAULT_TIMEOUT_SECONDS)
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true, // still let us read/parse error response bodies
        ],
    ]);

    $previous = libxml_use_internal_errors(true);
    try {
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return false;
        }
        $xml = simplexml_load_string($body);
        return $xml === false ? false : $xml;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

// httpGetMulti fetches all of $urls concurrently, at most $concurrency at a
// time, sending $headers with each request. Returns the response bodies with
// the same keys as $urls; each is false on a transport failure. Pages that
// need dozens of API calls use this so the total time is a few round trips
// instead of the sum of every call.
function httpGetMulti(array $urls, array $headers = [], int $concurrency = 10, int $timeoutSeconds = HTTP_DEFAULT_TIMEOUT_SECONDS): array
{
    $results = array_fill_keys(array_keys($urls), false);
    $pending = $urls;
    $active = [];
    $multi = curl_multi_init();

    try {
        do {
            while (count($active) < $concurrency && !empty($pending)) {
                $key = array_key_first($pending);
                $curl = curl_init($pending[$key]);
                unset($pending[$key]);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
                curl_setopt($curl, CURLOPT_TIMEOUT, $timeoutSeconds);
                curl_multi_add_handle($multi, $curl);
                $active[(int)$curl] = [$key, $curl];
            }

            curl_multi_exec($multi, $running);
            if ($running > 0) {
                curl_multi_select($multi, 1.0);
            }

            while ($info = curl_multi_info_read($multi)) {
                [$key, $curl] = $active[(int)$info['handle']];
                if ($info['result'] === CURLE_OK) {
                    $results[$key] = curl_multi_getcontent($curl);
                }
                curl_multi_remove_handle($multi, $curl);
                curl_close($curl);
                unset($active[(int)$info['handle']]);
            }
        } while (!empty($active) || !empty($pending));
    } finally {
        foreach ($active as [$key, $curl]) {
            curl_multi_remove_handle($multi, $curl);
            curl_close($curl);
        }
        curl_multi_close($multi);
    }

    return $results;
}

// httpGetXmlMulti is like httpGetMulti but parses each response as XML. Each
// result is false on a transport or parse failure, like httpGetXml.
function httpGetXmlMulti(array $urls, int $concurrency = 10, int $timeoutSeconds = HTTP_DEFAULT_TIMEOUT_SECONDS): array
{
    $previous = libxml_use_internal_errors(true);
    try {
        return array_map(function ($body) {
            return $body === false ? false : simplexml_load_string($body);
        }, httpGetMulti($urls, [], $concurrency, $timeoutSeconds));
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

// httpGetJsonMulti is like httpGetMulti but decodes each response as JSON
// into an associative array. Each result is false on a transport or parse
// failure, like httpRequestJson.
function httpGetJsonMulti(array $urls, array $headers = [], int $concurrency = 10, int $timeoutSeconds = HTTP_DEFAULT_TIMEOUT_SECONDS): array
{
    $headers[] = 'Accept: application/json';
    return array_map(function ($body) {
        $json = $body === false ? null : json_decode($body, true);
        return is_array($json) ? $json : false;
    }, httpGetMulti($urls, $headers, $concurrency, $timeoutSeconds));
}

// httpPostXml performs a POST (optionally with $fields as the body) with a
// bounded connect+total timeout and parses the response as XML.
function httpPostXml(string $url, array $fields = [], int $timeoutSeconds = HTTP_DEFAULT_TIMEOUT_SECONDS)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeoutSeconds);
    $response = curl_exec($curl);
    $failed = ($response === false) || (bool)curl_errno($curl);
    curl_close($curl);

    if ($failed) {
        return false;
    }

    $xml = simplexml_load_string($response);
    return $xml === false ? false : $xml;
}

// httpRequestJson performs an HTTP request with a JSON body (when $body is
// not null) and a bounded connect+total timeout, and decodes the response as
// JSON into an associative array. Returns false on transport or parse
// failure. Error responses (4xx/5xx) with a JSON body are still returned so
// callers can inspect API-level error details.
function httpRequestJson(string $method, string $url, array $headers = [], $body = null, int $timeoutSeconds = HTTP_DEFAULT_TIMEOUT_SECONDS)
{
    $headers[] = 'Accept: application/json';

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        $headers[] = 'Content-Type: application/json';
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeoutSeconds);
    $response = curl_exec($curl);
    $failed = ($response === false) || (bool)curl_errno($curl);
    curl_close($curl);

    if ($failed) {
        return false;
    }

    $json = json_decode($response, true);
    return is_array($json) ? $json : false;
}
