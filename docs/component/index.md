# Setup Component

Attach this component to your `AppController` to power up debugging:

- Quick-switch: layout, maintenance, debug, clear cache (password-protected in
  production mode).
- Notify the admin by email about self-inflicted 404s or loops (configurable).

::: warning Production-mode commands are emergency-only
The debug, clear-cache, and maintenance switches for production mode require a
password and are emergency commands only: for when you cannot quickly get SSH
shell access.

Change your password immediately afterward for security reasons, since passwords
should not be passed in plain text via the URL.
:::

::: tip Prefer the CLI
For normal execution, prefer the CLI and the Setup plugin's
[console commands](/console/).
:::

## How to set up

```php
// In your (App) controller
public function initialize(): void {
    parent::initialize();

    $this->loadComponent('Setup.Setup');
}
```

## Configuration

Quick switches allow access in debug mode. With debug disabled, they require a matching `Config.pwd` by default. The legacy `Config.productive` flag overrides that detection: set it to `true` in production and do not disable it there.

```php
// In config/bootstrap.php, using your application's secret configuration:
\Cake\Core\Configure::write('Config.productive', true);
\Cake\Core\Configure::write('Config.pwd', env('SETUP_EMERGENCY_PASSWORD'));
```

Without a non-empty password, production switches deny access. URL passwords can appear in logs and browser history; use the CLI whenever possible. `?maintenance=1&duration=...` has no timeout behavior. The component also requires the Flash component to be loaded.


### Session key

The component uses the session to identify users for 404 notifications. By default
it reads from `Auth.User`.

If you use the CakeDC/Users plugin or a different session structure, configure the
session key:

```php
// In your app.php or app_local.php
'Setup' => [
    'sessionKey' => 'Auth', // For the CakeDC/Users plugin
],
```

The component will then read the user ID from `Auth.id` instead of `Auth.User.id`.

## See also

- [Maintenance Mode](/maintenance/): the component adds URL-based maintenance switching.
- [Console Commands](/console/): the preferred way to run setup tasks.
