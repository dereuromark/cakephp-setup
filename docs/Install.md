# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Put the files in `ROOT/plugins/Setup`, using Packagist/Composer:
```
"require": {
	"dereuromark/cakephp-setup": "dev-cake3"
}
```
and

	composer update

Note that you can also use `require-dev` if you dont' need it for production environments and only use the dev tools.

If you want, however, to use certain shells like "User" in the productive enviroment, as well, please
use `require` then.
Maintenance Mode and additional SetupComponent functionality would also not be available, otherwise.

Details @ https://packagist.org/packages/dereuromark/cakephp-setup

This will load the plugin (within your boostrap file):
```php
Plugin::load('Setup');
```
or
```php
Plugin::loadAll();
```
