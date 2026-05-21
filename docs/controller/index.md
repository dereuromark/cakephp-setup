# Web Backend

The plugin offers useful tooling through the `/admin/setup` route.

::: danger Protect these routes
Make sure this is properly ACL-protected — for example with
[TinyAuth](https://github.com/dereuromark/cakephp-tinyauth) and a few lines of
config — if you enable the routing here. These actions should never be exposed to
the public or to non-admins.
:::

## Setup

To use the backend, load the plugin with `['routes' => true]` or provide the
routes manually. Also make sure the optional Tools dependency is available in this
case.

```php
$this->addPlugin('Setup', ['routes' => true]);
```

## Useful tools

### Configuration dashboard

A dashboard with useful information about the server and the application.

See `/admin/setup/configuration`.

### PHPInfo

Lists the `phpinfo()` page as you know it.

See `/admin/setup/backend/phpinfo`.

### Cache

Details about the current cache configuration.

See `/admin/setup/backend/cache`.

### Session

Details about the current session configuration.

See `/admin/setup/backend/session`.

### Cookies

Details about the current cookies.

See `/admin/setup/backend/cookies`.

### Database

An overview of the current database tables and their size.

See `/admin/setup/backend/database`.

You can exclude tables from this overview by configuring `blacklistedTables`:

```php
'Setup' => [
    'blacklistedTables' => [
        'phinxlog',
        'sessions',
    ],
],
```

### Foreign keys

Many applications forgot to add proper constraints and handling around foreign
keys:

- **NOT NULL foreign keys**: The parent removes the child if it is deleted.
- **NULL**: The foreign key (`parent_table_id`) is set to `NULL` when the parent is
  deleted.

The first can be done either on the database level as a constraint or on the
CakePHP application level (using the `dependent` config). The backend here focuses
on the second part.

#### FK nullable

See `/admin/setup/database/foreign-keys` for an overview of all possible foreign
keys, a list of possible issues, and the checks to run.

Make sure you apply the "nullable" foreign-key part to all existing rows in your
database. The script contains a check to make sure your database is clean here
before you apply those changes — otherwise the migration will fail to apply them.

This is especially important when you want to find all children that no longer
have a `belongsTo` relation, using `fk IS NULL`. You can only trust the results
here if you have the sanity constraints in place for cleanup of those fields on
delete.

A proposed migration could look like this:

```php
    $this->table('repositories')
        ->addForeignKey('module_id', 'modules', ['id'], ['delete' => 'SET_NULL'])
        ->update();
```

The `module_id` is `DEFAULT NULL`, so deleting the module now auto-sets this to
`NULL` rather than keeping the old id (which can no longer be joined in).

## See also

- [Console Commands](/console/) — the same database integrity tooling from the CLI.
- [Uptime](/maintenance/uptime) — the uptime route also lives under `/admin/setup`.
