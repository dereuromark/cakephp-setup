<?php

return [
	'Setup' => [
		// Compact help command - replaces core help with a more compact format
		// Set to true to enable: bin/cake help will show bracket notation for subcommands
		'compactHelp' => false,

		// Session key for user authentication data (used for 404 notifications)
		// Defaults to 'Auth.User', use 'Auth' for CakeDC/Users plugin compatibility
		'sessionKey' => null,

		// Tables to exclude from the database overview in /admin/setup/database
		'blacklistedTables' => [],

		// Healthcheck configuration
		// 'Healthcheck' => [
		//     'checks' => [
		//         \Setup\Healthcheck\Check\Environment\PhpVersionCheck::class,
		//         // Add your custom checks here
		//     ],
		// ],
	],
];
