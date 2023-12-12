# Useful Setup commands

You can run any command from the ROOT dir as `bin/cake [command]`.


## Application Maintenance

### CurrentConfig*
These commands let you quickly see what the current config is, both for DB and cache.

- `bin/cake current_config display`

You can also quickly see the phpinfo() output:

- `bin/cake current_config phpinfo`

Tip (for linux systems or any CakeBox env etc): Use `grep` to quickly filter the output.
So if you are only interested in your `xdebug` settings for CLI:

- `bin/cake current_config phpinfo | grep xdebug`

Very useful and quicker than any other lookup on CLI.

## DB Maintenance

### DbUnsigned
Alerts about possible not unsigned integer (foreign) keys in terms of data integrity issues.

- Provides a migration file content to be executed.
- This is needed as pre-requirement for DbConstraints and others that need the keys to be aligned.

### DbConstraints
Alerts about possible constraints missing in terms of data integrity issues.

- Provides a migration file content to be executed.
- Optional relation with foreign key not being set back to null when related has* entity has removed been removed.
  This is only relevant if relation is not "dependent => true", though.

### DbMigrationShell (TODO)
A Shell to ease database migrations needed.

- Convert null fields without a default value.

### DbMaintenanceShell (TODO)
Easily convert table format or table encoding. Those are mainly relevant for MySQL.

To make sure your tables are fit for 3 and 4 byte unicode (including Emoji etc), you can run:

- `bin/cake db_maintenance encoding`

To switch the engine from InnoDB to MyISAM or vice versa:

- `bin/cake db_maintenance engine`

A useful command to migrate a database with prefixed tables to one for CakePHP 3.x (without prefixes):

- `bin/cake db_maintenance table_prefix`

To assert foreign keys are not `DEFAULT '0'` but `DEFAULT NULL`, you can run:

- `bin/cake db_maintenance foreign_keys`

If you want to assert that for dates (`0000-00-00` etc are usually not valid dates):

- `bin/cake db_maintenance dates`

Most of those commands contain a `-d` dry run param, so you can output the generated SQL and pass it into your Migrations scripts instead.

## Others

### TestCli
Let's you test certain features like Routing and how/if they work in CLI.

- `bin/cake cli_test`

Depending on your domain it will output something like:
```
Router::url('/'):
    /
Router::url(['controller' => 'test']):
    /test
Router::url('/', true):
    http://example.local/
Router::url(['controller' => 'test'], true):
    http://example.local/test
```

### User*
Let's you quickly add a new user to your "users" table, including a properly hashed password, so
you can log in.

- `bin/cake user create [user]`

To update existing user you can use:

- `bin/cake user update [user]`

### Reset
Lets you reset all emails or passwords, this is very useful when copying live data dumps to your local dev
environment. Afterward you can login with `123` for any user, when resetting the passwords to this value, for example.

- `bin/cake reset email`

or

- `bin/cake reset pwd`

## Tooling

### MailmapShell
Creates a `.mailmap` file from your current commit history. Requires Git as tool.

- `bin/cake mailmap generate`

This is for example used in CakePHP to combine multiple accounts of the same user for `git shortlog`.
Check out the results with `git shortlog -sne` - might require some more manual adjustements afterwards.
