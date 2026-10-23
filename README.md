# Domain Name Manager

Supported Registrars:

- eNom
- ResellerClub
- Namecheap (No DNSSEC management)
- Cloudflare (Domain list, lock status, and read-only DNSSEC status)

## Developing

To start the development servers, run `./test-api/start-stub-server.sh` and `./start-dev-server.sh`.
Then browse to `http://localhost:8080`.

### Using the stub registrar APIs

`test-api/` holds stub versions of the eNom, Namecheap, and Cloudflare APIs that return canned
responses, so the app can be run without real registrar accounts. The stub server serves that
directory at `http://dns-test-api` on the `dnmanager` Docker network, which the dev server also
joins.

To point the app at the stubs, copy `config.sample.php` to `config.php`, define `TESTING`, and set
each registrar's `server` to the stub server and `api_path` to its stub script. `api_path` is only
used when `TESTING` is defined, so it can't redirect a production config by accident. The
credentials can be anything non-empty; the stubs don't check them.

```php
<?php
define('TESTING', true);

$config['enom'] = [
    'server' => 'http://dns-test-api',
    'username' => 'test',
    'password' => 'test',
    'api_path' => '/enom/Stub.php',
];

$config['namecheap'] = [
    'server' => 'http://dns-test-api',
    'username' => 'test',
    'apikey' => 'test',
    'clientip' => '127.0.0.1',
    'api_path' => '/namecheap/Stub.php',
];

$config['cloudflare'] = [
    'server' => 'http://dns-test-api',
    'account-id' => 'test',
    'apitoken' => 'test',
    'api_path' => '/cloudflare/Stub.php',
];

$config['pricingTLDs'] = ['com', 'net', 'org'];
```

Each stub script picks a canned response by the API command it's called with (`command` for eNom,
`Command` for Namecheap, the request path for Cloudflare) and returns nothing for commands it
doesn't know, which the app reports as a failed request. The responses are static, so changes
such as locking a domain or editing nameservers report success but don't change what's shown.
There's no ResellerClub stub; leave its `server` empty to skip it.
