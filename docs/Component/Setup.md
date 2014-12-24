# Setup Component

Attach this to your AppController to power up debugging:
- Quick-Switch: layout, maintenance, debug, clearcache (password protected in productive mode)
- Notify Admin via Email about self-inflicted 404s or loops (configurable)

TODO:
- Catch redirect loops with meaningful exception (will also be logged then)
- Automatically create stats about memory, exec time, queries and alike

Note that debug, clearcache, maintenance etc for productive mode, since they require a password,
are emergency commands only (in case you cannot power up ssh shell access that quickly).
Change your password immediately afterwards for security reasons as pwds should not be passed
plain via url.

Tip: Use the CLI and the Setup plugin shells for normal execution.

## How to setup
```php
// In your (App) controller
public function initialize() {
	$this->loadComponent('Setup.Setup');
}
```
