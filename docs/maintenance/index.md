# Maintenance Mode

Maintenance Mode lets you take your application offline for soft maintenance work
and bring it back online, with dynamic IP whitelisting so you can keep working
while everyone else sees a maintenance page.

You can run shells from the application root as `bin/cake [shell] [command]` (or
`.\bin\cake [shell] [command]` on Windows).

::: warning Soft maintenance only
This tool should not be used when the database connection or the application goes
down completely — for example during major upgrades. In that case it would fail
hard. Use it only for soft maintenance work.
:::

## MaintenanceMode shell

This is the preferred way of enabling and disabling maintenance mode for your
website.

Commands:

- `status`
- `activate`
- `deactivate`
- `whitelist [{ip}] [-r]`
- `reset`

### Example usage

```bash
cake Setup.MaintenanceMode activate
# Optionally whitelist your IP:
cake Setup.MaintenanceMode whitelist YOURIP
# Optionally whitelist your IP and activate debug mode for when you access the application:
cake Setup.MaintenanceMode whitelist YOURIP --debug
# ... do your work ...
cake Setup.MaintenanceMode deactivate
```

::: tip Deploy scripts
Your deploy script can wrap these commands — activate at the beginning, deactivate
at the end.
:::

## MaintenanceMiddleware

The middleware is the preferred way of triggering the maintenance-mode display: it
can short-circuit dispatching much more cleanly.

In your `src/Application.php`:

```php
use Setup\Middleware\MaintenanceMiddleware;

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set up.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware($middlewareQueue) {
        $middlewareQueue
            ->add(MaintenanceMiddleware::class)
            // ...
    }
```

### Customizing

Add a template file at `templates/Error/maintenance.php`.

The following config options control how the maintenance page is rendered:

| Option | Default |
|--------|---------|
| `className` | `View::class` |
| `templatePath` | `'Error'` |
| `statusCode` | `503` |
| `templateLayout` | `false` |
| `templateFileName` | `'maintenance'` |
| `templateExtension` | `'.php'` |
| `contentType` | `'text/html'` |
| `pathWhitelist` | `[]` |

### Path whitelist

You can exclude certain paths from maintenance mode using the `pathWhitelist`
option. This is useful for healthcheck endpoints that need to remain accessible
during maintenance, so load balancers do not cycle out healthy servers:

```php
$middlewareQueue->add(new MaintenanceMiddleware([
    'pathWhitelist' => ['/setup/healthcheck', '/health'],
]));
```

Paths are matched exactly or as prefixes, so `/health` also matches
`/health/detailed`.

## Maintenance component

This component adds functionality on top:

- A flash message tells you when you are currently whitelisted while maintenance
  mode is active (so you understand why you still see the live site).

## Setup component integration

The [Setup component](/component/) adds some extra conveniences.

You can switch maintenance mode via URL when you quickly need to jump back into it:

```text
# append to the URL
?maintenance=1
# optionally with a timeout
?maintenance=1&duration={time}
```

The duration is in minutes; omit it for an indefinite duration. This automatically
whitelists the current IP, since you could not deactivate it again otherwise.

::: danger Production safety
In production mode you additionally need a password: `?pwd={YOURPWD}`.

For security reasons, change the password once it has been used. Also disable URL
access entirely by removing the configured password when it is not in use, to
prevent brute-force guessing.
:::

## Extra sugar

You can alias the maintenance shell for less typing in your `bootstrap_cli.php`:

```php
use Cake\Console\ShellDispatcher;

// Custom shell aliases
ShellDispatcher::alias('m', 'Setup.MaintenanceMode'); // Or any other alias
```

## See also

- [Uptime](/maintenance/uptime) — the uptime route.
- [Healthcheck](/healthcheck/) — keep healthcheck endpoints whitelisted during maintenance.
