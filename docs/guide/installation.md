# Installation

## Requirements

This branch requires **CakePHP 5.1+** and **PHP 8.2+**.

A common optional dependency is the
[Tools](https://github.com/dereuromark/cakephp-tools) plugin, which several
features integrate with.

## Install via Composer

Installing the plugin is the same as for any other CakePHP plugin. Require it with
Composer from your application's root directory:

```bash
composer require dereuromark/cakephp-setup
```

::: tip Production vs. development
You can install the plugin under `require-dev` if you only need the development
tools and do not need it in production environments.

If you want to use certain features in production — such as user-management
commands, Maintenance Mode, or the additional Setup component functionality —
install it under `require` instead. Those features are not available otherwise.
:::

See the package page for details:
[packagist.org/packages/dereuromark/cakephp-setup](https://packagist.org/packages/dereuromark/cakephp-setup).

## Load the plugin

Then load the plugin:

```bash
bin/cake plugin load Setup
```

If you intend to use the [web backend](/controller/), load it with routing
enabled:

```php
$this->addPlugin('Setup', ['routes' => true]);
```

## Next steps

- [Maintenance Mode](/maintenance/) — enable and disable maintenance safely.
- [Healthcheck](/healthcheck/) — set up and run health checks.
- [Console Commands](/console/) — explore the operational toolbox.
