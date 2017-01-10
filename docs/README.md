# CakePHP Setup Plugin Documentation

## Version notice

This cake3 branch only works for **CakePHP3.x** - please use the master branch for CakePHP 2.x!
**It is still dev** (not even alpha), please be careful with using it.

## Installation
* [Installation](Install.md)

## Upgrade Guide
* [Upgrade guide from 2.x to 3.x](Upgrade.md)

## Detailed Documentation - Quicklinks
* [Maintenance Mode](Maintenance/Maintenance.md)
* [Setup Component](Component/Setup.md)
* [Useful Setup Shells](Console/Shells.md)

## Bake Templates Deluxe
The highly advanced bake templates of 2.x have been further enhanced and are part of this plugin.
The defaults go well together with the Tools plugin, Boostrap3+ and some other useful defaults.
You can also just steal ideas, of course ;)
* [Setup plugin Bake templates](Console/Bake.md)

## Useful debugging help
The following are convenience wrappers to debug safely. They will only show output with debug true.

* dd($data) = debug() + die() // This is now also in CakePHP 3.3+ directly :-)
* prd($data) = pr() + die()
* vd() = var_dump()
* vdd($data) = var_dump() + die()

They are available when you include the plugin's bootstrap at Plugin::load().

## Testing
You can test using a local installation of phpunit or the phar version of it:

	cd plugins/Setup
	composer update // or: php composer.phar update
	phpunit // or: php phpunit.phar

To test a specific file:

	phpunit /path/to/class.php

