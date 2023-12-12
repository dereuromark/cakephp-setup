<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\View\View;
use Migrations\View\Helper\MigrationHelper;
use RuntimeException;
use Shim\Filesystem\Folder;

/**
 * Can provide fixing for missing unsigned (foreign) integer keys.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbUnsignedCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Check database integrity issues regarding missing unsigned primary and foreign key columns and correct them if necessary. Required for constraints to be applied.';
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
				$io->err('Skipping due to errors: ' . $e->getMessage());

				continue;
			}
		}

		$io->out('');
		if ($tables) {
			$io->warning(count($tables) . ' tables found with possible unsigned issues.');
			foreach ($tables as $table => $fields) {
				$io->out(' - ' . $table . ':');
				foreach ($fields as $field => $config) {
					$io->out('   * ' . $field);
				}
			}

		} else {
			$io->success('Done :) No unsigned issues around (foreign) keys found.');
		}

		if ($tables && !$args->getOption('verbose')) {
			$io->out('');
			$io->info('Tip: Use verbose mode to have a ready-to-use migration file content generated for you.');
		}

		if ($tables && $args->getOption('verbose')) {
			$io->out('');
			$io->out('Add the following as migration to your config:');
			$io->out('');

			$result = [];
			foreach ($tables as $table => $fields) {
				$result[] = '$this->table(\'' . $table . '\')';

				foreach ($fields as $field => $data) {
					$data['signed'] = false;
					$type = $data['type'];
					unset($data['type']);
					if (empty($data['autoIncrement'])) {
						unset($data['autoIncrement']);
					}

					$wantedOptions = array_flip([
						'default',
						'signed',
						'null',
						'comment',
						'autoIncrement',
						//'length',
						//'limit',
						//'precision',
						//'after',
						//'collate',
					]);
					$options = [
						'indent' => 3,
					];
					$attributes = (new MigrationHelper(new View()))->stringifyList($data, $options, $wantedOptions);

					$result[] = "\t" . '->changeColumn(\'' . $field . '\', \'' . $type . '\', ['
						. $attributes
						. '])';
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

		$fields = [];

		$key = $model->getPrimaryKey();
		if (is_array($key)) {
			$io->warning('Skipping ' . $table . ' - multi-primary key not yet supported');
		} else {
			$field = $schema->getColumn($key);
			if ($field && $this->requiresUpdate($field)) {
				$field['null'] = false;
				$fields['id'] = $field;
			}
		}

		if (!$relationKeys) {
			return $fields ? [$model->getTable() => $fields] : [];
		}

		foreach ($relationKeys as $relationKey) {
			$relation = $associations->get($relationKey);
			if (!$relation) {
				continue;
			}

			/** @var string $foreignKey */
			$foreignKey = $relation->getForeignKey();
			$field = $foreignKey ? $schema->getColumn($foreignKey) : [];
			if ($field && !array_key_exists($foreignKey, $fields) && $this->requiresUpdate($field)) {
				$fields[$foreignKey] = $field;
			}

			/** @var string $bindingKey */
			$bindingKey = $relation->getBindingKey();
			$field = $bindingKey ? $schema->getColumn($bindingKey) : [];
			if ($field && !array_key_exists($bindingKey, $fields) && $this->requiresUpdate($field)) {
				$fields[$bindingKey] = $field;
			}
		}

		return $fields ? [$model->getTable() => $fields] : [];
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
				'boolean' => true,
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

	/**
	 * @param string|null $model
	 * @param string|null $plugin
	 *
	 * @throws \RuntimeException
	 *
	 * @return array<\Cake\ORM\Table>
	 */
	protected function _getModels(?string $model, ?string $plugin): array {
		if ($model) {
			$className = App::className($plugin ? $plugin . '.' : $model, 'Model/Table', 'Table');
			if (!$className) {
				throw new RuntimeException('Model not found: ' . $model);
			}

			return [
				TableRegistry::getTableLocator()->get($plugin ? $plugin . '.' : $model),
			];
		}

		$folders = App::classPath('Model/Table', $plugin);

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

	/**
	 * @param array<string, mixed> $field
	 *
	 * @return bool
	 */
	protected function requiresUpdate(array $field): bool {
		// We only care about AIIDs for now
		if ($field['type'] !== 'integer') {
			return false;
		}
		if ($field['unsigned'] === true) {
			return false;
		}

		return true;
	}

}
