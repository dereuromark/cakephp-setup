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
use Setup\Command\Traits\DbToolsTrait;
use Shim\Filesystem\Folder;

/**
 * Can provide fixing for Mysql8+ and int lengths.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbIntegrityIntsCommand extends Command {

	use DbToolsTrait;

	/**
	 * @var \Cake\Console\Arguments
	 */
	protected $args;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Check database integrity issues regarding Mysql 5 to 8 upgrade and `*int` field lengths.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$this->args = $args;

		$modelName = $args->getArgument('model');

		$plugin = (string)$args->getOption('plugin') ?: null;
		$models = $this->_getModels($modelName, $plugin);

		$version = $this->getSqlVersion();
		$io->out('MySql version: ' . $version);
		$io->out('This script is only for Mysql < 8, to ensure the meta data is set.');
		$io->out();

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

		$io->out();
		if ($tables) {
			$io->warning(count($tables) . ' tables found with possible unsigned issues.');
			foreach ($tables as $table => $fields) {
				$io->out(' - ' . $table . ':');
				foreach ($fields as $field => $config) {
					$io->out('   * ' . $field);
				}
			}

		} else {
			$io->success('Done :) No length issues around ints found.');
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

				foreach ($fields as $field => $data) {
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
						'limit',
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

		$fields = [];

		$columns = $schema->columns();
		foreach ($columns as $column) {
			$field = $schema->getColumn($column);
			if (!$field) {
				return [];
			}

			if ($this->isIntField($field)) {
				$hasComment = !empty($field['comment']);

				$length = $this->getLength($field);
				if ($length) {
					$matches = [];
					if ($hasComment) {
						preg_match('/\[schema]\s*length:\s*(\d)/', $field['comment'], $matches);
					}
					if (!$matches) {
						$lengthMeta = '[schema] length: ' . $length;
						$field['comment'] = $field['comment'] ? $lengthMeta . '; ' . $field['comment'] : $lengthMeta;
					} elseif ($this->args->getOption('overwrite')) {
						$lengthMeta = '[schema] length: ' . $length;
						$field['comment'] = preg_replace('/\[schema]\s*length:\s*\d/', $lengthMeta, $field['comment']);
					}
					$fields[$column] = $field;
				}
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
			'default' => [
				'short' => 'd',
				'help' => 'Default length from type.',
				'boolean' => true,
			],
			'overwrite' => [
				'short' => 'o',
				'help' => 'Overwrite existing meta file length.',
				'boolean' => true,
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
	protected function isBoolField(array $field): bool {
		if ($field['type'] === 'boolean' && $field['length'] === 1) {
			return true;
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $field
	 *
	 * @return bool
	 */
	protected function isIntField(array $field): bool {
		// We only care about different int types for now
		if (!str_contains($field['type'], 'integer')) {
			return false;
		}
		// Booleans we can ignore, they are always (1) length.
		if ($field['type'] === 'tinyinteger' && $field['length'] === 1 && $field['unsigned'] !== true) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $fieldSchema
	 *
	 * @return bool
	 */
	protected function isFloatField(array $fieldSchema): bool {
		return false;
	}

	/**
	 * @return string
	 */
	protected function getSqlVersion(): string {
		$db = $this->_getConnection();
		$result = $query = $db->execute('SELECT VERSION() as mysql_version')->fetch();

		return $result ? array_shift($result) : '';
	}

	/**
	 * @param array $field
	 *
	 * @return int|null
	 */
	protected function getLength(array $field): ?int {
		$length = $field['length'] ?? null;
		if ($length) {
			return $length;
		}

		if ($this->args->getOption('default')) {
			$map = [
				'integer' => 10,
				'smallinteger' => 5,
				'tinyinteger' => 2,
				'bigint' => 20,
			];

			return $map[$field['type']] ?? null;
		}

		return null;
	}

}
