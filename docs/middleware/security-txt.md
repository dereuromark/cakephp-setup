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

Register it in your `Application::middleware()`:

```php
use Setup\Middleware\SecurityTxtMiddleware;

public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
{
    $middlewareQueue
        ->add(new SecurityTxtMiddleware([
            'fields' => [
                'Contact' => 'https://github.com/owner/repo/security/advisories/new',
                'Canonical' => 'https://example.com/.well-known/security.txt',
                'Policy' => 'https://github.com/owner/repo/security/policy',
                'Preferred-Languages' => 'en, de',
            ],
        ]))
        // ... the rest of your stack
    ;

    return $middlewareQueue;
}
```

It is then served at `https://example.com/.well-known/security.txt` (and, by
default, at `/security.txt`).

::: warning Contact is required
RFC 9116 requires a `Contact` field. If you do not configure one, the middleware
passes through and serves nothing rather than emit an invalid file.
:::

## Options

| Option | Default | Description |
|--------|---------|-------------|
| `path` | `/.well-known/security.txt` | Canonical path served. |
| `serveRootFallback` | `true` | Also answer the legacy `/security.txt` path. |
| `expiresInterval` | `'+1 year'` | `strtotime`-relative interval used to compute the always-future `Expires`. |
| `cacheMaxAge` | `DAY` | `Cache-Control: max-age` in seconds. Set to `0` to omit the header. |
| `fields` | `[]` | Field map (see below). |

### `fields`

An associative array of RFC 9116 field names to values. A value may be a string
(one line) or an array of strings (repeated lines, e.g. multiple `Contact`):

```php
'fields' => [
    'Contact' => [
        'https://github.com/owner/repo/security/advisories/new',
        'mailto:security@example.com',
    ],
    'Canonical' => 'https://example.com/.well-known/security.txt',
    'Preferred-Languages' => 'en, de',
],
```

::: info Expires is managed for you
`Expires` is always computed from `expiresInterval`. Any `Expires` you put in
`fields` is ignored. Output order is: all `Contact` lines first, then the other
fields in the order you declare them, then `Expires` last.
:::

## Example output

```text
Contact: https://github.com/owner/repo/security/advisories/new
Canonical: https://example.com/.well-known/security.txt
Policy: https://github.com/owner/repo/security/policy
Preferred-Languages: en, de
Expires: 2027-05-23T00:00:00.000Z
```

> [!TIP]
> Pair the `Policy` field with a `SECURITY.md` in your repository (root, `.github/`,
> or `docs/`). GitHub renders it at `/security/policy` and links it from the repo's
> Security tab.
