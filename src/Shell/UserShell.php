<?php
namespace Setup\Shell;

use Cake\Console\Shell;

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

	public $uses = array(CLASS_USERS);

	/**
	 * UserShell::create()
	 * //TODO: refactor (smaller sub-parts)
	 *
	 * @param string|null $username
	 * @param string|null $password
	 * @return void
	 */
	public function create($username = null, $password = null) {
		while (empty($username)) {
			$username = $this->in('Username');
		}
		while (empty($password)) {
			$password = $this->in('Password');
		}

		$this->loadModel(CLASS_USERS);
		$schema = $this->Users->schema();

		$entity = $this->Users->newEntity();

		//TODO
		/*
		if (isset($this->User->Role) && is_object($this->User->Role)) {
			$roles = $this->User->Role->find('list');

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
		$this->Users->addBehavior('Tools.Passwordable', array('confirm' => false));
		//$this->User->validate['pwd']
		$data = array(
			'pwd' => $password,
			'active' => 1
		);

		$usernameField = $this->Users->displayField();
		if ($usernameField === $this->Users->primaryKey()) {
			return $this->error('Cannot read a displayField from the Users table. You need to define one, e.g. "username".');
		}
		$data[$usernameField] = $username;

		if (!empty($email)) {
			$data['email'] = $email;
		}
		if (!empty($role)) {
			$data['role_id'] = $role;
		}

		if ($schema->column('status') && method_exists(CLASS_USER, 'statuses')) {
			$statuses = CLASS_USER::statuses();
			$this->out(print_r($statuses, true));
			$status = $this->in('Please insert a status', array_keys($statuses));

			$data['status'] = $status;
		}

		if ($schema->column('email') && $usernameField !== 'email') {
			$provideEmail = $this->in('Provide Email?', array('y', 'n'), 'n');
			if ($provideEmail === 'y') {
				$email = $this->in('Please insert an email');
				$data['email'] = $email;
			}
			if ($schema->column('email_confirmed')) {
				$data['email_confirmed'] = 1;
			}
		}

		$this->out('');
		$continue = $this->in('Continue?', array('y', 'n'), 'n');
		if ($continue !== 'y') {
			return $this->error('Aborted!');
		}

		$this->out('');
		$this->hr();
		$entity = $this->Users->newEntity($data, ['validate' => false]);
		if (!empty($this->params['dry-run'])) {
			$this->out('User dry-run inserted! Data: ' . print_r($entity->toArray(), true));
			return;
		}
		if (!$this->Users->save($entity, ['checkRules' => false])) {
			return $this->error('User could not be inserted (' . print_r($entity->errors(), true) . ')');
		}

		$this->out('User inserted! ID: ' . $entity['id'] . 'Data: ' . print_r($entity->toArray(), true));
	}

	/**
	 * UserShell
	 *
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'dry-run' => array(
					'short' => 'd',
					'help' => 'Dry run the create command, no data will actually be inserted.',
					'boolean' => true
				),
			)
		);

		return parent::getOptionParser()
			->description('The User shell can create a user on the fly for local development.
Note that you can define the constant CLASS_USERS in your bootstrap to point to another table class, if \'Users\' is not used.
Make sure you configured the Passwordable behavior accordingly as per docs.')
			->addSubcommand('create', array(
				'help' => 'Create a new user with email and password provided.',
				'parser' => $subcommandParser
			));
	}

}
