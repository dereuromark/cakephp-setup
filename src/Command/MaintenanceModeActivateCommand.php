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
 * Use -d duration option to set a timeout. Otherwise the maintenance window has to
 * be closed manually.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MaintenanceModeActivateCommand extends Command {

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
		$duration = (int)$args->getOption('duration');
		$this->Maintenance->setMaintenanceMode($duration);
		$io->out('Maintenance mode activated ...');
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

		$parser->addOption('duration', [
			'short' => 'd',
			'help' => 'Duration in minutes - optional.',
		]);

		return $parser;
	}

}
