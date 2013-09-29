<?php

App::uses('AppShell', 'Console/Command');
App::uses('MaintenanceLib', 'Setup.Lib');
App::uses('Validation', 'Utility');

/**
 * Activate and deactivate "Maintenance Mode" for an application.
 * Also accepts a whitelist of IP addresses that should be excluded (sys admins etc).
 *
 * Use -d duration option to set a timeout. Otherwise the maintenance window has to
 * be closed manually.
 *
 * @author Mark Scherer
 * @licence MIT
 */
class MaintenanceShell extends AppShell {

	public $Maintenance;

	public function startup() {
		parent::startup();

		$this->Maintenance = new MaintenanceLib();
	}

	public function status() {
		if ($res = $this->Maintenance->isMaintenanceMode()) {
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
	 * @return void
	 */
	public function whitelist() {
		$ips = $this->args;
		if (!empty($ips)) {
			foreach ($ips as $ip) {
				if (!Validation::ip($ip)) {
					return $this->error($ip . ' is not a valid IP address.');
				}
			}
			if ($this->params['remove']) {
				$this->Maintenance->clearWhitelist($ips);
			} else {
				$this->Maintenance->whitelist($ips);
			}
			$this->out('Done!', 2);
		} else {
			if ($this->params['remove']) {
				$this->Maintenance->clearWhitelist();
			}
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
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'duration' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Duration in minutes - optional.'),
					'default' => ''
				),
			)
		);
		$whitelistParser = array(
			'options' => array(
				'remove' => array(
					'short' => 'r',
					'help' => __d('cake_console', 'Remove either all or specific IPs.'),
					'boolean' => true
				),
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', 'A shell to put the whole site into maintenance mode'))
			->addSubcommand('status', array(
				'help' => __d('cake_console', 'See the current status'),
				'parser' => $subcommandParser
			))
			->addSubcommand('activate', array(
				'help' => __d('cake_console', 'Activate maintenance mode'),
				'parser' => $subcommandParser
			))
			->addSubcommand('deactivate', array(
				'help' => __d('cake_console', 'Deactivate maintenance mode'),
				'parser' => $subcommandParser
			))
			->addSubcommand('whitelist', array(
				'help' => __d('cake_console', 'Configure whitelisted IPs.'),
				'parser' => $whitelistParser
			));
	}

}
