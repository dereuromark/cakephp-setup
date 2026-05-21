# Console Commands

You can run any command from the application root with `bin/cake [command]`.

## Compact help command

The plugin provides an optional compact help command that replaces the core help
with a more readable format, using bracket notation for subcommands.

Enable it in your configuration:

```php
'Setup' => [
    'compactHelp' => true,
],
```

When enabled, `bin/cake help` displays commands in a compact format:

```text
Available Commands:

 - bake [controller|model|template|...]
 - cache [clear|clear_all|list]
 - migrations [migrate|rollback|status|...]
```

Use `bin/cake help -v` for verbose output with descriptions and plugin grouping.

You can also filter by command prefix:

```bash
bin/cake help bake
```

## Application maintenance

### CurrentConfig

These commands quickly show the current config, for both the database and the
cache:

```bash
bin/cake current_config display
```

You can also quickly view the `phpinfo()` output:

```bash
bin/cake current_config phpinfo
```

::: tip Filter with grep
On Linux systems (or any container/VM environment), use `grep` to quickly filter
the output. For example, to see only your Xdebug settings for CLI:

```bash
bin/cake current_config phpinfo | grep xdebug
```

This is very useful and quicker than any other lookup on the CLI.
:::

## Database

### Init

Initialize the database:

```bash
bin/cake db init
```

### Reset

Remove all content from the tables, excluding the phinx migration tables:

```bash
bin/cake db reset
```

::: warning
Be careful — make sure you have a backup before doing this.
:::

### Wipe

Hard-reset the database, dropping all tables:

```bash
bin/cake db wipe
```

::: warning
Be careful — make sure you have a backup before doing this.
:::

## Database integrity

### Keys

Alerts you about possible non-unsigned integer (foreign) keys, in terms of
data-integrity issues.

- Provides migration-file content to be executed.
- This is a prerequisite for constraints and other features that need the keys to
  be aligned.

### Constraints

Alerts you about possible missing constraints, in terms of data-integrity issues.

- Provides migration-file content to be executed.
- Optionally relates a foreign key that is not set back to `NULL` when the related
  `has*` entity is removed. This is only relevant if the relation is not
  `dependent => true`.

### Nulls

Checks for fields that might need to be nullable, or the opposite.

- Converts `NULL` fields without a default value.

### Bools

Checks all boolean field types — usually `tinyint(1)` — for a valid schema.

Since MySQL 8+ they can no longer be unsigned, or the length is lost (making them
normal `tinyint` or enums). To avoid this, run this on a database older than v8 and
then perform a safe migration toward 8+.

- Converts any bool field to a signed one.

### Ints

Integers in newer MySQL versions lose their length as part of the schema
definition. To preserve this sometimes-important metadata, it can be written (and
later reused) into the column comment as metadata.

- Adds the length info to the comment as a prefix (`[schema] length: x`).

## Database data validation

These commands help detect and fix invalid data in the database.

### Dates

Check for invalid zero date/datetime values (`0000-00-00` or
`0000-00-00 00:00:00`):

```bash
bin/cake db_data dates
```

Options:

- `-f, --fix`: Fix invalid dates by setting them to `NULL`.
- `-c, --connection`: Database connection to use (default: `default`).
- Use `-v` for verbose output, including the SQL fix statements.

You can also check a specific table:

```bash
bin/cake db_data dates users
```

### Enums

Check for invalid enum values against PHP `BackedEnum` definitions:

```bash
bin/cake db_data enums
```

This command:

- Validates values against PHP `BackedEnum` class definitions.
- Detects MySQL `ENUM` columns and suggests migrating to `VARCHAR` plus a PHP
  `BackedEnum`.
- Shows mismatches between the PHP and MySQL enum definitions.

Options:

- `-f, --fix`: Fix invalid enum values by setting them to `NULL`.
- `-p, --plugin`: Plugin to check.
- `-c, --connection`: Database connection to use (default: `default`).
- Use `-v` for verbose output, including the SQL fix statements.

You can also check a specific model:

```bash
bin/cake db_data enums Users
```

### Orphans

Check for orphaned foreign-key records (FK values pointing to non-existent
parents):

```bash
bin/cake db_data orphans
```

This is useful when constraints were not enforced historically, or after data
migrations.

Options:

- `-f, --fix`: Fix orphaned records by setting the FK to `NULL`.
- `-d, --delete`: Delete orphaned records instead of nullifying (use with `--fix`).
- `-p, --plugin`: Plugin to check.
- `-c, --connection`: Database connection to use (default: `default`).
- Use `-v` for verbose output, including the SQL statements.

You can also check a specific model:

```bash
bin/cake db_data orphans Users
```

## Backup: create and restore

### Create

Dump a full database schema, including content, into a file in your backup folder.
It uses `mysqldump` and is therefore the most performant option here.

### Restore

Restore a dumped file into the database, overwriting the previous values there.

## Other tools

### CliTest

Lets you test certain features — such as routing — and how (or whether) they work
in CLI:

```bash
bin/cake cli_test
```

Depending on your domain it outputs something like:

```text
Router::url('/'):
    /
Router::url(['controller' => 'test']):
    /test
Router::url('/', true):
    http://example.local/
Router::url(['controller' => 'test'], true):
    http://example.local/test
```

### User

Lets you quickly add a new user to your `users` table, including a properly hashed
password, so you can log in:

```bash
bin/cake user create [user]
```

To update an existing user:

```bash
bin/cake user update [user]
```

If you need to provide custom defaults, use a callback. Set it in your `app.php`:

```php
    'UserCreate.callable' => function (User $user): User {
        // ...

        return $user;
    },
```

### Reset

Lets you reset all emails or passwords. This is very useful when copying live data
dumps into your local development environment. Afterward you can log in with a
known password for any user — for example after resetting all passwords to a
shared development value:

```bash
bin/cake reset email
```

or

```bash
bin/cake reset pwd
```

## Tooling

### Mailmap

Creates a `.mailmap` file from your current commit history. Requires Git as a
tool:

```bash
bin/cake mailmap generate
```

This is used, for example, in CakePHP to combine multiple accounts of the same
user for `git shortlog`. Check the results with `git shortlog -sne` — it might
require some manual adjustments afterward.

## See also

- [Bake Templates](/console/bake) — enhanced scaffolding via the Setup theme.
- [Web Backend](/controller/) — the same integrity tooling from the web.
