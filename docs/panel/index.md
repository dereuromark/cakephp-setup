# L10n DebugKit Panel

The Setup plugin ships a useful DebugKit panel that quickly shows the current
localization status:

- Datetime
- Date
- Time

## Enable the panel

Activate the panel in your config:

```php
    'DebugKit' => [
        'panels' => [
            // ...
            'Setup.L10n' => true,
        ],
    ],
```

It should now be visible in your DebugKit panel list.
