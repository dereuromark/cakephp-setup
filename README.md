# CakePHP Setup Plugin
[![Build Status](https://api.travis-ci.com/dereuromark/cakephp-setup.svg?branch=cake2)](https://travis-ci.org/dereuromark/cakephp-setup)
[![Coverage Status](https://coveralls.io/repos/dereuromark/cakephp-setup/badge.svg?branch=2.x)](https://coveralls.io/r/dereuromark/cakephp-setup)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-setup/license.svg)](https://packagist.org/packages/dereuromark/cakephp-setup)
[![Total Downloads](https://poser.pugx.org/dereuromark/setup-cakephp/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-setup)

This plugin provides useful tools for managing a CakePHP app.

NOTE: With 4.x development already being started, **this 2.x branch is now in maintenance mode**. No active development is done anymore on it, mainly only necessary bugfixes.

## Installation

* Clone/Copy the files in this directory into `APP/Plugin/Setup`
* Don't forget to include the plugin in your bootstrap's `CakePlugin::load()` statement or use `CakePlugin::loadAll()`

Tip: You can also use Packagist now @ https://packagist.org/packages/dereuromark/cakephp-setup

The recommendation is to also include the bootstrap file to leverage the debug output functions:
```php
Plugin::load('Setup', ['bootstrap' => true]);
```

## Documentation

Currently this plugin contains only the parts I managed to open source yet:

*	My custom bake templates

	http://www.dereuromark.de/2010/06/22/cake-bake-custom-templates/ (Part 1)
	http://www.dereuromark.de/2012/04/24/cake-bake-custom-templates-deluxe/ (Part 2)

*	BaseConfig database configuration class

	http://www.dereuromark.de/2012/02/25/dynamic-database-switching/

* BaseEmailConfig email configuration class (works together with Tools.EmailLib)

	http://www.dereuromark.de/2012/03/30/introducing-the-emaillib/

Possible dependencies: Tools Plugin (for bake templates for instance)

### SetupComponent

Just enable it via

    public $components = array('Setup.Setup');

Features:

* Auto create missing tmp folders etc in debug mode (already in core since 2.4 now)
* Catch redirect loops with meaningful exception (will also be logged then)
* Quick-Switch: layout, maintenance, debug, clearcache (password protected in productive mode)
* Notify Admin via Email about self-inflicted 404s or loops (configurable)

and more.

### Maintenance mode

If you have to move, update or just fix the application, an easy way to put the site into maintenance mode
is to use the shell for it. [Example on how to use it](http://www.dereuromark.de/2013/09/29/moving-a-cakephp-app/).
Contains of:

* Shell (`cake Setup.Maintenance [command]`)
* Lib
* (Optional) Setup component to display a flash message for admins in overwrite mode

### DB Maintenance tools

* CurrentConfig shell
* DbDump shell
* DbMaintenance shell

### Debug Tabs
As an alternative even before DebugKit existed I used a very basic tab box at the bottom to debug my apps.
This is quite useful to me as it does not require a lot of clicking open content. It is all visible right away.
To enable it, all you need to add is this snippet before the closing `</body>` tag in your layout ctp:
```php
<?php
	if ($debug = Configure::read('debug')) {
		$this->loadHelper('Setup.Debug', $debug);

		// Custom tabs (optional)
		if (!empty($debugItem)) {
			$this->Debug->add(1, 'Custom Debug Dump', $debugItem);
		}

  	// Display the tabs
		echo $this->Debug->show();
	}
?>
```

Make sure you have AssetDispatcher enabled for it to include the required css/js code necessary for the tabs
to be tabbable.

## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.

### Branching strategy
The master branch is the currently active and maintained one and works with the current 2.x stable version.
Older versions might be found in their respective branches (1.3, 2.0, 2.3, ...).
Please provide PRs mainly against master branch then.

### License
Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)

## Coming Up

* One Click Baking
* Complete Configuration and Maintenance Backend
* Coding help / guidelines
* Cleanup and Correction shells
* Testing stuff
* Backup stuff
