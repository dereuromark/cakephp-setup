<?php
declare(strict_types=1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use Cake\Utility\Security;

/**
 * Outputs the current configuration
 * - DB (default and test)
 * - Cache
 * - ...
 *
 * @author Mark Scherer
 * @license MIT
 */
class CurrentConfigDisplayCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Displays runtime configuration (config and environment).';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$io->info('Security Salt: ' . Security::getSalt());
		$io->info('Full Base URL: ' . Configure::read('App.fullBaseUrl'));

		$io->out();

		$time = new DateTime();
		$timestamp = $time->getTimestamp();
		$offset = (int)($time->getOffset() / HOUR);
		$io->info('Datetime: ' . $time->format(FORMAT_DB_DATETIME) . ' (' . date_default_timezone_get() . ') [GMT' . ($offset > 0 ? '+' . $offset : '-' . abs($offset)) . ']');
		$io->info('Timestamp: ' . $timestamp . ' => ' . (new DateTime(date(FORMAT_DB_DATETIME, $timestamp)))->format(FORMAT_DB_DATETIME));

		$io->out();

		$io->info('Email Config:');
		$config = (array)Mailer::getConfig('default');
		foreach ($config as $key => $value) {
			$io->out(' - ' . $key . ': ' . $value);
		}

		$io->out();

		$io->info('ENV:');
		foreach ($_ENV as $key => $value) {
			$io->out(' - ' . $key . ': ' . $value);
		}

		return CommandInterface::CODE_SUCCESS;
	}

}
