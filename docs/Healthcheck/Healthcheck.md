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

To only replace the ones you need, you can also merge with the defaults:
```php
'Setup' => [
    'Healthcheck' => [
        'checks' => [
            \Setup\Healthcheck\Check\Environment\PhpUploadLimitCheck::class => [
                'min' => 64,
            ],
            // ...
        ] + \Setup\Healthcheck\HealthcheckCollector::defaultChecks(),
    ],
],
```
If you need to pass configs to the checks, make sure the value is an array with the keys matching the param names.

You can also pass the instance of the check class instead of the class name as a string if needed:
```php
'Setup' => [
    'Healthcheck' => [
        'checks' => [
            new \Setup\Healthcheck\Check\Core\CakeVersionCheck(
                overrideComparisonChar: '^',
            ),
            // ...
        ],
    ],
],
```

You can set a priority (1...10) for each check to control the order of execution.
```php
    protected int $priority = 6;
```
The higher (towards 1), the earlier it will be executed.

If you want to set a specific scope, you can do that by either adjusting the property directly, or set the method.
The latter is needed for a dynamic scope through closure:
```php
    /**
     * @return array<string|callable>
     */
    public function scope(): array {
        return [
            // Only run in debug mode
            function () {
                return Configure::read('debug');
            },
        ];
    }
```

In case you need to adjust an existing check at runtime, you need to instantiate it first:
```php
$check = new \Setup\Healthcheck\Check\Core\CakeVersionCheck();
$check
    ->adjustPriority(6)
    ->adjustScope([...]);
```

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

