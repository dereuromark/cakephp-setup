# CakePHP Setup Plugin

[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-setup.png?branch=cake3)](https://travis-ci.org/dereuromark/cakephp-setup)
[![License](https://poser.pugx.org/dereuromark/cakephp-setup/license.png)](https://packagist.org/packages/dereuromark/cakephp-setup)

This CakePHP 3.0 plugin provides useful tools for managing a cakephp app.

## Version notice

This cake3 branch only works for **CakePHP3.x** - please use the master branch for CakePHP 2.x!

## Installation

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
Plugin::load('Tools');
```
or
```php
Plugin::loadAll();
```

## Documentation

Currently this plugin contains only the parts I managed to migrate yet:

*	Some useful debugging shells

Possible dependencies: Tools Plugin

## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.

## Coming Up

* One Click Baking
* Complete Configuration and Maintenance Backend
* Coding help / guidelines
* Cleanup and Correction shells
* Testing stuff
* Backup stuff