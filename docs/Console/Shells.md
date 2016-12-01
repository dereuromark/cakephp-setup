# Useful Setup shells

You can run any shell from the ROOT dir as `/bin/cake [shell] [command]` (or `.\bin\cake [shell] [command]` for Windows).


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
Easily convert table format or table encoding.

### TestCliShell
Let's you test certain features like Routing and how/if they work in CLI.

### UserShell
Let's you quickly add a new user to your "users" table, including a properly hashed password, so
you can log in.

Alternatively, you can also just print out the hash for a given plain-text password and then manually insert that into the database.

### ResetShell
Let's you reset all emails or passwords, this is very useful when copying live data dumps to your local dev
environment. Afterwards you can login with `123` for any user, when resetting the passwords to this value, for example.


## Code Cleanup Shells

### WhitespaceShell
Removes trailing whitespace from files and asserts a single newline at the end of PHP files.
It can also remove any trailing `?>` at the end.

### IndentShell
Corrects indentation (using PSR-R and a single tab, no spaces!) of (code) files.
