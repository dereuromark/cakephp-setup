# security.txt Middleware

`Setup\Middleware\SecurityTxtMiddleware` serves an [RFC 9116](https://www.rfc-editor.org/rfc/rfc9116)
`security.txt` file that tells security researchers how to report vulnerabilities.

The required `Expires` field is **computed on every request**, so it never goes
stale — no static file to hand-edit and no cron job to keep it fresh.

## How it works

The middleware short-circuits the request: when the path matches it returns the
`text/plain` response directly, without touching routing, authentication, or any
other downstream middleware. For every other path it simply passes through.

Because it answers before routing/auth, add it **early** in the queue.

## Setup

Describe the document with the `SecurityTxt` value object and register it in your
`Application::middleware()`:

```php
use Setup\Middleware\SecurityTxt;
use Setup\Middleware\SecurityTxtMiddleware;

public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
{
    $middlewareQueue
        ->add(new SecurityTxtMiddleware(new SecurityTxt(
            contact: 'https://github.com/owner/repo/security/advisories/new',
            canonical: 'https://example.com/.well-known/security.txt',
            policy: 'https://github.com/owner/repo/security/policy',
            preferredLanguages: 'en, de',
        )))
        // ... the rest of your stack
    ;

    return $middlewareQueue;
}
```

It is then served at `https://example.com/.well-known/security.txt` (and, by
default, at `/security.txt`).

::: warning Contact is required
RFC 9116 requires a `Contact`. It is the only non-optional parameter of
`SecurityTxt`, and constructing a document (or the middleware) without a non-empty
contact throws an `InvalidArgumentException` — so misconfiguration fails at boot
rather than serving an invalid file.
:::

## The `SecurityTxt` document

Each parameter maps to an RFC 9116 field. A value may be a single string or a list
of strings (repeated lines, e.g. multiple `Contact`). `null` fields are omitted.

| Parameter | Field | Notes |
|-----------|-------|-------|
| `contact` *(required)* | `Contact` | `https:`, `mailto:`, or `tel:` URI(s). |
| `canonical` | `Canonical` | Canonical URI(s) of this file. |
| `encryption` | `Encryption` | Encryption key URI(s). |
| `acknowledgments` | `Acknowledgments` | Hall-of-fame URI(s). |
| `preferredLanguages` | `Preferred-Languages` | RFC 5646 tags, e.g. `en, de`. |
| `policy` | `Policy` | Security policy URI(s). |
| `hiring` | `Hiring` | Security-related job URI(s). |
| `csaf` | `CSAF` | `provider-metadata.json` URI(s). |
| `expiresInterval` | `Expires` | `strtotime`-relative interval (default `+1 year`). |

::: info Expires is managed for you
`Expires` is always computed from `expiresInterval`, so it stays in the future.
Output order is: all `Contact` lines first, then the other fields, then `Expires`
last.
:::

## Behavior options

Transport/behavior knobs are passed as a second array argument (they are distinct
from the document content):

```php
new SecurityTxtMiddleware($document, [
    'cacheMaxAge' => WEEK,
    'serveRootFallback' => false,
]);
```

| Option | Default | Description |
|--------|---------|-------------|
| `path` | `/.well-known/security.txt` | Canonical path served (base-path aware). |
| `serveRootFallback` | `true` | Also answer the legacy `/security.txt` path. |
| `cacheMaxAge` | `DAY` | `Cache-Control: max-age` in seconds. Set to `0` to omit the header. |

## Array escape hatch

A raw config array is still accepted — useful for fields the value object does not
cover, or fully dynamic config. The same `Contact` requirement applies:

```php
new SecurityTxtMiddleware([
    'cacheMaxAge' => 0,
    'fields' => [
        'Contact' => ['https://example.com/report', 'mailto:security@example.com'],
        'Canonical' => 'https://example.com/.well-known/security.txt',
    ],
]);
```

## Example output

```text
Contact: https://github.com/owner/repo/security/advisories/new
Canonical: https://example.com/.well-known/security.txt
Policy: https://github.com/owner/repo/security/policy
Preferred-Languages: en, de
Expires: 2027-05-23T00:00:00.000Z
```

> [!TIP]
> Pair the `policy` field with a `SECURITY.md` in your repository (root, `.github/`,
> or `docs/`). GitHub renders it at `/security/policy` and links it from the repo's
> Security tab.
