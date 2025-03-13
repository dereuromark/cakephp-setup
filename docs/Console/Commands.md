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


## DB

### Init
Init the DB.
```bash
bin/cake db init
```

### Reset
Remove all content from tables, excluding the phinx migration tables.
```bash
bin/cake db reset
```

Careful, make sure to have a backup before doing this.

### Wipe
Hard-reset the DB, dropping all tables.
```bash
bin/cake db wipe
```

Careful, make sure to have a backup before doing this.


## DB Integrity

### Keys
Alerts about possible not unsigned integer (foreign) keys in terms of data integrity issues.

- Provides a migration file content to be executed.
- This is needed as pre-requirement for DbConstraints and others that need the keys to be aligned.

### Constraints
Alerts about possible constraints missing in terms of data integrity issues.

- Provides a migration file content to be executed.
- Optional relation with foreign key not being set back to null when related has* entity has removed been removed.
  This is only relevant if relation is not "dependent => true", though.

### Nulls
Check for fields that might need nullable or the opposite.

- Converts null fields without a default value.

### Bools
Check all boolean field types, usually tinyint(1), for valid schema.
Since MySQL 8+ they cannot be unsigned anymore or the length will be lost (making them normal tinyint or enums).
So in order to avoid this, make sure to run this on a < v8 DB and then have a safe migration towards 8+.

- Converts any bool field to a signed one.

### Ints
Ints in newer Mysql versions lose their length as schema definition.
In order to not lose this sometimes important meta info, they can be written (and then later re-used) to comment as meta data.

- Adds length field info to comment as prefix (`[schema] length: x`).

## Backup create and restore

### Create
Dump a full DB schema including content into a file in your backup folder.
It uses `mysqldump` and is therefore the most performant task possible here.

### Restore

Restore a dumped file into DB, overwriting the previous values there.

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
