<?php

namespace Setup\Command;

use BackedEnum;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Exception\CakeException;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use PDO;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Check and fix invalid enum values in the database.
 *
 * Detects:
 * - Values not matching PHP BackedEnum cases (primary)
 * - Values not matching MySQL ENUM definition (with deprecation warning)
 * - Mismatches between PHP and MySQL enum definitions
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbDataEnumsCommand extends Command {

	use DbToolsTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Check database for invalid enum values against PHP BackedEnum definitions.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$connection = (string)$args->getOption('connection');
		$modelName = $args->getArgument('model');
		$plugin = (string)$args->getOption('plugin') ?: null;

		$io->out('Checking PHP BackedEnum columns...', 1, ConsoleIo::VERBOSE);

		// Check PHP BackedEnum columns
		$phpEnumIssues = $this->checkPhpEnums($modelName, $plugin, $io, $connection);

		// Check MySQL ENUM columns (with deprecation warning)
		$io->out();
		$io->out('Checking MySQL ENUM columns...', 1, ConsoleIo::VERBOSE);
		$mysqlEnumIssues = $this->checkMysqlEnums($connection, $io, $args->getArgument('model'));

		$io->out();
		if (!$phpEnumIssues && !$mysqlEnumIssues) {
			$io->success('Done :) No invalid enum values found.');

			return static::CODE_SUCCESS;
		}

		// Report PHP enum issues
		if ($phpEnumIssues) {
			$io->warning('Found invalid PHP BackedEnum values:');
			foreach ($phpEnumIssues as $table => $columns) {
				$io->out(' - ' . $table . ':');
				foreach ($columns as $column => $data) {
					$io->out('   * ' . $column . ' (' . $data['enum_class'] . '):');
					foreach ($data['invalid_values'] as $value => $count) {
						$io->out('     - "' . $value . '": ' . $count . ' records');
					}
				}
			}
		}

		// Report MySQL enum issues
		if ($mysqlEnumIssues) {
			$io->out();
			foreach ($mysqlEnumIssues as $table => $columns) {
				foreach ($columns as $column => $data) {
					if ($data['is_deprecated']) {
						$io->warning('⚠ ' . $table . '.' . $column . ': MySQL ENUM detected - consider VARCHAR + PHP BackedEnum');
					}
					if ($data['invalid_values']) {
						$io->out('   Invalid values:');
						foreach ($data['invalid_values'] as $value => $count) {
							$io->out('     - "' . $value . '": ' . $count . ' records');
						}
					}
					if ($data['mismatch']) {
						$io->warning('   PHP/MySQL enum mismatch: ' . $data['mismatch']);
					}
				}
			}
		}

		if ($args->getOption('verbose')) {
			$this->outputFixSql($phpEnumIssues, $mysqlEnumIssues, $io);
		} else {
			$io->out();
			$io->info('Tip: Use verbose mode (-v) to see SQL fix statements.');
		}

		if ($args->getOption('fix')) {
			$this->executeFixing($phpEnumIssues, $mysqlEnumIssues, $connection, $io);
		}

		return static::CODE_SUCCESS;
	}

	/**
	 * Check PHP BackedEnum columns for invalid values.
	 *
	 * @param string|null $modelName Specific model to check
	 * @param string|null $plugin Plugin name
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param string $connection Connection name
	 * @return array<string, array<string, mixed>> Issues found
	 */
	protected function checkPhpEnums(?string $modelName, ?string $plugin, ConsoleIo $io, string $connection): array {
		$models = $this->_getModels($modelName, $plugin);
		$issues = [];

		foreach ($models as $model) {
			try {
				$table = $model->getTable();
				if (!$table) {
					continue;
				}

				$schema = $model->getSchema();
				$columns = $schema->columns();

				foreach ($columns as $column) {
					$columnType = $schema->getColumnType($column);
					if (!$columnType || !str_starts_with($columnType, 'enum-')) {
						continue;
					}

					try {
						$typeObject = TypeFactory::build($columnType);
					} catch (CakeException) {
						continue;
					}

					if (!$typeObject instanceof EnumType) {
						continue;
					}

					$enumClassName = $typeObject->getEnumClassName();
					if (!class_exists($enumClassName) || !is_subclass_of($enumClassName, BackedEnum::class)) {
						continue;
					}

					$io->verbose('- ' . $table . '.' . $column . ' (' . $enumClassName . ')');

					$validValues = array_map(
						fn(BackedEnum $case): string|int => $case->value,
						$enumClassName::cases(),
					);

					$invalidValues = $this->findInvalidValues($table, $column, $validValues, $connection);
					if ($invalidValues) {
						$issues[$table][$column] = [
							'enum_class' => $enumClassName,
							'valid_values' => $validValues,
							'invalid_values' => $invalidValues,
						];
					}
				}
			} catch (CakeException $e) {
				$io->error('Skipping model due to errors: ' . $e->getMessage());
			}
		}

		return $issues;
	}

	/**
	 * Check MySQL ENUM columns for invalid values and deprecation.
	 *
	 * @param string $connection Connection name
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param string|null $tableFilter Specific table to check
	 * @return array<string, array<string, mixed>> Issues found
	 */
	protected function checkMysqlEnums(string $connection, ConsoleIo $io, ?string $tableFilter): array {
		$db = $this->_getConnection($connection);
		$config = $db->config();
		$database = $config['database'];

		$sql = "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = '{$database}'
			AND DATA_TYPE = 'enum'";

		if ($tableFilter) {
			$sql .= " AND TABLE_NAME = '{$tableFilter}'";
		}

		$columns = $db->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
		if (!$columns) {
			return [];
		}

		$issues = [];
		foreach ($columns as $columnData) {
			$table = $columnData['TABLE_NAME'];
			$column = $columnData['COLUMN_NAME'];
			$columnType = $columnData['COLUMN_TYPE'];

			$io->verbose('- ' . $table . '.' . $column . ' (MySQL ENUM)');

			// Parse enum values from COLUMN_TYPE like "enum('value1','value2')"
			$validValues = $this->parseMysqlEnumValues($columnType);

			// Always flag MySQL ENUMs as deprecated
			$issues[$table][$column] = [
				'is_deprecated' => true,
				'mysql_values' => $validValues,
				'invalid_values' => [],
				'mismatch' => null,
			];

			// Check for invalid values (shouldn't exist with MySQL ENUM, but check anyway)
			$invalidValues = $this->findInvalidValues($table, $column, $validValues, $connection);
			if ($invalidValues) {
				$issues[$table][$column]['invalid_values'] = $invalidValues;
			}
		}

		return $issues;
	}

	/**
	 * Parse MySQL ENUM values from COLUMN_TYPE.
	 *
	 * @param string $columnType Column type like "enum('value1','value2')"
	 * @return array<string> Valid enum values
	 */
	protected function parseMysqlEnumValues(string $columnType): array {
		// Match enum('value1','value2','value3')
		if (!preg_match("/^enum\('(.*)'\)$/i", $columnType, $matches)) {
			return [];
		}

		$valuesString = $matches[1];
		// Split by ',' but handle escaped quotes
		$values = preg_split("/','/" , $valuesString);

		return $values ?: [];
	}

	/**
	 * Find values in database that are not in the valid values list.
	 *
	 * @param string $table Table name
	 * @param string $column Column name
	 * @param array<string|int> $validValues Valid enum values
	 * @param string $connection Connection name
	 * @return array<string, int> Invalid value => count
	 */
	protected function findInvalidValues(string $table, string $column, array $validValues, string $connection): array {
		$db = $this->_getConnection($connection);

		// Build NOT IN clause
		$quotedValues = array_map(fn($v) => "'" . addslashes((string)$v) . "'", $validValues);
		$notInClause = implode(',', $quotedValues);

		// Find distinct invalid values with counts
		$sql = "SELECT `{$column}` as val, COUNT(*) as cnt
			FROM `{$table}`
			WHERE `{$column}` IS NOT NULL
			AND `{$column}` NOT IN ({$notInClause})
			GROUP BY `{$column}`";

		$results = $db->execute($sql)->fetchAll(PDO::FETCH_ASSOC);

		$invalid = [];
		foreach ($results as $row) {
			$invalid[$row['val']] = (int)$row['cnt'];
		}

		return $invalid;
	}

	/**
	 * Output SQL fix statements.
	 *
	 * @param array<string, array<string, mixed>> $phpEnumIssues PHP enum issues
	 * @param array<string, array<string, mixed>> $mysqlEnumIssues MySQL enum issues
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @return void
	 */
	protected function outputFixSql(array $phpEnumIssues, array $mysqlEnumIssues, ConsoleIo $io): void {
		if (!$phpEnumIssues && !$mysqlEnumIssues) {
			return;
		}

		$io->out();
		$io->out('SQL to fix (sets invalid values to NULL):');
		$io->out();

		foreach ($phpEnumIssues as $table => $columns) {
			foreach ($columns as $column => $data) {
				foreach (array_keys($data['invalid_values']) as $value) {
					$io->out("UPDATE `{$table}` SET `{$column}` = NULL WHERE `{$column}` = '" . addslashes((string)$value) . "';");
				}
			}
		}

		foreach ($mysqlEnumIssues as $table => $columns) {
			foreach ($columns as $column => $data) {
				foreach (array_keys($data['invalid_values']) as $value) {
					$io->out("UPDATE `{$table}` SET `{$column}` = NULL WHERE `{$column}` = '" . addslashes((string)$value) . "';");
				}
			}
		}
	}

	/**
	 * Execute fixing of invalid values.
	 *
	 * @param array<string, array<string, mixed>> $phpEnumIssues PHP enum issues
	 * @param array<string, array<string, mixed>> $mysqlEnumIssues MySQL enum issues
	 * @param string $connection Connection name
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @return void
	 */
	protected function executeFixing(array $phpEnumIssues, array $mysqlEnumIssues, string $connection, ConsoleIo $io): void {
		$allIssues = array_merge_recursive($phpEnumIssues, $mysqlEnumIssues);

		if (!$allIssues) {
			return;
		}

		$continue = $io->askChoice('Continue? This will set invalid enum values to NULL in the DB!', ['y', 'n'], 'n');
		if ($continue !== 'y') {
			$io->abort('Aborted!');
		}

		$db = $this->_getConnection($connection);
		$fixed = 0;

		foreach ($phpEnumIssues as $table => $columns) {
			foreach ($columns as $column => $data) {
				foreach (array_keys($data['invalid_values']) as $value) {
					$sql = "UPDATE `{$table}` SET `{$column}` = NULL WHERE `{$column}` = '" . addslashes((string)$value) . "'";
					$statement = $db->execute($sql);
					$fixed += $statement->rowCount();
				}
			}
		}

		foreach ($mysqlEnumIssues as $table => $columns) {
			foreach ($columns as $column => $data) {
				foreach (array_keys($data['invalid_values']) as $value) {
					$sql = "UPDATE `{$table}` SET `{$column}` = NULL WHERE `{$column}` = '" . addslashes((string)$value) . "'";
					$statement = $db->execute($sql);
					$fixed += $statement->rowCount();
				}
			}
		}

		$io->success('Fixed ' . $fixed . ' records.');
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'fix' => [
				'short' => 'f',
				'help' => 'Fix invalid enum values by setting them to NULL.',
				'boolean' => true,
			],
			'plugin' => [
				'short' => 'p',
				'help' => 'Plugin to check.',
			],
			'connection' => [
				'short' => 'c',
				'help' => 'The datasource connection to use.',
				'default' => 'default',
			],
		];
		$arguments = [
			'model' => [
				'help' => 'Specific model (table) to check.',
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addOptions($options)
			->addArguments($arguments);
	}

}
