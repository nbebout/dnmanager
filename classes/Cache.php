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

// How long to wait before retrying a refresh that failed, while serving the
// older cached value. Without this, a registrar that's down or hanging would
// be retried, and its timeout waited out, on every page load.
const CACHE_RETRY_SECONDS = 900;

// cacheRemember returns the cached value for $key if it is younger than
// $ttlSeconds, otherwise it calls $callback(), stores the result, and
// returns it.
//
// If $callback() throws or returns an empty result, any existing (even
// expired) cached value is returned instead - stale pricing data is far
// better than a fatal error or a timed-out page - and $warning is set to
// say so, so the page can show that the data is old and why. An empty
// result more likely means something went wrong than that a registrar has
// no prices, so it's never stored. The failure is recorded in the cache,
// and the refresh isn't retried for CACHE_RETRY_SECONDS. With nothing
// cached to fall back on, the exception is thrown (or the empty result
// returned) as usual.
function cacheRemember(string $key, int $ttlSeconds, callable $callback, ?string &$warning = null)
{
    $warning = null;
    $file = cacheDir() . '/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) . '.json';

    $cached = null;
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['time'], $decoded['value'])) {
            $cached = $decoded;
            if ((time() - $cached['time']) < $ttlSeconds) {
                return $cached['value'];
            }
            if (isset($cached['failed_at'], $cached['error']) && (time() - $cached['failed_at']) < CACHE_RETRY_SECONDS) {
                $warning = cacheStaleWarning($cached);
                return $cached['value'];
            }
        }
    }

    try {
        $value = $callback();
        $error = empty($value) ? 'no data was returned' : null;
    } catch (Throwable $e) {
        if ($cached === null) {
            throw $e;
        }
        $error = $e->getMessage();
    }

    if ($error !== null) {
        if ($cached === null) {
            return $value;
        }
        $cached['failed_at'] = time();
        $cached['error'] = $error;
        @file_put_contents($file, json_encode($cached), LOCK_EX);
        $warning = cacheStaleWarning($cached);
        return $cached['value'];
    }

    @file_put_contents($file, json_encode(['time' => time(), 'value' => $value]), LOCK_EX);
    return $value;
}

// cacheStaleWarning describes a cached value being served because its refresh failed.
function cacheStaleWarning(array $cached): string
{
    return 'showing data from ' . date('M j, g:i A T', $cached['time']) . " because the refresh failed: {$cached['error']}";
}
