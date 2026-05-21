<?php

/**
 * Setup Example Configuration
 *
 * Merge the keys below into your application's config/app.php (or
 * config/app_local.php) — do not replace the whole file, since this snippet
 * only contains this plugin's configuration. When copying entries that
 * reference imported classes, use fully-qualified class names or move the
 * `use` imports to the top of the target file. Customize the values as needed.
 */
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

		// Healthcheck configuration.
		// `checks` overrides the built-in default set (Setup\Healthcheck\HealthcheckCollector::defaultChecks()).
		// Each entry is a class-string of a CheckInterface (optionally a class-string => options pair).
		// Setting `checks` REPLACES the built-in default set entirely, so leave it unset
		// to keep all defaults. Uncomment and provide a COMPLETE list only for full control:
		// 'Healthcheck' => [
		//     'checks' => [
		//         \Setup\Healthcheck\Check\Environment\PhpVersionCheck::class,
		//     ],
		// ],
	],

	// Paths to external binaries used for DB dump/restore (Setup\Command\Traits\DbBackupTrait).
	// Only prepended on Windows when set; on Unix the bare command name is used.
	'Cli' => [
		'gitPath' => null, // Path prefix for gzip/gunzip binaries
		'mysqlPath' => null, // Path prefix for mysql/mysqldump binaries
	],

	// Top-level Healthcheck options read by individual check classes.
	'Healthcheck' => [
		'sslHost' => null, // Host for the SSL certificate expiry check; null => derive from Router::url('/', true)
		'checkCacheKeys' => [], // Additional cache config keys to verify (merged with the default cache keys)
		'diskSpacePaths' => [ROOT], // Paths checked by the disk space check; defaults to [ROOT]
	],

	// Maintenance mode handling (Setup\Controller\Component\SetupComponent).
	'Maintenance' => [
		'overwrite' => null, // When set (e.g. the whitelisted IP), maintenance mode is bypassed and a notice is shown
	],

	// User creation command hook (Setup\Command\UserCreateCommand).
	'UserCreate' => [
		'callable' => null, // Optional Closure(User $user): User to mutate the entity before saving
	],
];
