# CakePHP Setup Plugin Documentation

## Version notice

This branch only works for **CakePHP3.x**

## Installation
* [Installation](Install.md)

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
	composer install // or: php composer.phar install
	composer test-setup
	composer test

To test a specific file:

	php phpunit.phar /path/to/MyClass.php

To test MySQL specific tests, run this before:
```
export db_dsn="mysql://root:secret@127.0.0.1/cake_test"
```

## Tips

Import Huge SQL file:

	...\bin\mysql -u root dbname < dumpfilename.sql

Same other direction:

	...\bin\mysqldump -h host -u root -p dbname > dumpfilename.sql
