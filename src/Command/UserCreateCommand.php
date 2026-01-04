<?php

namespace Setup\Command;

use App\Model\Entity\User;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ModelAwareTrait;
use Cake\Utility\Inflector;
use Closure;

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
class UserCreateCommand extends Command {

	use ModelAwareTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Create a new user from CLI.';
	}

	/**
	 * Creates a new user including a freshly hashed password.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$displayFieldValue = $args->getArgument('login');
		$password = $args->getArgument('password');

		/** @var \App\Model\Table\UsersTable $Users */
		$Users = $this->fetchModel(CLASS_USERS);
		$schema = $Users->getSchema();

		$displayField = $Users->getDisplayField();
		if (!is_string($displayField)) {
			$io->abort('Only supported for single display fields');
		}
		$displayFieldName = Inflector::humanize($displayField);

		while (empty($displayFieldValue)) {
			$displayFieldValue = $io->ask($displayFieldName);
		}
		while (empty($password)) {
			$password = $io->ask('Password');
		}

		$roleField = $this->findRoleField($schema);
		if ($roleField) {
			if (isset($Users->Roles) && is_object($Users->Roles)) {
				/** @var \Cake\ORM\Table $rolesTable */
				$rolesTable = $Users->Roles;
				$roles = $rolesTable->find('list')->toArray();

				if ($roles) {
					$io->out();
					$io->out(print_r($roles, true));
				}

				$roleIds = array_keys($roles);
				while ($roles && empty($role)) {
					$role = $io->askChoice('Role', $roleIds);
				}
			} elseif (class_exists(User::class) && method_exists(User::class, 'roles')) {
				$roles = User::roles();

				if ($roles) {
					$io->out();
					$io->out(print_r($roles, true));
				}

				/** @var array<string> $roleIds */
				$roleIds = array_keys($roles);
				while (!empty($roles) && empty($role)) {
					$role = $io->askChoice('Role', $roleIds);
				}
			} else {
				$roles = (array)Configure::read('Roles');
				$roleIds = array_values($roles);

				$io->out(print_r($roles, true));
				while ($roles && empty($role)) {
					$role = $io->askChoice('Role', $roleIds);
				}
			}

			if (!$roles) {
				$io->out('No Role found (either no table, or no data)');
				$role = $io->ask('Please insert a role id manually');
			}
		}

		$io->out();
		$Users->addBehavior('Tools.Passwordable', ['confirm' => false]);

		$data = [
			'pwd' => $password,
		];
		if ($Users->getSchema()->getColumn('active')) {
			$data['active'] = 1;
		}

		if ($displayField === $Users->getPrimaryKey()) {
			$io->abort('Cannot read a displayField from the Users table. You need to define one, e.g. "username".');
		}
		$data[$displayField] = $displayFieldValue;

		if (!empty($role)) {
			$data[$roleField] = $role;
		}

		$userEntity = CLASS_USER;
		if ($schema->getColumn('status') && method_exists($userEntity, 'statuses')) {
			/** @var array<string, string> $statuses */
			$statuses = $userEntity::statuses();
			$io->out(print_r($statuses, true));
			$status = $io->askChoice('Please insert a status', array_keys($statuses));

			$data['status'] = $status;
		}

		if ($schema->getColumn('email') && $displayField !== 'email') {
			$emailSchema = $schema->getColumn('email');
			$nullAllowed = $emailSchema['null'] ?? null;
			$provideEmail = $nullAllowed === false ? 'y' : $io->askChoice('Provide Email?', ['y', 'n'], 'n');
			if ($provideEmail === 'y') {
				$email = $io->ask('Please insert an email');
				$data['email'] = $email;
			}
			if ($schema->getColumn('email_confirmed')) {
				$data['email_confirmed'] = 1;
			}
		}

		if (!$args->getOption('dry-run')) {
			$io->out();
			$continue = $io->askChoice('Continue?', ['y', 'n'], 'n');
			if ($continue !== 'y') {
				$io->abort('Aborted!');
			}
		}

		$schemaColumns = $schema->columns();
		foreach ($schemaColumns as $schemaColumn) {
			$definition = $schema->getColumn($schemaColumn);
			if (!$definition) {
				continue;
			}
			if ($definition['null'] || !empty($definition['autoIncrement']) || isset($data[$schemaColumn])) {
				continue;
			}
			if (in_array($definition['type'], ['date', 'datetime', 'time', 'timestamp'], true)) {
				// skip these, as they are not required
				continue;
			}

			$data[$schemaColumn] = $definition['default'] ?? '';
		}

		$io->out();
		$io->hr();
		/** @var \App\Model\Entity\User $user */
		$user = $Users->newEntity($data, ['validate' => false]);
		$callable = Configure::read('UserCreate.callable');
		if ($callable instanceof Closure) {
			$user = $callable($user);
		}

		if ($args->getOption('dry-run')) {
			$io->out('User dry-run inserted! Data: ' . print_r($user->toArray(), true));

			return;
		}

		if (!$Users->save($user, ['checkRules' => false])) {
			$io->abort('User could not be inserted (' . print_r($user->getErrors(), true) . ')');
		}

		$io->out('User inserted! ID: ' . $user['id']);
		$io->out('Data: ' . print_r($user->toArray(), true), 1, ConsoleIo::VERBOSE);
		$io->out('Pwd Hash: ' . $user->password);
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
				'help' => 'Dry run the command, no data will actually be created.',
				'boolean' => true,
			]);
	}

	/**
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 * @return string|null
	 */
	protected function findRoleField(TableSchemaInterface $schema): ?string {
		if ($schema->getColumn('role_id')) {
			return 'role_id';
		}
		if ($schema->getColumn('role')) {
			return 'role';
		}

		return null;
	}

}
