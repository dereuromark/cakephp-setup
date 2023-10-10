<?php
declare(strict_types=1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Error\Debugger;

/**
 * Outputs the current configuration
 * - DB (default and test)
 * - Cache
 * - ...
 *
 * @author Mark Scherer
 * @license MIT
 */
class CurrentConfigConfigureCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Outputs configure values for given dot path.';
	}

	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		return $parser->addArgument('key');
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$key = $args->getArgument('key');
		$config = Configure::read($key);
		if (is_array($config)) {
			ksort($config);
		}
		$type = Debugger::getType($config);
		if (is_array($config)) {
			$type .= ' and size of ' . count($config);
		}
		$io->out(print_r($config, true));
		$io->info('of type ' . $type);

		return CommandInterface::CODE_SUCCESS;
	}

}
