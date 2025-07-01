<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Exception\CakeException;
use Cake\ORM\Table;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Alerts about possible constraints missing in terms of data integrity issues.
 * - Optional relation with foreign key not being set back to null when related has* entity has removed been removed.
 *   This is only relevant if relation is not "dependent => true", though.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbIntegrityConstraintsCommand extends Command {

	use DbToolsTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Check database integrity issues regarding nullable foreign key columns and correct them by adding missing on delete constraints.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$modelName = $args->getArgument('model');

		$plugin = (string)$args->getOption('plugin') ?: null;
		$models = $this->_getModels($modelName, $plugin);

		$io->out('Checking ' . count($models) . ' models:', 1, ConsoleIo::VERBOSE);
		$tables = [];
		foreach ($models as $model) {
			try {
				$tables += $this->checkModel($model, $io);
			} catch (CakeException $e) {
				$io->error('Skipping due to errors: ' . $e->getMessage());

				continue;
			}
		}

		$io->out();
		if ($tables) {
			$io->warning(count($tables) . ' tables found with possible missing constraints.');
		} else {
			$io->success('Done :) No possible nullable foreign key constraints found.');
		}

		if ($tables && !$args->getOption('verbose')) {
			$io->out();
			$io->info('Tip: Use verbose mode to have a ready-to-use migration file content generated for you.');
		}

		if ($tables && $args->getOption('verbose')) {
			$io->out();
			$io->out('Add the following as migration to your config:');
			$io->out();

			$result = [];
			foreach ($tables as $table => $fields) {
				$result[] = '$this->table(\'' . $table . '\')';

				foreach ($fields as $field => $relation) {
					$result[] = "\t" . '->addForeignKey(\'' . $field . '\', \'' . $relation['table'] . '\', [\'' . $relation['field'] . '\'], [\'delete\' => \'SET_NULL\'])';
				}

				$result[] = "\t" . '->update();';
			}

			$io->out($result);
		}
	}

	/**
	 * @param \Cake\ORM\Table $model
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function checkModel(Table $model, ConsoleIo $io): array {
		$table = $model->getTable();
		if (!$table) {
			return [];
		}

		$io->out('### ' . $table, 1, ConsoleIo::VERBOSE);

		/** @var \Cake\Database\Schema\TableSchema $schema */
		$schema = $model->getSchema();

		$associations = $model->associations();
		$relationKeys = $associations->keys();
		if (!$relationKeys) {
			return [];
		}

		$fields = [];
		foreach ($relationKeys as $relationKey) {
			$relation = $associations->get($relationKey);
			if (!$relation || $relation->type() !== $relation::MANY_TO_ONE) {
				continue;
			}
			$foreignKey = $relation->getForeignKey();
			if (!$foreignKey) {
				continue;
			}
			assert(is_string($foreignKey));

			$bindingKey = $relation->getBindingKey();
			if (!$bindingKey) {
				continue;
			}
			assert(is_string($bindingKey));

			$io->out('Checking: ' . $model->getAlias() . '.' . $foreignKey . ' => ' . $relation->getName() . '.' . $bindingKey);
			$field = $schema->getColumn($foreignKey);
			if (!$field) {
				$io->warning(' - Cannot find column definition for `' . $foreignKey . '`');
			}
			if ($field && ($field['null'] !== true || $field['default'] !== null)) {
				continue;
			}
			// We only care about AIIDs for now
			if ($field && $field['type'] !== 'integer') {
				continue;
			}

			$ok = false;
			$constraints = $schema->constraints();
			foreach ($constraints as $constraint) {
				$constraintDetails = $schema->getConstraint($constraint);
				assert($constraintDetails !== null);
				if ($constraintDetails['type'] !== 'foreign') {
					continue;
				}
				if ($constraintDetails['columns'] !== [$foreignKey]) {
					continue;
				}

				$ok = true;

				if (!empty($constraintDetails['delete']) && $constraintDetails['delete'] === 'setNull') {
					continue;
				}

				$io->warning('- Possibly missing a [\'delete\' => \'SET_NULL\'] constraint.');
			}

			if ($ok) {
				continue;
			}

			$io->warning('- Foreign key constraint missing: ' . $foreignKey);

			$fields[$model->getTable()][$foreignKey] = [
				'table' => $relation->getTable(),
				'field' => $relation->getPrimaryKey(),
			];
		}

		return $fields;
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'plugin' => [
				'short' => 'p',
				'help' => 'Plugin',
			],
			/*
			'fix' => [
				'short' => 'f',
				'help' => 'Fix instead of just outputting migration content',
			],
			*/
		];
		$arguments = [
			'model' => [
				'help' => 'Specific model (table)',
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addOptions($options)
			->addArguments($arguments);
	}

}
