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
