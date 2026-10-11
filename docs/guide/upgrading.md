# Upgrading

Maintenance middleware now checks the state before running the downstream handler. Blocked requests no longer execute controller actions before their response is replaced. Place the middleware before routing and authentication, after the error handler. A blocked client cannot reach the component-based URL deactivation switch. If its IP changes or is not whitelisted, recover with `bin/cake maintenance_mode deactivate` from the CLI.

Maintenance activation now reports a successful empty status-file write correctly. IPv6 subnet whitelist entries are matched using their address bytes, and fractional or scientific-notation CIDR prefixes are rejected. Exact whitelist entries are stored without substring collisions.

Production quick switches now require a password when debug is disabled and the legacy `Config.productive` flag is unset. Explicit `Config.productive` settings retain their existing behavior. Review that configuration before using URL switches.

The public healthcheck controller preserves parent authentication redirects. When no parent redirect stops the request, it allows unauthenticated access through supported authentication components and skips the Authorization component. Enable this endpoint deliberately and configure monitoring access in the application.

Use the current `maintenance_mode` commands. Old `Setup.MaintenanceMode` shell commands and `ShellDispatcher` aliases are not CakePHP 5 APIs. No maintenance timeout or CLI whitelist debug switch is implemented.

CakePHP 6 compatibility is not declared. Update current CakePHP 5 dependencies, run application checks, and resolve deprecations before attempting a major upgrade. Composer remains the source of supported version constraints.

`db reset` now preserves Migrations 5's unified `cake_migrations` table as well as legacy tracking tables. It quotes table names and rejects execution on unsupported drivers; the current reset SQL is MySQL-specific.
