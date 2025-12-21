<?php

namespace Setup;

use Bake\Command\SimpleBakeCommand;
use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;
use Setup\Command\BakeHealthcheckCommand;
use Setup\Command\CliTestCommand;
use Setup\Command\CurrentConfigConfigureCommand;
use Setup\Command\CurrentConfigDisplayCommand;
use Setup\Command\CurrentConfigPhpinfoCommand;
use Setup\Command\CurrentConfigValidateCommand;
use Setup\Command\DbBackupCreateCommand;
use Setup\Command\DbBackupRestoreCommand;
use Setup\Command\DbDataDatesCommand;
use Setup\Command\DbDataEnumsCommand;
use Setup\Command\DbDataOrphansCommand;
use Setup\Command\DbInitCommand;
use Setup\Command\DbIntegrityBoolsCommand;
use Setup\Command\DbIntegrityConstraintsCommand;
use Setup\Command\DbIntegrityIntsCommand;
use Setup\Command\DbIntegrityKeysCommand;
use Setup\Command\DbIntegrityNullsCommand;
use Setup\Command\DbResetCommand;
use Setup\Command\DbWipeCommand;
use Setup\Command\HealthcheckCommand;
use Setup\Command\HelpCommand;
use Setup\Command\MailCheckCommand;
use Setup\Command\MaintenanceModeActivateCommand;
use Setup\Command\MaintenanceModeDeactivateCommand;
use Setup\Command\MaintenanceModeStatusCommand;
use Setup\Command\MaintenanceModeWhitelistCommand;
use Setup\Command\ResetCommand;
use Setup\Command\UserCreateCommand;
use Setup\Command\UserUpdateCommand;

/**
 * Plugin for Setup
 */
class SetupPlugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected bool $middlewareEnabled = false;

	/**
	 * @param \Cake\Console\CommandCollection $commands
	 *
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		// Enable compact help command if configured
		if (Configure::read('Setup.compactHelp')) {
			$commands->remove('help');
			$commands->add('help', HelpCommand::class);
		}

		$commands->add('healthcheck', HealthcheckCommand::class);

		$commands->add('maintenance_mode status', MaintenanceModeStatusCommand::class);
		$commands->add('maintenance_mode activate', MaintenanceModeActivateCommand::class);
		$commands->add('maintenance_mode deactivate', MaintenanceModeDeactivateCommand::class);
		$commands->add('maintenance_mode whitelist', MaintenanceModeWhitelistCommand::class);

		$commands->add('current_config display', CurrentConfigDisplayCommand::class);
		$commands->add('current_config configure', CurrentConfigConfigureCommand::class);
		$commands->add('current_config validate', CurrentConfigValidateCommand::class);
		$commands->add('current_config phpinfo', CurrentConfigPhpinfoCommand::class);

		$commands->add('db init', DbInitCommand::class);
		$commands->add('db reset', DbResetCommand::class);
		$commands->add('db wipe', DbWipeCommand::class);

		$commands->add('db_integrity keys', DbIntegrityKeysCommand::class);
		$commands->add('db_integrity constraints', DbIntegrityConstraintsCommand::class);
		$commands->add('db_integrity nulls', DbIntegrityNullsCommand::class);
		$commands->add('db_integrity bools', DbIntegrityBoolsCommand::class);
		$commands->add('db_integrity ints', DbIntegrityIntsCommand::class);

		$commands->add('db_data dates', DbDataDatesCommand::class);
		$commands->add('db_data enums', DbDataEnumsCommand::class);
		$commands->add('db_data orphans', DbDataOrphansCommand::class);

		$commands->add('db_backup create', DbBackupCreateCommand::class);
		$commands->add('db_backup restore', DbBackupRestoreCommand::class);

		$commands->add('user create', UserCreateCommand::class);
		$commands->add('user update', UserUpdateCommand::class);

		$commands->add('reset', ResetCommand::class);
		$commands->add('mail_check', MailCheckCommand::class);
		$commands->add('cli_test', CliTestCommand::class);

		if (class_exists(SimpleBakeCommand::class)) {
			$commands->add('bake healthcheck', BakeHealthcheckCommand::class);
		}

		return $commands;
	}

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->prefix('Admin', function (RouteBuilder $routes): void {
			$routes->plugin('Setup', function (RouteBuilder $routes): void {
				$routes->connect('/', ['controller' => 'Setup', 'action' => 'index']);

				$routes->fallbacks();
			});
		});

		$routes->plugin('Setup', ['path' => '/setup'], function (RouteBuilder $routes) {
			$routes->setExtensions(['json']);
			$routes->connect('/healthcheck', ['controller' => 'Healthcheck', 'action' => 'index']);
		});
	}

}
