<?php

namespace Setup\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\Filesystem\Folder;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use RuntimeException;

/**
 * Alerts about possible constraints missing in terms of data integrity issues.
 * - Optional relation with foreign key not being set back to null when related has* entity has removed been removed.
 *   This is only relevant if relation is not "dependent => true", though.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbConstraintsShell extends Shell {

	/**
	 * @param string|null $modelName
	 *
	 * @return void
	 */
	public function check($modelName = null) {
		$plugin = $this->param('plugin');
		$models = $this->_getModels($modelName, $plugin);

		$this->out('Checking ' . count($models) . ' models:', 1, static::VERBOSE);
		$tables = [];
		foreach ($models as $model) {
			try {
				$tables += $this->checkModel($model);
			} catch (CakeException $e) {
				$this->err('Skipping due to errors: ' . $e->getMessage());

				continue;
			}
		}

		if ($tables && $this->param('verbose')) {
			$this->out('');

			$result = [];
			foreach ($tables as $table => $fields) {
				$result[] = '$this->table(\'' . $table . '\')';

				foreach ($fields as $field => $relation) {
					$result[] = "\t" . '->addForeignKey(\'' . $field . '\', \'' . $relation['table'] . '\', [\'' . $relation['field'] . '\'], [\'delete\' => \'SET_NULL\'])';
				}

				$result[] = "\t" . '->update();';
			}

			$this->out($result);
		}

		$this->out('');
		$this->out('Done :) Possible nullable foreign key constraints checks executed.');
	}

	/**
	 * @param string|null $modelName
	 *
	 * @return void
	 */
	public function fix($modelName = null) {
		$plugin = $this->param('plugin');
		$models = $this->_getModels($modelName, $plugin);

		$this->out('Checking ' . count($models) . ' models:', 1, static::VERBOSE);

		foreach ($models as $model) {
			try {
				$this->fixModel($model);
			} catch (CakeException $e) {
				$this->err('Skipping due to errors: ' . $e->getMessage());

				continue;
			}
		}

		$this->out('');
		$this->out('Done :) Possible nullable foreign key constraints fixes executed.');
	}

	/**
	 * @param \Cake\ORM\Table $model
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function checkModel(Table $model): array {
		$table = $model->getTable();
		if (!$table) {
			return [];
		}

		$this->out('### ' . $table, 1, static::VERBOSE);

		$schema = $model->getSchema();

		$associations = $model->associations();
		$relationKeys = $associations->keys();
		if (!$relationKeys) {
			return [];
		}

		$fields = [];
		foreach ($relationKeys as $relationKey) {
			$relation = $associations->get($relationKey);
			if ($relation->type() !== $relation::MANY_TO_ONE) {
				continue;
			}

			$this->out('Checking: ' . $model->getAlias() . '.' . $relation->getForeignKey() . ' => ' . $relation->getName() . '.' . $relation->getBindingKey());
			$field = $schema->getColumn($relation->getForeignKey());
			if (!$field) {
				$this->warn(' - Cannot find column definition for `' . $relation->getForeignKey() . '`');
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
				if ($constraintDetails['type'] !== 'foreign') {
					continue;
				}
				if ($constraintDetails['columns'] !== [$relation->getForeignKey()]) {
					continue;
				}

				$ok = true;

				if (!empty($constraintDetails['delete']) && $constraintDetails['delete'] === 'setNull') {
					continue;
				}

				$this->warn('- Possibly missing a [\'delete\' => \'SET_NULL\'] constraint.');
			}

			if ($ok) {
				continue;
			}

			$this->warn('- Foreign key constraint missing: ' . $relation->getForeignKey());

			$fields[$model->getTable()][$relation->getForeignKey()] = [
				'table' => $relation->getTable(),
				'field' => $relation->getPrimaryKey(),
			];
		}

		return $fields;
	}

	/**
	 * @param \Cake\ORM\Table $model
	 *
	 * @return void
	 */
	protected function fixModel(Table $model): void {
		$table = $model->getTable();
		if (!$table) {
			return;
		}

		$this->out('### ' . $table, 1, static::VERBOSE);

		$schema = $model->getSchema();

		$associations = $model->associations();
		$relationKeys = $associations->keys();
		if (!$relationKeys) {
			return;
		}

		$fields = [];

		foreach ($relationKeys as $relationKey) {
			$relation = $associations->get($relationKey);
			if ($relation->type() !== $relation::MANY_TO_ONE) {
				continue;
			}

			$this->out('Checking: ' . $model->getAlias() . '.' . $relation->getForeignKey() . ' => ' . $relation->getName() . '.' . $relation->getBindingKey());
			$field = $schema->getColumn($relation->getForeignKey());
			if (!$field) {
				$this->warn(' - Cannot find column definition for `' . $relation->getForeignKey() . '`');
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
				if ($constraintDetails['type'] !== 'foreign') {
					continue;
				}
				if ($constraintDetails['columns'] !== [$relation->getForeignKey()]) {
					continue;
				}

				$ok = true;

				if (!empty($constraintDetails['delete']) && $constraintDetails['delete'] === 'setNull') {
					continue;
				}

				//$this->warn('- Possibly missing a [\'delete\' => \'SET_NULL\'] constraint.');
			}

			if ($ok) {
				continue;
			}

			$records = $model->find()
				->contain([$relation->getName()])
				->select([
					$model->getAlias() . '.' . $model->getPrimaryKey(),
					$model->getAlias() . '.' . $relation->getForeignKey(),
					$relation->getName() . '.' . $relation->getPrimaryKey(),
				])
				->where([$relation->getForeignKey() . ' IS NOT' => null])
				->all()
				->toArray();
			$property = $relation->getProperty();
			foreach ($records as $record) {
				if ($record->$property) {
					continue;
				}

				$this->err('Invalid non-null foreign key for ID ' . $record->id);
			}
		}
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				'plugin' => [
					'short' => 'p',
					'help' => 'Plugin',
				],
			],
			'arguments' => [
				'model' => [
					'help' => 'Specific model (table)',
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('A Shell to check database integrity issues regarding nullable foreign key columns.')
			->addSubcommand('check', [
				'help' => 'Correct nullable foreign key columns by adding missing on delete constraints.',
				'parser' => $subcommandParser,
			])
			->addSubcommand('fix', [
				'help' => 'Correct nullable foreign key columns by setting invalid ones back to NULL.',
				'parser' => $subcommandParser,
			]);
	}

	/**
	 * @param string|null $model
	 * @param string|null $plugin
	 *
	 * @throws \RuntimeException
	 *
	 * @return array<\Cake\ORM\Table>
	 */
	protected function _getModels($model, $plugin) {
		if ($model) {
			$className = App::className($plugin ? $plugin . '.' : $model, 'Model/Table', 'Table');
			if (!$className) {
				throw new RuntimeException('Model not found: ' . $model);
			}

			return [
				TableRegistry::getTableLocator()->get($plugin ? $plugin . '.' : $model),
			];
		}

		$folders = App::path('Model/Table', $plugin);

		$models = [];
		foreach ($folders as $folder) {
			$folderContent = (new Folder($folder))->read(Folder::SORT_NAME, true);

			foreach ($folderContent[1] as $file) {
				$name = pathinfo($file, PATHINFO_FILENAME);

				preg_match('#^(.+)Table$#', $name, $matches);
				if (!$matches) {
					continue;
				}

				$model = $matches[1];

				$className = App::className($plugin ? $plugin . '.' . $model : $model, 'Model/Table', 'Table');
				if (!$className) {
					continue;
				}

				$models[] = TableRegistry::getTableLocator()->get($plugin ? $plugin . '.' . $model : $model);
			}
		}

		return $models;
	}

}
