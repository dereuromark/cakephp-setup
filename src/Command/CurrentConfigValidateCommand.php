<?php
declare(strict_types=1);

namespace Setup\Command;

use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Exception;

/**
 * Outputs the current configuration
 * - DB (default and test)
 * - Cache
 * - ...
 *
 * @author Mark Scherer
 * @license MIT
 */
class CurrentConfigValidateCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Checks application config for CLI (DB, Cache).';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$io->out('DB default:');
		try {
			$db = ConnectionManager::get('default');
			$io->out(print_r($db->config(), true));
		} catch (Exception $e) {
			$io->err($e->getMessage());
		}

		$io->out('');
		$io->out('DB test:');
		try {
			$db = ConnectionManager::get('test');
			$io->out(print_r($db->config(), true));
		} catch (Exception $e) {
			$io->err($e->getMessage());
		}

		$io->out('');
		$io->out('Cache:');
		$io->out(print_r(Cache::getConfig('_cake_core_'), true));

		return CommandInterface::CODE_SUCCESS;
	}

}
