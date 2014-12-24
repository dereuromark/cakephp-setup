# Maintenance Mode

You can run shells from the ROOT dir as `/bin/cake [shell] [command]` (or `.\bin\cake [shell] [command]` for Windows).

Warning: This tool should not be used if the DB connection or your application goes down completely due to upgrades.
There it would fail hard. It should only be used for soft maintenance work.


## Maintenance Shell
This should be the preferred way of enabling and disabling the maintenance mode for your website.

Commands
- status
- activate
- deactivate
- whitelist [{ip}] [-r]
- reset


## Maintenance Dispatching Filter
..coming up - this should then be the preferred way of triggering the maintenance mode display, as it can way cleaner
abort the dispatching.

For now just use that in bootstrap:
```php
if (php_sapi_name() !== 'cli') {
    $Maintenance = new Setup\Maintenance\Maintenance();
    $Maintenance->checkMaintenance();
}


## Maintenance Component
This component adds functionality on top:
- A flash message shows you if you are currently whitelisted in case maintenance mode is active (and you just
don't see it due to the whitelisting).


## Customizing
If you want to customize the output (defaults to the translated string `__d('setup', 'Maintenance work')`) you can
put a template in `APP . 'Template' . DS . 'Error' . DS` named `maintenance.ctp`. It needs to be pure HTML (no PHP or CakePHP
functionality).


## Setup Component
The setup component adds some additional goddies on top:

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
