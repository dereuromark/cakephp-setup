<?php

namespace Setup\Command;

use BackedEnum;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use PDO;
use Setup\Command\Traits\DbToolsTrait;
use Throwable;
use ValueError;

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
		$phpEnumIssues = $this->checkPhpEnums($modelName, $plugin, $io, $connection, (bool)$args->getOption('fix'));

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
	 * @param bool $fix Whether to attempt fixing
	 * @return array<string, array<string, mixed>> Issues found
	 */
	protected function checkPhpEnums(?string $modelName, ?string $plugin, ConsoleIo $io, string $connection, bool $fix = false): array {
		try {
			$models = $this->_getModels($modelName, $plugin);
		} catch (Throwable $e) {
			// Invalid enum data prevents model loading - extract info from error
			if ($e instanceof ValueError || $e->getPrevious() instanceof ValueError) {
				$io->warning('Cannot load models due to invalid enum data in database:');
				$io->error($e->getMessage());
				$io->out();

				// Try to find and fix the invalid enum data directly
				$this->tryFixEnumFromError($e, $io, $connection, $fix);
			} else {
				$io->error('Cannot load models: ' . $e->getMessage());
			}

			return [];
		}

		$issues = [];

		foreach ($models as $model) {
			$issues = $this->checkModel($model, $io, $connection, $issues);
		}

		return $issues;
	}

	/**
	 * Check a single model for invalid enum values.
	 *
	 * @param \Cake\ORM\Table $model The model to check
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param string $connection Connection name
	 * @param array<string, array<string, mixed>> $issues Existing issues
	 * @return array<string, array<string, mixed>> Updated issues
	 */
	protected function checkModel(Table $model, ConsoleIo $io, string $connection, array $issues): array {
		try {
			$table = $model->getTable();
			if (!$table) {
				return $issues;
			}

			$schema = $model->getSchema();
			$columns = $schema->columns();

			foreach ($columns as $column) {
				$columnType = $schema->getColumnType($column);
				if (!$columnType || !str_starts_with($columnType, 'enum-')) {
					continue;
				}

				$typeObject = TypeFactory::build($columnType);
				if (!$typeObject instanceof EnumType) {
					continue;
				}

				$enumClassName = $typeObject->getEnumClassName();
				if (!class_exists($enumClassName)) {
					continue;
				}

				$io->verbose('- ' . $table . '.' . $column . ' (' . $enumClassName . ')');

				$validValues = array_map(
					fn (BackedEnum $case): string|int => $case->value,
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
		} catch (ValueError $e) {
			// Invalid enum data in this model - report it
			$io->warning('Model ' . $model->getAlias() . ' has invalid enum data: ' . $e->getMessage());
		} catch (CakeException $e) {
			$io->error('Skipping model due to errors: ' . $e->getMessage());
		} catch (Throwable $e) {
			$io->error('Skipping model ' . $model->getAlias() . ': ' . $e->getMessage());
		}

		return $issues;
	}

	/**
	 * Try to find and fix invalid enum data when models can't be loaded.
	 *
	 * Scans all varchar columns in the database to find columns containing the invalid value.
	 *
	 * @param \Throwable $e The exception
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param string $connection Connection name
	 * @param bool $fix Whether to attempt fixing
	 * @return void
	 */
	protected function tryFixEnumFromError(Throwable $e, ConsoleIo $io, string $connection, bool $fix): void {
		$message = $e->getMessage();

		// Try to extract enum class from error message like:
		// '"" is not a valid backing value for enum App\Model\Enum\PccApiFieldMappingType'
		if (!preg_match('/for enum ([A-Za-z0-9\\\\]+)/', $message, $matches)) {
			$io->info('Fix the invalid enum data manually or use db_data enums with a specific model.');

			return;
		}

		$enumClass = $matches[1];
		$io->info('Enum class: ' . $enumClass);

		if (!class_exists($enumClass) || !is_subclass_of($enumClass, BackedEnum::class)) {
			$io->error('Cannot load enum class: ' . $enumClass);

			return;
		}

		$validValues = array_map(fn (BackedEnum $case): string|int => $case->value, $enumClass::cases());
		$io->out('Valid values: ' . implode(', ', array_map(fn ($v) => "'" . $v . "'", $validValues)));

		// Extract the invalid value from message
		$invalidValue = null;
		if (preg_match('/^"([^"]*)"/', $message, $valueMatches)) {
			$invalidValue = $valueMatches[1];
			$io->out('Invalid value found: ' . ($invalidValue === '' ? '(empty string)' : "'{$invalidValue}'"));
		}

		$io->out();
		$io->info('Scanning Table classes for columns using this enum...');
		$io->out();

		// Scan Table class files to find which column uses this enum
		$found = $this->scanForInvalidEnumColumns($connection, $validValues, $invalidValue, $enumClass, $io);

		if (!$found) {
			$io->warning('Could not automatically locate the column. Check your Table classes for:');
			$io->out('  ->setColumnType(\'column\', EnumType::from(' . $enumClass . '::class))');

			return;
		}

		if ($fix) {
			$this->fixFoundEnumIssues($found, $connection, $io);
		} else {
			$io->out();
			$io->info('Run with --fix to automatically fix these issues.');
		}
	}

	/**
	 * Scan Table class files to find which column uses the enum class.
	 *
	 * @param string $connection Connection name
	 * @param array<string|int> $validValues Valid enum values
	 * @param string|null $invalidValue Specific invalid value to search for
	 * @param string $enumClass The enum class to search for
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @return array<array<string, mixed>> Found columns with issues
	 */
	protected function scanForInvalidEnumColumns(string $connection, array $validValues, ?string $invalidValue, string $enumClass, ConsoleIo $io): array {
		// Search Table class files for the enum registration
		$tableLocations = [
			APP . 'Model' . DS . 'Table',
		];

		// Add plugin paths
		foreach (Plugin::loaded() as $plugin) {
			$pluginPath = Plugin::path($plugin) . 'src' . DS . 'Model' . DS . 'Table';
			if (is_dir($pluginPath)) {
				$tableLocations[] = $pluginPath;
			}
		}

		$found = [];
		$shortEnumClass = substr($enumClass, (int)strrpos($enumClass, '\\') + 1);

		foreach ($tableLocations as $location) {
			if (!is_dir($location)) {
				continue;
			}

			$files = glob($location . DS . '*Table.php');
			if (!$files) {
				continue;
			}

			foreach ($files as $file) {
				$content = file_get_contents($file);
				if ($content === false) {
					continue;
				}

				// Look for setColumnType('column', EnumType::from(EnumClass::class))
				// or getSchema()->setColumnType('column', 'enum-EnumClass')
				if (strpos($content, $enumClass) === false && strpos($content, $shortEnumClass) === false) {
					continue;
				}

				// Try to extract column name from patterns like:
				// ->setColumnType('column_name', EnumType::from(EnumClass::class))
				if (preg_match("/setColumnType\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*EnumType::from\s*\(\s*{$shortEnumClass}::class/", $content, $matches)) {
					$column = $matches[1];
					$tableName = $this->extractTableName($file, $content);

					if ($tableName) {
						$result = $this->checkColumnForInvalidValue($connection, $tableName, $column, $invalidValue, $validValues, $io);
						if ($result) {
							$found[] = $result;
						}
					}
				}
			}
		}

		return $found;
	}

	/**
	 * Extract table name from a Table class file.
	 *
	 * @param string $file File path
	 * @param string $content File content
	 * @return string|null Table name
	 */
	protected function extractTableName(string $file, string $content): ?string {
		// Try to find explicit table name in initialize()
		if (preg_match("/setTable\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches)) {
			return $matches[1];
		}

		// Fall back to convention: UsersTable.php -> users
		$className = basename($file, '.php');
		if (str_ends_with($className, 'Table')) {
			$className = substr($className, 0, -5);

			return Inflector::underscore($className);
		}

		return null;
	}

	/**
	 * Check a specific column for the invalid value.
	 *
	 * @param string $connection Connection name
	 * @param string $table Table name
	 * @param string $column Column name
	 * @param string|null $invalidValue Invalid value to check
	 * @param array<string|int> $validValues Valid enum values
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @return array<string, mixed>|null Issue data or null
	 */
	protected function checkColumnForInvalidValue(string $connection, string $table, string $column, ?string $invalidValue, array $validValues, ConsoleIo $io): ?array {
		$db = $this->_getConnection($connection);
		$config = $db->config();
		$database = $config['database'];

		// Get nullability info
		$sql = "SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = '{$database}'
			AND TABLE_NAME = '{$table}'
			AND COLUMN_NAME = '{$column}'";

		$result = $db->execute($sql)->fetch(PDO::FETCH_ASSOC);
		if (!$result) {
			return null;
		}

		$nullable = $result['IS_NULLABLE'] === 'YES';

		// Check for invalid value
		if ($invalidValue !== null) {
			$checkSql = "SELECT COUNT(*) as cnt FROM `{$table}` WHERE `{$column}` = '" . addslashes($invalidValue) . "'";
			$countResult = $db->execute($checkSql)->fetch(PDO::FETCH_ASSOC);
			$count = (int)($countResult['cnt'] ?? 0);

			if ($count > 0) {
				$io->out("Found in {$table}.{$column}: {$count} records" . ($nullable ? ' (nullable)' : ' (NOT NULL)'));

				return [
					'table' => $table,
					'column' => $column,
					'nullable' => $nullable,
					'invalid_value' => $invalidValue,
					'count' => $count,
					'valid_values' => $validValues,
				];
			}
		}

		return null;
	}

	/**
	 * Fix found enum issues by setting invalid values to NULL or a valid value.
	 *
	 * @param array<array<string, mixed>> $found Found issues
	 * @param string $connection Connection name
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @return void
	 */
	protected function fixFoundEnumIssues(array $found, string $connection, ConsoleIo $io): void {
		$db = $this->_getConnection($connection);
		$fixed = 0;

		foreach ($found as $issue) {
			$table = $issue['table'];
			$column = $issue['column'];
			$nullable = $issue['nullable'];
			$invalidValue = $issue['invalid_value'];
			$validValues = $issue['valid_values'];

			if ($nullable) {
				// Set to NULL
				$sql = "UPDATE `{$table}` SET `{$column}` = NULL WHERE `{$column}` = '" . addslashes($invalidValue) . "'";
				$io->out("Setting {$table}.{$column} = NULL where value = '{$invalidValue}'");
			} else {
				// Column is NOT NULL - ask for a valid value or use first valid value
				$defaultValue = $validValues[0] ?? null;
				if ($defaultValue === null) {
					$io->warning("Cannot fix {$table}.{$column}: NOT NULL and no valid values defined");

					continue;
				}
				$io->warning("{$table}.{$column} is NOT NULL - setting to first valid value: '{$defaultValue}'");
				$sql = "UPDATE `{$table}` SET `{$column}` = '" . addslashes((string)$defaultValue) . "' WHERE `{$column}` = '" . addslashes($invalidValue) . "'";
			}

			$statement = $db->execute($sql);
			$fixed += $statement->rowCount();
		}

		if ($fixed > 0) {
			$io->success('Fixed ' . $fixed . ' records. Please re-run the command to check for more issues.');
		}
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
		$values = preg_split("/','/", $valuesString);

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
		$quotedValues = array_map(fn ($v) => "'" . addslashes((string)$v) . "'", $validValues);
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
