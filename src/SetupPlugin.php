<?php

namespace Setup;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;
use Setup\Command\MaintenanceModeActivateCommand;
use Setup\Command\MaintenanceModeDeactivateCommand;
use Setup\Command\MaintenanceModeStatusCommand;
use Setup\Command\MaintenanceModeWhitelistCommand;

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
		$commands = parent::console($commands);
		$commands->add('maintenance_mode status', MaintenanceModeStatusCommand::class);
		$commands->add('maintenance_mode activate', MaintenanceModeActivateCommand::class);
		$commands->add('maintenance_mode deactivate', MaintenanceModeDeactivateCommand::class);
		$commands->add('maintenance_mode whitelist', MaintenanceModeWhitelistCommand::class);

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
	}

}
