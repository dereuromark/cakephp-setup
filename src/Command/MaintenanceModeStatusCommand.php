<?php
declare(strict_types = 1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Setup\Maintenance\Maintenance;

/**
 * Activate and deactivate "Maintenance Mode" for an application.
 * Also accepts a whitelist of IP addresses that should be excluded (sys admins etc).
 *
 * @author Mark Scherer
 * @license MIT
 */
class MaintenanceModeStatusCommand extends Command {

	/**
	 * @var \Setup\Maintenance\Maintenance
	 */
	protected $Maintenance;

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->Maintenance = new Maintenance();
	}

	/**
	 * Implement this action with your command's logic.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$isMaintenanceMode = $this->Maintenance->isMaintenanceMode();
		if ($isMaintenanceMode) {
			$io->out('Maintenance mode active!');
		} else {
			$io->out('Maintenance mode not active');
		}
	}

	/**
	 * Hook action for defining this command's option parser.
	 *
	 * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		return $parser;
	}

}
