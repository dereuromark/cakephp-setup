# Installation

This development branch requires PHP 8.5 and CakePHP 6. The application must allow development stability for unreleased framework and companion packages. Keep stable packages preferred.

Install from your application's root:

```bash
composer config minimum-stability dev
composer config prefer-stable true
composer require dereuromark/cakephp-setup:dev-cake6
```

Composer checks the supported PHP and CakePHP versions from the package constraints. Maintenance middleware and healthchecks in production require a production dependency. For development-only use, install with `--dev` and load using `bin/cake plugin load Setup --no-routes --only-debug`; do not register a development-only package unconditionally.

## Load the plugin

For CLI tools and middleware, load Setup with its web routes disabled:

```bash
bin/cake plugin load Setup --no-routes
```

This keeps its administration endpoints out of your application's route table. The plugin does not automatically add middleware; [Getting started](./index) shows where to add it.

If Setup is already registered, edit its existing entry in `config/plugins.php` instead of adding it twice. Enable routes only when you need the [web backend](/controller/) or [web healthcheck](/healthcheck/) and have configured access.

## Optional dependencies

| Feature | Additional package |
|---|---|
| Web administration, Tools helpers, default enhanced bake base classes | `dereuromark/cakephp-tools` |
| Template customization in the administration backend | `dereuromark/cakephp-templating` |
| Generate application classes or healthchecks | `cakephp/bake` |
| Run generated database migrations | `cakephp/migrations` |
| L10n panel | `cakephp/debug_kit` |
| Healthcheck Queue task | Community `dereuromark/cakephp-queue` |

Install the packages for the features you use, then load their plugins. For the administration tools:

```bash
composer require dereuromark/cakephp-tools:dev-cake6
bin/cake plugin load Tools
```

The core maintenance middleware needs no Tools dependency. The development dependencies in this repository are not automatically installed in applications consuming the package.

Follow CakePHP's [plugin loading documentation](https://book.cakephp.org/5/en/plugins.html#loading-a-plugin) for registration through `Application::bootstrap()`.

The temporary `cakephp/migrations:dev-fix/6.x-cakephp-compatibility` dependency tracks the upstream CakePHP 6 compatibility work in [Migrations PR 1121](https://github.com/cakephp/migrations/pull/1121). Replace it with the compatible release constraint when that work is released.
