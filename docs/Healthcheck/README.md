# Healthcheck Documentation
This plugin provides a healthcheck stack that can be used to check the status
of your application and its services.

## Config/Setup
The simplest way is to use Configure `Setup.Healthcheck.checks`:
```php
'Setup' => [
    'Healthcheck' => [
        'checks' => [
            \Setup\Healthcheck\Check\Environment\PhpVersionCheck::class,
            \Setup\Healthcheck\Check\Core\CakeVersionCheck::class,
            // ...
        ],
    ],
],
```
Once defined, this will replace the defaults.
You can also use the `Setup.Healthcheck.checks` config to add your own checks.

If you need to pass configs to the checks, you can do so by using the `Setup.Healthcheck.checksConfig` config:
```php
'Setup' => [
    'Healthcheck' => [
        'checksConfig' => [
            \Setup\Healthcheck\Check\Core\CakeVersionCheck::class => [
                'overrideComparisonChar' => '^',
            ],
            // ...
        ],
    ],
],
```
You can also pass the instance of the check class instead of the class name as a string if needed.


## Usage

You can use the healthcheck stack by accessing the `/setup/healthcheck` endpoint in your application.
In debug mode you can see the issues in detail. In production mode, only the status is shown.

For CLI you can run the command:
```bash
bin/cake healthcheck
```

You can also write a queue task to run the healthcheck periodically and log the results or
on errors directly alert the admin(s).
Using [QueueScheduler plugin](https://github.com/dereuromark/cakephp-queue-scheduler) you can directly
add a scheduled task for it in the backend, e.g. every hour.

