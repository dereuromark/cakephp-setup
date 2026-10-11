# Getting started

Complete [Installation](./installation) first. This walkthrough adds maintenance mode without enabling administration routes or requiring a database.

## Confirm that the commands are loaded

Run this from your application's root:

```bash
bin/cake maintenance_mode status
```

A fresh installation reports that maintenance mode is disabled. This reads the status; it does not take the application offline.

## Add the middleware

In `src/Application.php`, import the middleware:

```php
use Cake\Routing\Middleware\RoutingMiddleware;
use Setup\Middleware\MaintenanceMiddleware;
```

Add this line to your existing `middleware()` method before RoutingMiddleware and authentication. Keep ErrorHandlerMiddleware earlier in the queue:

```php
$middlewareQueue->insertBefore(RoutingMiddleware::class, new MaintenanceMiddleware());
```

Create `templates/Error/maintenance.php` in your application. This template is required; the plugin does not ship a fallback page:

```html
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Maintenance</title></head>
<body><h1>Temporarily unavailable</h1><p>Please try again later.</p></body>
</html>
```

The middleware renders it with HTTP 503 and no layout by default.

## Try it locally

Activate maintenance in a disposable local application:

```bash
bin/cake maintenance_mode activate
```

Requests now receive HTTP 503 without reaching the application's downstream handler. Finish by restoring normal service:

```bash
bin/cake maintenance_mode deactivate
```

To keep access for your own IP, whitelist it before activation. Use the IP seen by your application, especially behind a reverse proxy:

```bash
bin/cake maintenance_mode whitelist 127.0.0.1
bin/cake maintenance_mode whitelist ::1
```

The whitelist applies to matching clients only. To test the maintenance page from that same client, remove the entry first:

```bash
bin/cake maintenance_mode whitelist 127.0.0.1 --remove
bin/cake maintenance_mode whitelist ::1 --remove
```

The status and whitelist files live under `tmp/`. CLI and web processes must see the same directory and have suitable permissions. With several hosts, deploy the state consistently or place it on a shared filesystem; local files do not coordinate the fleet.

## Choose another feature

[Healthchecks](/healthcheck/) can inspect your environment through CLI or web endpoints. [Console tools](/console/) include database inspection and destructive reset commands; read their scope before using them. The [web backend](/controller/) requires explicit route enablement and your application's access control.

Debugging helpers such as `prd()` and `vd()` load with the plugin bootstrap and print only while debug is enabled. The plugin does not make administration endpoints safe to expose publicly.
