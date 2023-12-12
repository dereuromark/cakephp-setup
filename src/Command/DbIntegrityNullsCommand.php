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
		return 'Assert proper non null fields (having a default value, needed for MySQL > 5.6).';
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

		$io->out(count($tables) . ' tables/fields need updating.');
		if (!$args->getOption('dry-run')) {
			$continue = $io->askChoice('Continue?', ['y', 'n'], 'y');
			if ($continue !== 'y') {
				$io->abort('Aborted!');
			}
		}
		$sql = implode(PHP_EOL, $tables);
		if (!$args->getOption('dry-run')) {
			$io->out($sql);

			return;
		}

		//$db->execute($sql);
		$io->out('Done :)');
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

			if ($type === 'tinyinteger') {
				if ($null === true || $default !== null) {
					continue;
				}

				$fields[] = $field; // 'ALTER TABLE' . ' ' . $table['table_name'] . ' CHANGE `' . $name . '` `' . $name . '` ' . $type . ' NOT NULL DEFAULT \'0\';';
			}

			if (!in_array($type, ['longtext', 'mediumtext', 'text', 'char', 'string'], true)) {
				continue;
			}

			if ($null === true || $default !== null) {
				continue;
			}

			$fields[] = $field;
		}

		return $fields ? [$table => $fields] : [];
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'dry-run' => [
				'short' => 'd',
				'help' => 'Dry run the command, nothing will actually be modified. It will output the SQL to copy-and-paste, e.g. into a Migrations file.',
				'boolean' => true,
			],
			'table' => [
				'short' => 't',
				'help' => 'Specific table (separate multiple with comma).',
				'default' => '',
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addOptions($options);
	}

}
