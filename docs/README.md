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


## Testing
You can test using a local installation of phpunit or the phar version of it:

	cd plugins/Setup
	composer update // or: php composer.phar update
	phpunit // or: php phpunit.phar

To test a specific file:

	phpunit /path/to/class.php

