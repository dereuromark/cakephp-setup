<?php
namespace Setup\Shell;

use ArrayObject;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Event\Event;
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
 * @property \App\Model\Table\UsersTable $Users
 */
class UserShell extends Shell {

	/**
	 * Lists users
	 *
	 * @param string|null $role Role ID
	 * @return void
	 */
	public function index($role = null) {
		$this->loadModel(CLASS_USERS);

		$query = $this->Users->find()
			->where([$this->Users->getDisplayField() . ' IS NOT' => null]);
		if ($this->param('search')) {
			$query = $query->where([$this->Users->getDisplayField() . ' LIKE' => '%' . $this->param('search') . '%']);
		}

		if ($role && $this->Users->getSchema()->getColumn('role_id')) {
			$query = $query->where(['role_id' => (int)$role]);
		}

		/** @var \App\Model\Entity\User[] $users */
		$users = $query->orderDesc($this->Users->getPrimaryKey())->limit(100)->all()->toArray();

		$count = count($users);
		if ($count < 1) {
			$this->err($count . ' users found');
		} else {
			$this->success($count . ' users found');
		}

		$displayField = $this->Users->getDisplayField();
		$emailField = null;
		if ($this->Users->getSchema()->getColumn('email') && $displayField !== 'email') {
			$emailField = 'email';
		}

		foreach ($users as $user) {
			$this->out('* ' . $user->get($displayField) . ($emailField ? ' (' . $user->get($emailField) . ')' : ''));
			if ($this->param('verbose')) {
				$this->out($user);
			}
		}
		if ($count > 200) {
			$this->warn('(Only lists 200 users)');
		}
	}

	/**
	 * Creates a new user including a freshly hashed password.
	 *
	 * @param string|null $displayFieldValue
	 * @param string|null $password
	 * @return void
	 */
	public function create($displayFieldValue = null, $password = null) {
		$this->loadModel(CLASS_USERS);
		$schema = $this->Users->getSchema();

		$displayField = $this->Users->getDisplayField();
		$displayFieldName = Inflector::humanize($displayField);

		while (empty($displayFieldValue)) {
			$displayFieldValue = $this->in($displayFieldName);
		}
		while (empty($password)) {
			$password = $this->in('Password');
		}

		if ($schema->getColumn('role_id')) {
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
			*/

			$roles = (array)Configure::read('Roles');
			$roleIds = array_values($roles);

			$this->out(print_r($roles, true));
			while (!empty($roles) && empty($role)) {
				$role = $this->in('Role', $roleIds);
			}

			if (empty($roles)) {
				$this->out('No Role found (either no table, or no data)');
				$role = $this->in('Please insert a role id manually');
			}
		}

		$this->out('');
		$this->Users->addBehavior('Tools.Passwordable', ['confirm' => false]);

		$data = [
			'pwd' => $password,
			'active' => 1,
		];

		if ($displayField === $this->Users->getPrimaryKey()) {
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
		if ($schema->getColumn('status') && method_exists($userEntity, 'statuses')) {
			$statuses = $userEntity::statuses();
			$this->out(print_r($statuses, true));
			$status = $this->in('Please insert a status', array_keys($statuses));

			$data['status'] = $status;
		}

		if ($schema->getColumn('email') && $displayField !== 'email') {
			$provideEmail = $this->in('Provide Email?', ['y', 'n'], 'n');
			if ($provideEmail === 'y') {
				$email = $this->in('Please insert an email');
				$data['email'] = $email;
			}
			if ($schema->getColumn('email_confirmed')) {
				$data['email_confirmed'] = 1;
			}
		}

		if (!$this->param('dry-run')) {
			$this->out('');
			$continue = $this->in('Continue?', ['y', 'n'], 'n');
			if ($continue !== 'y') {
				$this->abort('Aborted!');
			}
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

		$this->out('User inserted! ID: ' . $entity['id']);
		$this->out('Data: ' . print_r($entity->toArray(), true), 1, Shell::VERBOSE);
	}

	/**
	 * Updates existing user with a freshly hashed password.
	 *
	 * @param string|null $displayFieldValue
	 * @param string|null $password
	 * @return void
	 */
	public function update($displayFieldValue = null, $password = null) {
		$this->loadModel(CLASS_USERS);
		$schema = $this->Users->getSchema();

		$displayField = $this->Users->getDisplayField();
		$displayFieldName = Inflector::humanize($displayField);

		while (empty($displayFieldValue)) {
			$displayFieldValue = $this->in($displayFieldName);
		}

		$user = $this->Users->find()->where([$displayField => $displayFieldValue])->firstOrFail();

		while (empty($password)) {
			$password = $this->in('Password');
		}

		$this->Users->addBehavior('Tools.Passwordable', ['confirm' => false]);
		$this->Users->patchEntity($user, ['pwd' => $password]);

		$this->Users->saveOrFail($user);

		$this->success('Password updated for user ' . $displayFieldValue);
	}

	/**
	 * Creates a new user including a freshly hashed password.
	 *
	 * @param string|null $password
	 * @return void
	 */
	public function password($password = null) {
		while (empty($password)) {
			$password = $this->in('Password');
		}

		$this->loadModel(CLASS_USERS);
		$this->Users->addBehavior('Tools.Passwordable', ['confirm' => false]);

		/** @var \App\Model\Entity\User $entity */
		$entity = $this->Users->newEntity([
			'pwd' => $password,
		], ['validate' => false]);
		/** @var \Tools\Model\Behavior\PasswordableBehavior $Passwordable */
		$Passwordable = $this->Users->behaviors()->get('Passwordable');
		$Passwordable->beforeSave(new Event('beforeSave'), $entity, new ArrayObject());

		$this->out('Generating hash...');
		$this->hr();
		$this->out($entity->password);
		$this->hr();
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the create command, no data will actually be inserted.',
					'boolean' => true,
				],
			],
		];

		$createParser = $subcommandParser;
		$createParser['arguments'] = [
			'login' => [
				'help' => 'Display field value',
				'required' => false,
			],
			'password' => [
				'help' => 'Password',
				'required' => false,
			],
		];

		$listParser = [
			'options' => [
				'search' => [
					'short' => 's',
					'help' => 'Search in the display field.',
					'default' => '',
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('The User shell can create a user on the fly for local development.
Note that you can define the constant CLASS_USERS in your bootstrap to point to another table class, if \'Users\' is not used.
Make sure you configured the Passwordable behavior accordingly as per docs.')
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
			]);
	}

}
