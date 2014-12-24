# Maintenance Mode

You can run shells from the ROOT dir as `/bin/cake [shell] [command]` (or `.\bin\cake [shell] [command]` for Windows).


## Maintenance Shell
This should be the preferred way of enabling and disabling the

## Setup Component
The setup component adds some additional goddies on top:

A flash message shows you if you are whitelisted.

You can set the maintenance mode via URL, if you quickly need to jump into it again:
```
// append to the URL
?maintenance=1
// optionally with timeout
?maintenance=1&duration={time}
```
With time in minutes for infinite time. It will automatically whitelist this IP, as you could not
deactivate it again otherwise.

### Note
In productive mode you need a pwd, though, on top: `?pwd={YOURPWD}`.

For security reasons you need to change the password, once used.
Also, deactivate the URl access completely by removing the config pwd, when not in use, to
prevent brute force guesses.