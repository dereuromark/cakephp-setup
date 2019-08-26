# Useful Setup shells

You can run any shell from the ROOT dir as `bin/cake [shell] [command]` (or `.\bin\cake [shell] [command]` for Windows).


## Application Maintenance Shells

### CurrentConfigShell
This shell lets you quickly see what the current config is, both for DB and cache.

- `bin/cake Setup.CurrentConfig display`

You can also quickly see the phpinfo() output:

- `bin/cake Setup.CurrentConfig phpinfo`

Tip (for linux systems or any CakeBox env etc): Use `grep` to quickly filter the output.
So if you are only interested in your `xdebug` settings for CLI:

- `bin/cake Setup.CurrentConfig phpinfo | grep xdebug`

Very useful and quicker than any other lookup on CLI.

### DbMaintenanceShell
Easily convert table format or table encoding. Those are mainly relevant for MySQL.

To make sure your tables are fit for 3 and 4 byte unicode (including Emoji etc), you can run:

- `bin/cake Setup.DbMaintenance encoding`

To switch the engine from InnoDB to MyISAM or vice versa:

- `bin/cake Setup.DbMaintenance engine`

A useful command to migrate a database with prefixed tables to one for CakePHP 3.x (without prefixes):

- `bin/cake Setup.DbMaintenance table_prefix`

To assert foreign keys are not `DEFAULT '0'` but `DEFAULT NULL`, you can run:

- `bin/cake Setup.DbMaintenance foreign_keys`

If you want to assert that for dates (`0000-00-00` etc are usually not valid dates):

- `bin/cake Setup.DbMaintenance dates`

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

- `bin/cake Setup.User create [user]`

Alternatively, you can also just print out the hash for a given plain-text password and then manually insert that into the database.

- `bin/cake Setup.User pwd [pwd]`

To list currently available users (or check if there are any yet):
- `bin/cake Setup.User index`

### ResetShell
Let's you reset all emails or passwords, this is very useful when copying live data dumps to your local dev
environment. Afterwards you can login with `123` for any user, when resetting the passwords to this value, for example.

- `bin/cake Setup.User reset email`

or

- `bin/cake Setup.User reset pwd`

## Code Cleanup Shells

### SuperfluousWhitespaceShell
Removes trailing whitespace from files and asserts a single newline at the end of PHP files.
It can also remove any trailing `?>` at the end.

- `bin/cake Setup.SuperfluousWhitespace clean`

and

- `bin/cake Setup.SuperfluousWhitespace eof`

### IndentShell
Corrects indentation (using PSR-2-R and a single tab, no spaces!) of (code) files.

- `bin/cake Setup.Indent folder `

### CopyrightRemovalShell
Removes the unnecessary copyrights from the skeleton application code.
It can and only may used for such skeleton "bake" files that could just as well be manually "re-created" for starting a fresh application project.

- `bin/cake Setup.CopyrightRemoval clean`

## Tooling Shells

### MailmapShell
Creates a `.mailmap` file from your current commit history. Requires Git as tool.

- `bin/cake Setup.Mailmap generate`

This is for example used in CakePHP to combine multiple accounts of the same user for `git shortlog`.
Check out the results with `git shortlog -sne` - might require some more manual adjustements afterwards.
