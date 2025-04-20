<?php
declare(strict_types = 1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Setup\Maintenance\Maintenance;
use Setup\Utility\Validation;

/**
 * Activate and deactivate "Maintenance Mode" for an application.
 * Also accepts a whitelist of IP addresses that should be excluded (sys admins etc).
 *
 * @author Mark Scherer
 * @license MIT
 */
class MaintenanceModeWhitelistCommand extends Command {

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
		$ip = $args->getArgument('ip');
		if ($ip) {
			if (!Validation::ipOrSubnet($ip)) {
				$io->abort($ip . ' is not a valid IP address or subnet.');
			}
			if ($args->getOption('remove')) {
				$this->Maintenance->clearWhitelist([$ip]);
			} else {
				$this->Maintenance->addToWhitelist([$ip]);
			}
			$io->out('Done!', 2);
		} else {
			if ($args->getOption('remove')) {
				$this->Maintenance->clearWhitelist();
			}
		}

		$io->out('Current whitelist:');
		/** @var array<string> $ip */
		$ip = $this->Maintenance->whitelist();
		if (!$ip) {
			$io->out('n/a');
		} else {
			$io->out($ip);
		}

		return null;
	}

	/**
	 * Hook action for defining this command's option parser.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addArgument('ip', [
			'help' => 'IP address (or subnet) to specify.',
		]);

		$parser->addOption('remove', [
			'short' => 'r',
			'help' => 'Remove either all or specific IPs.',
			'boolean' => true,
		]);
		$parser->addOption('debug', [
			'short' => 'd',
			'help' => 'Enable debug mode for whitelisted IPs.',
			'boolean' => true,
		]);

		return $parser;
	}

}
