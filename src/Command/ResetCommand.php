<?php

declare(strict_types=1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;
use Setup\Auth\PasswordHasherFactory;

if (!defined('CLASS_USERS')) {
	define('CLASS_USERS', 'Users');
}

/**
 * @author Mark Scherer
 * @license MIT
 */
class ResetCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Can reset local development data.
Note that you can define the constant CLASS_USERS in your bootstrap to point to another table class, if \'Users\' is not used.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$field = $args->getArgument('field');
		if (!in_array($field, ['email', 'pwd'], true)) {
			$io->abort('Not a valid field type: ' . $field);
		}

		$this->{$field}($args, $io);
	}

	/**
	 * Resets all emails - e.g. to your admin email (for local development).
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return void
	 */
	protected function email(Arguments $args, ConsoleIo $io): void {
		$email = $args->getArgument('value');

		$io->out('Email:');
		while (!$email || !Validation::email($email)) {
			$email = $io->ask('New email address (must have a valid form at least)');
		}

		$Users = $this->table($args);
		if (!$Users->hasField('email')) {
			$io->abort(CLASS_USERS . ' table doesnt have an email field!');
		}

		$io->hr();
		$io->out('Resetting...');

		if (!$args->getOption('dry-run')) {
			$count = $Users->updateAll(['email' => $email], ['email !=' => $email]);
		} else {
			$count = $Users->find('all', ...['conditions' => [CLASS_USERS . '.email !=' => $email]])->count();
		}
		$io->out($count . ' emails reset - DONE');
	}

	/**
	 * Resets all pwds to a simple pwd (for local development).
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return void
	 */
	public function pwd(Arguments $args, ConsoleIo $io): void {
		$pwd = $args->getArgument('value');

		$pwdToHash = null;
		if ($pwd) {
			$pwdToHash = $pwd;
		}
		while (!$pwdToHash || mb_strlen($pwdToHash) < 2) {
			$pwdToHash = $io->ask(__('Password to Hash (2 characters at least)'));
		}
		$io->hr();
		$io->out('Password:');
		$io->out($pwdToHash);

		$hasher = 'Default';
		$hashType = Configure::read('Passwordable.passwordHasher');
		if ($hashType) {
			$hasher = $hashType;
		}
		$passwordHasher = PasswordHasherFactory::build($hasher);
		$pwd = $passwordHasher->hash($pwdToHash);
		if (!$pwd) {
			$io->abort('Hashing failed');
		}

		$io->hr();
		$io->out('Hash:');
		$io->out($pwd);

		$io->hr();
		$io->out('resetting...');

		$Users = $this->table($args);
		if (!$Users->hasField('password')) {
			$io->abort(CLASS_USERS . ' table doesnt have a password field!');
		}

		if (!$args->getOption('dry-run')) {
			$count = $Users->updateAll(['password' => $pwd], ['password !=' => $pwd]);
		} else {
			$count = $Users->find('all', ...['conditions' => [CLASS_USERS . '.password !=' => $pwd]])->count();
		}
		$io->out($count . ' pwds reset - DONE');
	}

	/**
	 * Hook action for defining this command's option parser.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 *
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);
		$parser->setDescription(static::getDescription());

		$parser->addArgument('field', [
			'help' => 'Chose "email" or "pwd". For pwds it will reset all user passwords via Hasher class. If you are not using Default hasher, make sure
 you provide the correct one via Configure \'Passwordable.passwordHasher\'.',
			'required' => true,
		]);
		$parser->addArgument('value', [
			'help' => 'Value to reset to.',
		]);

		$parser->addOption('connection', [
			'short' => 'c',
			'help' => 'The datasource connection to use.',
			'default' => 'default',
		]);
		$parser->addOption('dry-run', [
			'short' => 'd',
			'help' => 'Dry run the reset command, no data will actually be modified.',
			'boolean' => true,
		]);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args
	 *
	 * @return \Cake\ORM\Table
	 */
	protected function table(Arguments $args): Table {
		$Users = TableRegistry::getTableLocator()->get(CLASS_USERS);
		if ($args->getOption('connection')) {
			/** @var \Cake\Database\Connection $connection */
			$connection = ConnectionManager::get((string)$args->getOption('connection'));
			$Users->setConnection($connection);
		}

		return $Users;
	}

}
