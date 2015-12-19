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

## Maintenance Dispatching Filter
This should then be the preferred way of triggering the maintenance mode display, as it can way cleaner
short-circuit the dispatching.

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

Alternativly, as low-level approach, you could still use the deprecated approach without any dispatching filter:
```php
if (php_sapi_name() !== 'cli') {
    $Maintenance = new Setup\Maintenance\Maintenance();
    $Maintenance->checkMaintenance();
}
```
But this is not recommended anymore.


## Maintenance Component
This component adds functionality on top:
- A flash message shows you if you are currently whitelisted in case maintenance mode is active (and you just
don't see it due to the whitelisting).


## Customizing
If you want to customize the output (defaults to the translated string `__d('setup', 'Maintenance work')`) you can
put a template in `APP . 'Template' . DS . 'Error' . DS` named `maintenance.ctp`. It needs to be pure HTML (no PHP or CakePHP
functionality).

There are also some Configure values in case you really want to render a complete maintenance view.
In that case you can use complete PHP/CakePHP functionality:
- template (true/false, defaults to false)
- layout (true/false, defaults to false)

It will then use the `APP . 'Template' . DS . 'Error' . DS` . 'maintenance.ctp'` template and if applicable,
the `APP . 'Template' . DS . 'Layout' . DS . 'maintenance.ctp'` layout.

## Setup Component
The Setup component adds some additional goddies on top:

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
