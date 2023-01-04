<?php

namespace Setup\Shell;

use Cake\Auth\PasswordHasherFactory;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;

if (!defined('CLASS_USERS')) {
	define('CLASS_USERS', 'Users');
}

/**
 * Reset user data
 *
 * @author Mark Scherer
 * @license MIT
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class ResetShell extends Shell {

	/**
	 * Resets all emails - e.g. to your admin email (for local development).
	 *
	 * @param string|null $email
	 * @return void
	 */
	public function email($email = null) {
		$this->out('Email:');
		while (empty($email) || !Validation::email($email)) {
			$email = $this->in('New email address (must have a valid form at least)');
		}

		/** @var \App\Model\Table\UsersTable $Users */
		$Users = TableRegistry::getTableLocator()->get(CLASS_USERS);
		$this->Users = $Users;
		if (!$this->Users->hasField('email')) {
			$this->abort(CLASS_USERS . ' table doesnt have an email field!');
		}

		$this->hr();
		$this->out('Resetting...');

		if (empty($this->params['dry-run'])) {
			$count = $this->Users->updateAll(['email' => $email . ''], ['email !=' => $email]);
		} else {
			$count = $this->Users->find('all', ['conditions' => [CLASS_USERS . '.email !=' => $email]])->count();
		}
		$this->out($count . ' emails resetted - DONE');
	}

	/**
	 * Resets all pwds to a simple pwd (for local development).
	 *
	 * @param string|null $pwd
	 * @return void
	 */
	public function pwd($pwd = null) {
		$pwdToHash = null;
		if (!empty($pwd)) {
			$pwdToHash = $pwd;
		}
		while (empty($pwdToHash) || mb_strlen($pwdToHash) < 2) {
			$pwdToHash = $this->in(__('Password to Hash (2 characters at least)'));
		}
		$this->hr();
		$this->out('Password:');
		$this->out($pwdToHash);

		$hasher = 'Default';
		$hashType = Configure::read('Passwordable.passwordHasher');
		if ($hashType) {
			$hasher = $hashType;
		}
		$passwordHasher = PasswordHasherFactory::build($hasher);
		$pwd = $passwordHasher->hash($pwdToHash);
		if (!$pwd) {
			$this->abort('Hashing failed');
		}

		$this->hr();
		$this->out('Hash:');
		$this->out($pwd);

		$this->hr();
		$this->out('resetting...');

		/** @var \App\Model\Table\UsersTable $Users */
		$Users = TableRegistry::getTableLocator()->get(CLASS_USERS);
		$this->Users = $Users;
		if (!$this->Users->hasField('password')) {
			$this->abort(CLASS_USERS . ' table doesnt have a password field!');
		}

		if (empty($this->params['dry-run'])) {
			$count = $this->Users->updateAll(['password' => $pwd], ['password !=' => $pwd]);
		} else {
			$count = $this->Users->find('all', ['conditions' => [CLASS_USERS . '.password !=' => $pwd]])->count();
		}
		$this->out($count . ' pwds resetted - DONE');
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the reset command, no data will actually be modified.',
					'boolean' => true,
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('The Reset shell can reset local development data.
Note that you can define the constant CLASS_USERS in your bootstrap to point to another table class, if \'Users\' is not used.')
			->addSubcommand('email', [
				'help' => 'Reset all user emails.',
				'parser' => $subcommandParser,
			])
			->addSubcommand('pwd', [
				'help' => 'Hash and Reset all user passwords via Hasher class. If you are not using Default hasher, make sure
 you provide the correct one via Configure \'Passwordable.passwordHasher\'.',
				'parser' => $subcommandParser,
			]);
	}

}
