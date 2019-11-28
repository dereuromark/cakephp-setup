<?php

namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Validation\Validation;
use Setup\Maintenance\Maintenance;

/**
 * Activate and deactivate "Maintenance Mode" for an application.
 * Also accepts a whitelist of IP addresses that should be excluded (sys admins etc).
 *
 * Use -d duration option to set a timeout. Otherwise the maintenance window has to
 * be closed manually.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MaintenanceModeShell extends Shell {

	/**
	 * @var \Setup\Maintenance\Maintenance
	 */
	public $Maintenance;

	/**
	 * @return void
	 */
	public function startup() {
		parent::startup();

		$this->Maintenance = new Maintenance();
	}

	/**
	 * @return void
	 */
	public function status() {
		$isMaintenanceMode = $this->Maintenance->isMaintenanceMode();
		if ($isMaintenanceMode) {
			$this->out('Maintenance mode active!');
		} else {
			$this->out('Maintenance mode not active');
		}
	}

	/**
	 * Deactivate maintenance mode.
	 * Will not remove whitelisted IPs.
	 *
	 * @return void
	 */
	public function deactivate() {
		$this->Maintenance->setMaintenanceMode(false);
		$this->out('Maintenance mode deactivated ...');
	}

	/**
	 * Activate maintenance mode with an optional timeout setting.
	 *
	 * @return void
	 */
	public function activate() {
		$duration = (int)$this->params['duration'];
		$this->Maintenance->setMaintenanceMode($duration);
		$this->out('Maintenance mode activated ...');
	}

	/**
	 * Whitelist specific IPs. Each argument is a single IP.
	 * Not passing any argument will output the current whitelist.
	 *
	 * @return int|null
	 */
	public function whitelist() {
		if ($this->params['remove']) {
			$this->Maintenance->clearWhitelist();
		}

		$ips = $this->args;
		if (!empty($ips)) {
			foreach ($ips as $ip) {
				if (!Validation::ip($ip)) {
					$this->abort($ip . ' is not a valid IP address.');
				}
			}
			if ($this->params['remove']) {
				$this->Maintenance->clearWhitelist($ips);
			} else {
				$this->Maintenance->whitelist($ips, $this->params['debug']);
			}
			$this->out('Done!', 2);
		}

		$this->out('Current whitelist:');
		$ips = $this->Maintenance->whitelist();
		if (!$ips) {
			$this->out('n/a');
		} else {
			$this->out($ips);
		}
	}

	/**
	 * @return void
	 */
	public function reset() {
		$this->Maintenance->setMaintenanceMode(false);
		$this->Maintenance->clearWhitelist();
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'duration' => [
					'short' => 'd',
					'help' => 'Duration in minutes - optional.',
					'default' => '',
				],
			],
		];
		$whitelistParser = [
			'options' => [
				'remove' => [
					'short' => 'r',
					'help' => 'Remove either all or specific IPs.',
					'boolean' => true,
				],
				'debug' => [
					'short' => 'd',
					'help' => 'Enable debug mode for whitelisted IPs.',
					'boolean' => true,
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('A shell to put the whole site into maintenance mode')
			->addSubcommand('status', [
				'help' => 'See the current status',
				'parser' => $subcommandParser,
			])
			->addSubcommand('activate', [
				'help' => 'Activate maintenance mode',
				'parser' => $subcommandParser,
			])
			->addSubcommand('deactivate', [
				'help' => 'Deactivate maintenance mode',
				'parser' => $subcommandParser,
			])
			->addSubcommand('reset', [
				'help' => 'Reset maintenance mode',
				'parser' => $subcommandParser,
			])
			->addSubcommand('whitelist', [
				'help' => 'Configure whitelisted IPs.',
				'parser' => $whitelistParser,
			]);
	}

}
