# Overview

The **Setup** plugin provides a collection of useful development and maintenance
tools for managing a CakePHP application. It bundles maintenance-mode handling, a
pluggable healthcheck stack, debugging helpers, console utilities, a small admin
web backend, and enhanced bake templates.

::: info CakePHP version
This documentation is for the branch that supports **CakePHP 5.1+**. See the
[version map](https://github.com/dereuromark/cakephp-setup/wiki#cakephp-version-map)
for older releases.
:::

## What is this plugin for?

This plugin aims to make developing and operating CakePHP applications easier. It
extends and leverages the [Tools](https://github.com/dereuromark/cakephp-tools)
plugin and brings together the pieces that are useful day to day:

- **Maintenance Mode** — dynamic activation and deactivation, including dynamic IP
  whitelisting and a middleware that short-circuits the request early.
- **Healthcheck** — a pluggable set of environment, application, and database
  checks, runnable from the web or the CLI.
- **Console utilities** — config inspection, database management, data-integrity
  checks, backups, user management, and more.
- **Web backend** — a small admin area with a configuration dashboard plus cache,
  session, cookie, database, and foreign-key tooling.
- **DebugKit panel** — a localization (L10n) panel for quick format inspection.
- **Bake templates** — refined scaffolding output for models, controllers, and
  views.

## The pieces

| Area | What it gives you |
|------|-------------------|
| [Maintenance Mode](/maintenance/) | Turn the app on and off for soft maintenance work, with whitelisting and middleware. |
| [Healthcheck](/healthcheck/) | A status overview of your environment, application, and database, web and CLI. |
| [Console Commands](/console/) | Day-to-day operations from `bin/cake` — config, DB, backups, users. |
| [Bake Templates](/console/bake) | Enhanced scaffolding via the `Setup` bake theme. |
| [Setup Component](/component/) | Quick-switch debugging helpers attached to your controller. |
| [Web Backend](/controller/) | An admin backend behind your own ACL. |
| [L10n Panel](/panel/) | A DebugKit panel showing localization status. |

## Debugging helpers

The plugin ships a few convenience wrappers that make debugging safer. They only
produce output when `debug` is enabled:

| Helper | Equivalent |
|--------|------------|
| `dd($data)` | `debug()` + `die()` |
| `prd($data)` | `pr()` + `die()` |
| `vd($data)` | `var_dump()` |
| `vdd($data)` | `var_dump()` + `die()` |

These become available once the plugin's bootstrap is included — for example via
`bin/cake plugin load Setup`.

## Next steps

- [Installation](/guide/installation) — install the plugin and load it.
- [Maintenance Mode](/maintenance/) — enable and disable maintenance safely.
- [Healthcheck](/healthcheck/) — monitor your application's health.
- [Console Commands](/console/) — the operational toolbox.
