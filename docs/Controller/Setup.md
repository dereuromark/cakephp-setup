# Setup Web Backend

Useful tooling via `/admin/setup` route.

**Important**: Make sure this is properly ACL protected (e.g. [TinyAuth](https://github.com/dereuromark/cakephp-tinyauth) and 3 lines of config) if you enable the routing here.
Those actions should never be exposed to the public or non-admins.

## Setup
For this you need to make sure the plugin is loaded with `['routes' => true]` or you provide them manually.
Also make sure that the optional Tools dependency is in this case available.

## Useful tools

### Configuration Dashboard
With useful info about server and application.

See `/admin/setup/configuration`

### PHPInfo
Lists the phpinfo() page as you know it.

See `/admin/setup/backend/phpinfo`.

### Cache
Details about the current cache config.

See `/admin/setup/backend/cache`.

### Session
Details about the current session config.

See `/admin/setup/backend/session`.

### Cookies
Details about the current cookies.

See `/admin/setup/backend/cookies`.

### Database
Overview about the current DB tables and size.

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

### Foreign Keys

Many applications for sure forgot to add proper constraints/handling around foreign keys:

- NOT NULL foreign keys: Parent removes child if deleted
- NULL: FK (parent_table_id) is set to NULL if parent gets deleted

The first can be done both on DB level as constraint or Cake app level (using `depending true` config).
The backend here now focuses on the 2nd part.

#### FK nullable
See `/admin/setup/database/foreign-keys`
for an overview of all possible foreign keys and get a list of possible issues and checks to run.

Make sure you apply the foreign key "NULL"able part to all existing rows in your DB.
The script contains a check to make sure your DB is clean here before you apply those.
The migration will otherwise fail to apply them.

This is especially important when you want to find all childs that do not have some belongsTo relation (anymore) using
`fk IS NULL`. You can only trust the results here if you have the sanity constraints here for cleanup of those fields on delete.

A migration that could be proposed to you could look like this:
```php
    $this->table('repositories')
        ->addForeignKey('module_id', 'modules', ['id'], ['delete' => 'SET_NULL'])
        ->update();
```
The `module_id` is `DEFAULT NULL` and as such, deleting now the module will auto-set this to false rather than keeping the old id (that cannot be joined in anymore).

You can test the SQL issue on nullable and deleting live [here](http://sqlfiddle.com/#!9/816f16c/1).
