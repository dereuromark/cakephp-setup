<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Tools\Mailer\Mailer;

/**
 * Test mail sending from CLI
 */
class MailCheckCommand extends Command {

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		if (!Configure::read('Config.live')) {
			$io->warning('Configure::read(\'Config.live\') is not enabled. Normal emails wouldn\'t be sent. Overwriting it for this check only.');
			Configure::write('Config.live', true);
		}

		$io->verbose('From email as configured: ' . Configure::read('Config.systemEmail', Configure::read('Config.adminEmail')));

		$to = $io->ask('Email to send to', Configure::read('Config.adminEmail'));
		if (!$to) {
			return static::CODE_ERROR;
		}

		$email = new Mailer();
		$email->setTo($to);
		$email->setSubject('Test Mail from CLI');

		$url = Router::url('/', true);
		$message = <<<TXT
A test mail from CLI.

Example URL: $url
TXT;

		$result = $email->deliver($message);
		$io->verbose('Result:');
		$io->verbose((string)json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

		return static::CODE_SUCCESS;
	}

	/**
	 * Get the option parser.
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();
		$parser->setDescription('Send a test email from CLI to verify mail config.');

		return $parser;
	}

}
