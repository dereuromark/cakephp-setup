<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ModelAwareTrait;
use Cake\Utility\Inflector;

if (!defined('CLASS_USERS')) {
	define('CLASS_USERS', 'Users');
}
if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'App\Model\Entity\User');
}

/**
 * Update a new user with password from CLI.
 *
 * @author Mark Scherer
 * @license MIT
 */
class UserUpdateCommand extends Command {

	use ModelAwareTrait;

	/**
	 * Creates a new user including a freshly hashed password.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		/** @var \App\Model\Table\UsersTable $Users */
		$Users = $this->fetchModel(CLASS_USERS);

		$displayField = $Users->getDisplayField();
		$displayFieldName = Inflector::humanize($displayField);

		$displayFieldValue = $args->getArgument('login');
		while (!$displayFieldValue) {
			$displayFieldValue = $io->ask($displayFieldName);
		}

		/** @var \App\Model\Entity\User $user */
		$user = $Users->find()->where([$displayField => $displayFieldValue])->firstOrFail();

		$password = $args->getArgument('password');
		while (!$password) {
			$password = $io->ask('Password');
		}

		$Users->addBehavior('Tools.Passwordable', ['confirm' => false]);
		$Users->patchEntity($user, ['pwd' => $password]);

		if ($args->getOption('dry-run')) {
			$io->out('User dry-run inserted!');
			$io->out('Pwd Hash: ' . $user->password);

			return;
		}

		$Users->saveOrFail($user);

		$io->success('Password updated for user ' . $displayFieldValue);
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		return parent::getOptionParser()
			->setDescription('The User shell can create a user on the fly for local development.
Note that you can define the constant CLASS_USERS in your bootstrap to point to another table class, if \'Users\' is not used.
Make sure you configured the Passwordable behavior accordingly as per docs.')
			->addArgument('login', [
				'help' => 'Display field value',
			])
			->addArgument('password')
			->addOption('dry-run', [
				'short' => 'd',
				'help' => 'Dry run the command, no data will actually be modified.',
				'boolean' => true,
			]);
			/*
			->addSubcommand('index', [
				'help' => 'Lists current users.',
				'parser' => $listParser,
			])
			->addSubcommand('create', [
				'help' => 'Create a new user with email and password provided.',
				'parser' => $createParser,
			])
			->addSubcommand('update', [
				'help' => 'Update a specific user with a new password.',
				'parser' => $subcommandParser,
			])
			->addSubcommand('password', [
				'help' => 'Generate a hash from a given password.',
				'parser' => $subcommandParser,
			]);*/
	}

}
