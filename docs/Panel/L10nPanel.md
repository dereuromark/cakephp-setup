## DebugKit L10n Panel
The Setup plugin ships with a useful DebugKit panel to show quickly the current localization status
- Datetime
- Date
- Time

### Enable the panel
Activate the panel in your config:

```php
    'DebugKit' => [
        'panels' => [
            ...
            'Setup.L10n' => true,
        ],
    ],
```

Now it should be visible in your DebugKit panel list.
