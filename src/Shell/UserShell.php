<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Utility\Inflector;

if (!defined('CLASS_USERS')) {
	define('CLASS_USERS', 'Users');
}
if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'App\Model\Entity\User');
}

/**
 * Create a new user from CLI.
 *
 * @author Mark Scherer
 * @license MIT
 */
class UserShell extends Shell {

	public $uses = [CLASS_USERS];

	/**
	 * Creates a new user including a freshly hashed password.
	 *
	 * @param string|null $displayFieldValue
	 * @param string|null $password
	 * @return void
	 */
	public function create($displayFieldValue = null, $password = null) {
		$this->loadModel(CLASS_USERS);
		$schema = $this->Users->schema();

		$displayField = $this->Users->displayField();
		$displayFieldName = Inflector::humanize($displayField);

		while (empty($displayFieldValue)) {
			$displayFieldValue = $this->in($displayFieldName);
		}
		while (empty($password)) {
			$password = $this->in('Password');
		}

		//$entity = $this->Users->newEntity();
		//TODO
		/*
		if (isset($this->Users->Roles) && is_object($this->Users->Roles)) {
			$roles = $this->Users->Roles->find('list');

			if (!empty($roles)) {
				$this->out('');
				$this->out(print_r($roles, true));
			}

			$roleIds = array_keys($roles);
			while (!empty($roles) && empty($role)) {
				$role = $this->in('Role', $roleIds);
			}
		} elseif (method_exists($this->User, 'roles')) {
			$roles = User::roles();

			if (!empty($roles)) {
				$this->out('');
				$this->out(print_r($roles, true));
			}

			$roleIds = array_keys($roles);
			while (!empty($roles) && empty($role)) {
				$role = $this->in('Role', $roleIds);
			}
		}
		if (empty($roles)) {
			$this->out('No Role found (either no table, or no data)');
			$role = $this->in('Please insert a role manually');
		}
		*/

		$this->out('');
		$this->Users->addBehavior('Tools.Passwordable', ['confirm' => false]);

		$data = [
			'pwd' => $password,
			'active' => 1
		];

		if ($displayField === $this->Users->primaryKey()) {
			$this->abort('Cannot read a displayField from the Users table. You need to define one, e.g. "username".');
		}
		$data[$displayField] = $displayFieldValue;

		if (!empty($email)) {
			$data['email'] = $email;
		}
		if (!empty($role)) {
			$data['role_id'] = $role;
		}

		$userEntity = CLASS_USER;
		if ($schema->column('status') && method_exists($userEntity, 'statuses')) {
			$statuses = $userEntity::statuses();
			$this->out(print_r($statuses, true));
			$status = $this->in('Please insert a status', array_keys($statuses));

			$data['status'] = $status;
		}

		if ($schema->column('email') && $displayField !== 'email') {
			$provideEmail = $this->in('Provide Email?', ['y', 'n'], 'n');
			if ($provideEmail === 'y') {
				$email = $this->in('Please insert an email');
				$data['email'] = $email;
			}
			if ($schema->column('email_confirmed')) {
				$data['email_confirmed'] = 1;
			}
		}

		$this->out('');
		$continue = $this->in('Continue?', ['y', 'n'], 'n');
		if ($continue !== 'y') {
			$this->abort('Aborted!');
		}

		$this->out('');
		$this->hr();
		$entity = $this->Users->newEntity($data, ['validate' => false]);
		if (!empty($this->params['dry-run'])) {
			$this->out('User dry-run inserted! Data: ' . print_r($entity->toArray(), true));
			return;
		}
		if (!$this->Users->save($entity, ['checkRules' => false])) {
			$this->abort('User could not be inserted (' . print_r($entity->errors(), true) . ')');
		}

		$this->out('User inserted! ID: ' . $entity['id'] . 'Data: ' . print_r($entity->toArray(), true));
	}

	/**
	 * UserShell
	 *
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the create command, no data will actually be inserted.',
					'boolean' => true
				],
			]
		];

		return parent::getOptionParser()
			->description('The User shell can create a user on the fly for local development.
Note that you can define the constant CLASS_USERS in your bootstrap to point to another table class, if \'Users\' is not used.
Make sure you configured the Passwordable behavior accordingly as per docs.')
			->addSubcommand('create', [
				'help' => 'Create a new user with email and password provided.',
				'parser' => $subcommandParser
			]);
	}

}
