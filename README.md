# CakePHP Setup Plugin

This branch targets CakePHP 6 and PHP 8.5 or later. Use the `cake6` branches of companion CakePHP plugins during development.

Composer applications consuming this development branch must set `minimum-stability` to `dev` and `prefer-stable` to `true`, then require this plugin as `dev-cake6`. Companion plugins also use their `cake6` branches. The published documentation website covers stable CakePHP 5 releases; use this branch's `docs/` directory for the upgrade documentation.

[![CI](https://github.com/dereuromark/cakephp-setup/actions/workflows/ci.yml/badge.svg?branch=cake6)](https://github.com/dereuromark/cakephp-setup/actions/workflows/ci.yml?query=branch%3Acake6)
[![Coverage](https://img.shields.io/codecov/c/github/dereuromark/cakephp-setup/master.svg)](https://codecov.io/gh/dereuromark/cakephp-setup)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-setup/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-setup)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.5-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-setup/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-setup/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-setup)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

Provides useful development tools for managing a CakePHP app. See the [version map](https://github.com/dereuromark/cakephp-setup/wiki#cakephp-version-map) for other CakePHP branches.

Maintenance middleware, healthchecks, debugging helpers, administration tools, and bake templates for CakePHP applications. Most features are opt-in. The web administration routes require your application's access control.

## Documentation

Full documentation lives at **[dereuromark.github.io/cakephp-setup](https://dereuromark.github.io/cakephp-setup/)**.

A few good entry points:

* [Installation](https://dereuromark.github.io/cakephp-setup/guide/installation)
* [Getting started](https://dereuromark.github.io/cakephp-setup/guide/)
* [Maintenance Mode](https://dereuromark.github.io/cakephp-setup/maintenance/)
* [security.txt Middleware](https://dereuromark.github.io/cakephp-setup/middleware/security-txt)
* [Healthcheck](https://dereuromark.github.io/cakephp-setup/healthcheck/)
* [Console Commands](https://dereuromark.github.io/cakephp-setup/console/)

The Markdown sources live in the [docs](docs/) directory of this repository.

Possible dependencies: [Tools](https://github.com/dereuromark/cakephp-tools) Plugin

## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.
