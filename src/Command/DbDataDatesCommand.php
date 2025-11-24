<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use PDO;
use Setup\Command\Traits\DbToolsTrait;
use Throwable;

/**
 * Check and fix invalid date/datetime values in the database.
 *
 * Detects:
 * - Zero dates: '0000-00-00'
 * - Zero datetimes: '0000-00-00 00:00:00'
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbDataDatesCommand extends Command {

	use DbToolsTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Check database for invalid zero date/datetime values (0000-00-00).';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$connection = (string)$args->getOption('connection');
		$tables = $this->_getTables($connection);

		$io->out('Checking ' . count($tables) . ' tables for invalid dates:', 1, ConsoleIo::VERBOSE);

		$issues = [];
		foreach ($tables as $table) {
			$tableArg = $args->getArgument('table');
			if ($tableArg && $table !== $tableArg) {
				continue;
			}

			$tableIssues = $this->checkTable($table, $io, $connection);
			if ($tableIssues) {
				$issues[$table] = $tableIssues;
			}
		}

		$io->out();
		if (!$issues) {
			$io->success('Done :) No invalid date values found.');

			return static::CODE_SUCCESS;
		}

		$totalCount = 0;
		$io->warning('Found invalid date values:');
		foreach ($issues as $table => $columns) {
			$io->out(' - ' . $table . ':');
			foreach ($columns as $column => $data) {
				$io->out('   * ' . $column . ' (' . $data['type'] . '): ' . $data['count'] . ' invalid records');
				$totalCount += $data['count'];
			}
		}
		$io->out();
		$io->out('Total: ' . $totalCount . ' records with invalid dates.');

		if ($args->getOption('verbose')) {
			$io->out();
			$io->out('SQL to fix (sets invalid dates to NULL):');
			$io->out();

			foreach ($issues as $table => $columns) {
				foreach ($columns as $column => $data) {
					$io->out("UPDATE `{$table}` SET `{$column}` = NULL WHERE CAST(`{$column}` AS CHAR(19)) LIKE '0000-00-00%';");
				}
			}
		} else {
			$io->out();
			$io->info('Tip: Use verbose mode (-v) to see SQL fix statements.');
		}

		if ($args->getOption('fix')) {
			$continue = $io->askChoice('Continue? This will set invalid dates to NULL in the DB!', ['y', 'n'], 'n');
			if ($continue !== 'y') {
				$io->abort('Aborted!');
			}

			$db = $this->_getConnection($connection);
			$fixed = 0;
			$failed = [];
			foreach ($issues as $table => $columns) {
				foreach ($columns as $column => $data) {
					$sql = "UPDATE `{$table}` SET `{$column}` = NULL WHERE CAST(`{$column}` AS CHAR(19)) LIKE '0000-00-00%'";
					try {
						$statement = $db->execute($sql);
						$fixed += $statement->rowCount();
					} catch (Throwable $e) {
						// Likely NOT NULL constraint - show the records that can't be fixed
						$failed[] = "{$table}.{$column}";
						$io->warning("Cannot fix {$table}.{$column}: " . $e->getMessage());
						$io->out('Records that need manual fixing:');

						$selectSql = "SELECT * FROM `{$table}` WHERE CAST(`{$column}` AS CHAR(19)) LIKE '0000-00-00%' LIMIT 10";
						$records = $db->execute($selectSql)->fetchAll(PDO::FETCH_ASSOC);
						foreach ($records as $record) {
							$io->out('  ' . json_encode($record, JSON_UNESCAPED_UNICODE));
						}
						if ($data['count'] > 10) {
							$io->out('  ... and ' . ($data['count'] - 10) . ' more records');
						}
						$io->out();
					}
				}
			}

			if ($fixed > 0) {
				$io->success('Fixed ' . $fixed . ' records.');
			}
			if ($failed) {
				$io->warning('Could not fix ' . count($failed) . ' columns due to constraints: ' . implode(', ', $failed));
				$io->info('These columns likely have NOT NULL constraints. Consider setting a default date instead of NULL.');
			}
		}

		return static::CODE_SUCCESS;
	}

	/**
	 * Check a single table for invalid date values.
	 *
	 * @param string $table Table name
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param string $connection Connection name
	 * @return array<string, array<string, mixed>> Column issues
	 */
	protected function checkTable(string $table, ConsoleIo $io, string $connection): array {
		$db = $this->_getConnection($connection);
		$config = $db->config();
		$database = $config['database'];

		// Get date/datetime columns
		$sql = "SELECT COLUMN_NAME, DATA_TYPE
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = '{$database}'
			AND TABLE_NAME = '{$table}'
			AND DATA_TYPE IN ('date', 'datetime', 'timestamp')";

		$columns = $db->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
		if (!$columns) {
			return [];
		}

		$io->verbose('### ' . $table);

		$issues = [];
		foreach ($columns as $columnData) {
			$column = $columnData['COLUMN_NAME'];
			$type = $columnData['DATA_TYPE'];

			// Use CAST to avoid strict SQL mode issues when comparing zero dates
			$countSql = "SELECT COUNT(*) as cnt FROM `{$table}` WHERE CAST(`{$column}` AS CHAR(19)) LIKE '0000-00-00%'";
			$result = $db->execute($countSql)->fetch(PDO::FETCH_ASSOC);
			$count = (int)($result['cnt'] ?? 0);

			if ($count > 0) {
				$io->verbose(' - ' . $column . ': ' . $count . ' invalid');
				$issues[$column] = [
					'type' => $type,
					'count' => $count,
				];
			}
		}

		return $issues;
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'fix' => [
				'short' => 'f',
				'help' => 'Fix invalid dates by setting them to NULL.',
				'boolean' => true,
			],
			'connection' => [
				'short' => 'c',
				'help' => 'The datasource connection to use.',
				'default' => 'default',
			],
		];
		$arguments = [
			'table' => [
				'help' => 'Specific table to check.',
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addOptions($options)
			->addArguments($arguments);
	}

}
