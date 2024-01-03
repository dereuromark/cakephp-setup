<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Exception\CakeException;
use Cake\ORM\Table;
use Cake\View\View;
use Migrations\View\Helper\MigrationHelper;
use Setup\Command\Traits\DbToolsTrait;

/**
 * A command to ease database migrations needed
 * - Convert null fields without a default value
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbIntegrityNullsCommand extends Command {

	use DbToolsTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Assert proper non null fields (having a default value, needed for MySQL > 5.6 if you are not providing it yourself on create).';
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

		if (!$tables) {
			$io->out('Nothing to do :)');

			return;
		}

		$io->out();
		$io->out(count($tables) . ' tables/fields could potentially need updating.');

		if (!$args->getOption('verbose')) {
			$io->out('');
			$io->info('Tip: Use verbose mode to have a ready-to-use migration file content generated for you.');

			return;
		}

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
				]);
				$options = [
					'indent' => 2,
				];
				$attributes = (new MigrationHelper(new View()))->stringifyList($data, $options, $wantedOptions);

				$result[] = str_repeat(' ', 4) . '->changeColumn(\'' . $field . '\', \'' . $type . '\', ['
					. $attributes
					. '])';
			}

			$result[] = str_repeat(' ', 4) . '->update();';
		}

		$io->out($result);
	}

	/**
	 * @param \Cake\ORM\Table $model
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return array
	 */
	protected function checkModel(Table $model, ConsoleIo $io): array {
		$table = $model->getTable();
		if (!$table) {
			return [];
		}

		$io->out('### ' . $table, 1, ConsoleIo::VERBOSE);

		/** @var \Cake\Database\Schema\TableSchema $schema */
		$schema = $model->getSchema();

		$fields = $schema->columns();

		$associations = $model->associations();
		$relationKeys = $associations->keys();
		$primaryKey = (array)$model->getPrimaryKey();

		$excludedFields = $primaryKey;
		foreach ($relationKeys as $relationKey) {
			$relation = $associations->get($relationKey);
			if (!$relation) {
				continue;
			}

			/** @var string|false $foreignKey */
			$foreignKey = $relation->getForeignKey();
			if ($foreignKey) {
				$excludedFields[] = $foreignKey;
			}

			/** @var string $bindingKey */
			$bindingKey = $relation->getBindingKey();
			if ($bindingKey) {
				$excludedFields[] = $bindingKey;
			}
		}

		$fieldsToCheck = array_diff_key($fields, $excludedFields);

		$fields = [];
		foreach ($fieldsToCheck as $field) {
			$fieldSchema = $schema->getColumn($field);
			if (!$fieldSchema) {
				continue;
			}

			$type = $fieldSchema['type'];
			$null = $fieldSchema['null'];
			$default = $fieldSchema['default'];

			if ($type === 'boolean') {
				if ($default !== null) {
					continue;
				}

				$fieldSchema['null'] = false;
				$fieldSchema['default'] = '0';

				$fields[$field] = $fieldSchema;
			}

			if ($type === 'tinyinteger') {
				if ($null === true || $default !== null) {
					continue;
				}

				$fieldSchema['default'] = '0';

				$fields[$field] = $fieldSchema; // 'ALTER TABLE' . ' ' . $table['table_name'] . ' CHANGE `' . $name . '` `' . $name . '` ' . $type . ' NOT NULL DEFAULT \'0\';';
			}

			if (!in_array($type, ['longtext', 'mediumtext', 'text', 'char', 'string'], true)) {
				continue;
			}

			if ($null === true || $default !== null) {
				continue;
			}

			$fieldSchema['default'] = '';

			$fields[$field] = $fieldSchema;
		}

		foreach ($fields as $field => $fieldSchema) {
			$io->out('- ' . $field, 1, ConsoleIo::VERBOSE);
		}

		return $fields ? [$table => $fields] : [];
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
