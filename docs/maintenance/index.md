# Maintenance mode

The middleware returns a maintenance response before the downstream application handler runs. It supports an IP whitelist and optional path exceptions. It does not execute scheduled database upgrades or supervise your deployment.

## CLI commands

Run commands from the application's root:

```bash
bin/cake maintenance_mode status
bin/cake maintenance_mode whitelist 192.0.2.10
bin/cake maintenance_mode activate
bin/cake maintenance_mode deactivate
```

The whitelist accepts exact IPv4/IPv6 addresses and CIDR networks, such as `192.0.2.0/24` or `2001:db8::/32`. Prefix lengths must be whole decimal numbers. Remove one entry or clear the list:

```bash
bin/cake maintenance_mode whitelist 192.0.2.10 --remove
bin/cake maintenance_mode whitelist --remove
```

There is no `maintenance_mode reset` command. The legacy `--debug` option is accepted but has no effect; use your application's debug configuration deliberately instead.

## Middleware

Follow [Getting started](/guide/#add-the-middleware) to add `MaintenanceMiddleware` before routing and authentication. Keep an error handler earlier in the queue. Whitelisted paths or IPs continue through the normal application handler; blocked requests return HTTP 503 by default.

The state is stored in `TMP . 'maintenance.txt'`, with the whitelist in `TMP . 'maintenance_whitelist.txt'`. Share or distribute these files deliberately when web and CLI run on different hosts. A maintenance page cannot protect against failures before the middleware queue is built, or when its template rendering fails.

## Custom page and options

Create `templates/Error/maintenance.php` before activating maintenance; there is no bundled fallback template. The [beginner guide](/guide/#add-the-middleware) includes a complete page. Constructor options:

| Option | Default | Purpose |
|---|---|---|
| `className` | `Cake\View\View` | View class |
| `templatePath` | `Error` | Template directory |
| `templateFileName` | `maintenance` | Template name |
| `templateLayout` | `false` | Disable layout, or select a layout name |
| `statusCode` | `503` | Response status |
| `contentType` | `text/html` | Response content type |
| `pathWhitelist` | `[]` | Paths permitted during maintenance |

The legacy `templateExtension` option is present in the defaults but is not used by the renderer. Templates use CakePHP's normal `.php` extension.

For example, keep the healthcheck endpoint reachable:

```php
$middlewareQueue->add(new \Setup\Middleware\MaintenanceMiddleware([
    'pathWhitelist' => ['/setup/healthcheck'],
]));
```

A whitelist path permits that exact path and descendants, not similarly named siblings. The healthcheck still needs its routes enabled and its own access configuration. Behind a proxy, configure CakePHP's trusted proxy handling so `clientIp()` reflects the correct client.

## URL switches

Loading [SetupComponent](/component/) enables a `?maintenance=1` switch, with `?maintenance=0` to deactivate. It whitelists the current client IP. No automatic timeout is implemented; a `duration` query parameter has no effect.

These are legacy emergency controls. Prefer the CLI for normal operations. Production mode requires the configured password; see [SetupComponent](/component/) for its configuration and limits.

## Related tools

[Uptime](/maintenance/uptime) reports a basic response; [Healthchecks](/healthcheck/) inspect more services. Whitelist only the monitoring endpoints you need.
