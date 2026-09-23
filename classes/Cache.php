<?php

// Cache.php provides a tiny file-based cache so slow, external-API-backed
// pages (pricing.php, renewinfo.php) don't re-fetch data from every
// registrar on every single page load. This is what actually prevents
// gateway timeouts under load - lowering socket timeouts stops a single
// hung request from blocking forever, but caching stops the page from
// making dozens of live API calls in the first place.

function cacheDir(): string
{
    $dir = sys_get_temp_dir() . '/dnmanager-cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

// cacheRemember returns the cached value for $key if it is younger than
// $ttlSeconds, otherwise it calls $callback(), stores the result, and
// returns it. If $callback() throws, any existing (even expired) cached
// value is returned as a fallback rather than propagating the failure -
// stale pricing data is far better than a fatal error or a timed-out page.
function cacheRemember(string $key, int $ttlSeconds, callable $callback)
{
    $file = cacheDir() . '/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) . '.json';

    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['time'], $decoded['value'])) {
            if ((time() - $decoded['time']) < $ttlSeconds) {
                return $decoded['value'];
            }
        }
    }

    try {
        $value = $callback();
    } catch (Throwable $e) {
        if (isset($decoded['value'])) {
            return $decoded['value']; // serve stale data rather than fail the page
        }
        throw $e;
    }

    @file_put_contents($file, json_encode(['time' => time(), 'value' => $value]), LOCK_EX);
    return $value;
}
