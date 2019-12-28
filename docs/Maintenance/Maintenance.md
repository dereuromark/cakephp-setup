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
// Optionally whitelist your IP and activate the debug mode for when you access the application:
cake Setup.MaintenanceMode whitelist YOURIP --debug
// ... Do your work ...
cake Setup.MaintenanceMode deactivate
```

Tip: Your deploy script (e.g. sh script) can contain those commands. One at the beginning, the other at the end.

## MaintenanceMiddleware
This should then be the preferred way of triggering the maintenance mode display, as it can way cleaner
short-circuit the dispatching.

In your `src/Application.php`:
```php
use Setup\Middleware\MaintenanceMiddleware;

    /**
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware($middlewareQueue) {
        $middlewareQueue
            ->add(MaintenanceMiddleware::class)
            ...
    }
```

### Customizing
Make sure you have a template file in `'templates' . DS . 'Error' . DS` named `maintenance.php`.

Configs:
- 'className' => View::class,
- 'templatePath' => 'Error',
- 'statusCode' => 503,
- 'templateLayout' => false,
- 'templateFileName' => 'maintenance',
- 'templateExtension' => '.php',
- 'contentType' => 'text/html'

Those can be used to adjust the content of the maintenance mode page.


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
