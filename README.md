# CakePHP Setup Plugin

This plugin provides useful tools for managing a cakephp app.


## Installation

* Clone/Copy the files in this directory into `app/Plugin/Setup`
* Don't forget to include the plugin in your bootstrap's `CakePlugin::load()` statement or use `CakePlugin::loadAll()`

Tip: You can also use packagist now @ https://packagist.org/packages/dereuromark/setup-cakephp


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


## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.

### Status
[![Build Status](https://api.travis-ci.org/dereuromark/setup.png)](https://travis-ci.org/dereuromark/setup)

### Branching strategy
The master branch is the currently active and maintained one and works with the current 2.x stable version.
Older versions might be found in their respective branches (1.3, 2.0, 2.3, ...).
Please provide PRs mainly against master branch then.


## Coming Up

* One Click Baking
* Complete Configuration and Maintenance Backend
* Coding help / guidelines
* Cleanup and Correction shells
* Testing stuff
* Backup stuff