# Useful Setup shells

You can run any shell from the ROOT dir as `bin/cake [shell] [command]` (or `.\bin\cake [shell] [command]` for Windows).


## Application Maintenance Shells

### CurrentConfigShell
This shell lets you quickly see what the current config is, both for DB and cache.

- `bin/cake current_config display`

You can also quickly see the phpinfo() output:

- `bin/cake current_config phpinfo`

Tip (for linux systems or any CakeBox env etc): Use `grep` to quickly filter the output.
So if you are only interested in your `xdebug` settings for CLI:

- `bin/cake current_config phpinfo | grep xdebug`

Very useful and quicker than any other lookup on CLI.

### DbConstraintsShell
Alerts about possible constraints missing in terms of data integrity issues.

- Optional relation with foreign key not being set back to null when related has* entity has removed been removed.
  This is only relevant if relation is not "dependent => true", though.

### DbMigrationShell
A Shell to ease database migrations needed.

- Convert null fields without a default value.

### DbMaintenanceShell
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


### TestCliShell
Let's you test certain features like Routing and how/if they work in CLI.

- `bin/cake Setup.TestCli router`

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

### UserShell
Let's you quickly add a new user to your "users" table, including a properly hashed password, so
you can log in.

- `bin/cake user create [user]`

Alternatively, you can also just print out the hash for a given plain-text password and then manually insert that into the database.

- `bin/cake user pwd [pwd]`

To update existing user you can use:

- `bin/cake user update [user]`

To list currently available users (or check if there are any yet):
- `bin/cake user index`

### ResetShell
Let's you reset all emails or passwords, this is very useful when copying live data dumps to your local dev
environment. Afterwards you can login with `123` for any user, when resetting the passwords to this value, for example.

- `bin/cake reset email`

or

- `bin/cake reset pwd`

## Code Cleanup Shells

### SuperfluousWhitespaceShell
Removes trailing whitespace from files and asserts a single newline at the end of PHP files.
It can also remove any trailing `?>` at the end.

- `bin/cake superfluous_whitespace clean`

and

- `bin/cake superfluous_whitespace eof`

### IndentShell
Corrects indentation (using PSR-2-R and a single tab, no spaces!) of (code) files.

- `bin/cake indent folder `

### CopyrightRemovalShell
Removes the unnecessary copyrights from the skeleton application code.
It can and only may used for such skeleton "bake" files that could just as well be manually "re-created" for starting a fresh application project.

- `bin/cake copyright_removal clean`

## Tooling Shells

### MailmapShell
Creates a `.mailmap` file from your current commit history. Requires Git as tool.

- `bin/cake mailmap generate`

This is for example used in CakePHP to combine multiple accounts of the same user for `git shortlog`.
Check out the results with `git shortlog -sne` - might require some more manual adjustements afterwards.
