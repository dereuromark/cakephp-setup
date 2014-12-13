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

Details @ https://packagist.org/packages/dereuromark/cakephp-setup

This will load the plugin (within your boostrap file):
```php
Plugin::load('Setup');
```
or
```php
Plugin::loadAll(...);
```
