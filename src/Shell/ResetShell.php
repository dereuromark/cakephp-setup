<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Core\Plugin;
use Cake\Core\Configure;
use Cake\Validation\Validation;
use Cake\ORM\TableRegistry;
use Cake\Auth\PasswordHasherFactory;

if (!defined('CLASS_USERS')) {
	define('CLASS_USERS', 'Users');
}

/**
 * Reset user data
 *
 * @author Mark Scherer
 * @license MIT
 */
class ResetShell extends Shell {

	public $Auth = null;

	/**
	 * Resets all emails - e.g. to your admin email (for local development).
	 *
	 * @return void
	 */
	public function email($email = null) {
		$this->out('Email:');
		while (empty($email) || !Validation::email($email)) {
			$email = $this->in('New email address (must have a valid form at least)');
		}

		$this->Users = TableRegistry::get(CLASS_USERS);
		if (!$this->Users->hasField('email')) {
			return $this->error(CLASS_USERS . ' table doesnt have an email field!');
		}

		$this->hr();
		$this->out('Resetting...');

		if (empty($this->params['dry-run'])) {
			$count = $this->Users->updateAll(array('email' => $email . ''), array('email !=' => $email));
		} else {
			$count = $this->Users->find('all', ['conditions' => [CLASS_USERS . '.email !=' => $email]])->count();
		}
		$this->out($count . ' emails resetted - DONE');
	}

	/**
	 * Resets all pwds to a simple pwd (for local development).
	 *
	 * @return void
	 */
	public function pwd($pwd = null) {
		if (!empty($pwd)) {
			$pwToHash = $pwd;
		}
		while (empty($pwToHash) || mb_strlen($pwToHash) < 2) {
			$pwToHash = $this->in(__('Password to Hash (2 characters at least)'));
		}
		$this->hr();
		$this->out('Password:');
		$this->out($pwToHash);

		$hasher = 'Default';
		if ($hashType = Configure::read('Passwordable.passwordHasher')) {
			$hasher = $hashType;
		}
		$passwordHasher = PasswordHasherFactory::build($hasher);
		$pw = $passwordHasher->hash($pwToHash);

		$this->hr();
		$this->out('Hash:');
		$this->out($pw);

		$this->hr();
		$this->out('resetting...');

		$this->Users = TableRegistry::get(CLASS_USERS);
		if (!$this->Users->hasField('password')) {
			return $this->error(CLASS_USERS . ' table doesnt have a password field!');
		}

		$newPwd = $pw;

		if (empty($this->params['dry-run'])) {
			$count = $this->Users->updateAll(array('password' => $newPwd), array('password !=' => $pw));
		} else {
			$count = $this->Users->find('all', ['conditions' => [CLASS_USERS . '.password !=' => $pw]])->count();
		}
		$this->out($count . ' pwds resetted - DONE');
	}

	/**
	 * ResetShell::help()
	 *
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'dry-run' => array(
					'short' => 'd',
					'help' => 'Dry run the reset command, no data will actually be modified.',
					'boolean' => true
				),
			)
		);

		return parent::getOptionParser()
			->description('The Reset shell can reset local development data.
Note that you can define the constant CLASS_USERS in your bootstrap to point to another table class, if \'Users\' is not used.')
			->addSubcommand('email', array(
				'help' => 'Reset all user emails.',
				'parser' => $subcommandParser
			))
			->addSubcommand('pwd', array(
				'help' => 'Hash and Reset all user passwords via Hasher class. If you are not using Default hasher, make sure
 you provide the correct one via Configure \'Passwordable.passwordHasher\'.',
				'parser' => $subcommandParser
			));
	}

}
