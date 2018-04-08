# Maintenance Mode

You can run shells from the ROOT dir as `/bin/cake [shell] [command]` (or `.\bin\cake [shell] [command]` for Windows).

Warning: This tool should not be used if the DB connection or your application goes down completely due to upgrades.
There it would fail hard. It should only be used for soft maintenance work.


## MaintenanceMode Shell
This should be the preferred way of enabling and disabling the maintenance mode for your website.

Commands
- status
- activate
- deactivate
- whitelist [{ip}] [-r]
- reset

### Example usage
```
cake Setup.MaintenanceMode activate
// Optionally whitelist your IP:
cake Setup.MaintenanceMode whitelist YOURIP
// ... Do your work ...
cake Setup.MaintenanceMode deactivate
```

## MaintenanceMiddleware (CakePHP 3.3+)
This should then be the preferred way of triggering the maintenance mode display, as it can way cleaner
short-circuit the dispatching.

In your `src/Application.php`:
```php
use Setup\Middleware\MaintenanceMiddleware;

	/**
	 * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to setup.
	 * @return \Cake\Http\MiddlewareQueue The updated middleware.
	 */
	public function middleware($middleware) {
		$middleware
			->add(MaintenanceMiddleware::class)
			...
	}
```

### Customizing
Make sure you have a template file in `APP . 'Template' . DS . 'Error' . DS` named `maintenance.ctp`.

Configs:
- 'className' => View::class,
- 'templatePath' => 'Error',
- 'statusCode' => 503,
- 'templateLayout' => false,
- 'templateFileName' => 'maintenance',
- 'templateExtension' => '.ctp',
- 'contentType' => 'text/html'

Those can be used to adjust the content of the maintenance mode page.

## Maintenance Dispatching Filter (deprecated)

Just add this in your bootstrap:
```php
use Setup\Routing\Filter\MaintenanceFilter;

DispatcherFactory::add(new MaintenanceFilter());
```

You might want to wrap it with
```php
if (php_sapi_name() !== 'cli') {}
```
to only add this filter for non CLI requests.

This filter has a very limited customization way. You can only adjust the string `__d('setup', 'Maintenance work')`.


## Maintenance Component
This component adds functionality on top:
- A flash message shows you if you are currently whitelisted in case maintenance mode is active (and you just
don't see it due to the whitelisting).


## Setup Component
The Setup component adds some additional goodies on top:

You can set the maintenance mode via URL, if you quickly need to jump into it again:
```
// append to the URL
?maintenance=1
// optionally with timeout
?maintenance=1&duration={time}
```
With time in minutes for infinite time. It will automatically whitelist this IP, as you could not
deactivate it again otherwise.

### Note
In productive mode you need a pwd, though, on top: `?pwd={YOURPWD}`.

For security reasons you need to change the password, once used.
Also, deactivate the URl access completely by removing the config pwd, when not in use, to
prevent brute force guesses.


## Extra Sugar
You can shell alias the MaintenanceShell for less typing in your `bootstrap_cli.php`:
```php
use Cake\Console\ShellDispatcher;

// Custom shell aliases
ShellDispatcher::alias('m', 'Setup.MaintenanceMode'); // Or any other alias
```
