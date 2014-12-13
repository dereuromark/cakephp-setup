# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Put the files in `ROOT/plugins/Setup`, using Packagist/Composer:
```
"require-dev": {
	"dereuromark/cakephp-setup": "dev-cake3"
}
```
and

	composer update

Note that `require-dev` usually totally enough for using this plugin, as it only provides helpful
dev tools. It is not needed for production environments.

Details @ https://packagist.org/packages/dereuromark/cakephp-setup

This will load the plugin (within your boostrap file):
```php
Plugin::load('Setup');
```
or
```php
Plugin::loadAll(...);
```
