<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Exception\CakeException;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Table;
use PDO;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Check and fix orphaned foreign key records in the database.
 *
 * Detects foreign key values pointing to non-existent parent records.
 * This is useful when constraints weren't enforced historically or after
 * data migrations.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbDataOrphansCommand extends Command {

	use DbToolsTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Check database for orphaned foreign key records (FK values pointing to non-existent parents).';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$modelName = $args->getArgument('model');
		$plugin = (string)$args->getOption('plugin') ?: null;
		$connection = (string)$args->getOption('connection');

		$models = $this->_getModels($modelName, $plugin);

		$io->out('Checking ' . count($models) . ' models for orphaned records:', 1, ConsoleIo::VERBOSE);

		$issues = [];
		foreach ($models as $model) {
			try {
				$modelIssues = $this->checkModel($model, $io, $connection);
				if ($modelIssues) {
					$issues[$model->getAlias()] = $modelIssues;
				}
			} catch (CakeException $e) {
				$io->error('Skipping due to errors: ' . $e->getMessage());

				continue;
			}
		}

		$io->out();
		if (!$issues) {
			$io->success('Done :) No orphaned records found.');

			return static::CODE_SUCCESS;
		}

		$totalCount = 0;
		$io->warning('Found orphaned records:');
		foreach ($issues as $model => $associations) {
			$io->out(' - ' . $model . ':');
			foreach ($associations as $assocName => $data) {
				$io->out('   * ' . $data['foreign_key'] . ' -> ' . $data['target_table'] . ': ' . $data['count'] . ' orphaned records');
				$totalCount += $data['count'];
			}
		}
		$io->out();
		$io->out('Total: ' . $totalCount . ' orphaned records.');

		if ($args->getOption('verbose')) {
			$io->out();
			$io->out('SQL to identify orphaned records:');
			$io->out();

			foreach ($issues as $model => $associations) {
				foreach ($associations as $assocName => $data) {
					$io->out("-- {$data['table']}.{$data['foreign_key']} -> {$data['target_table']}");
					$io->out("SELECT * FROM `{$data['table']}` WHERE `{$data['foreign_key']}` IS NOT NULL AND `{$data['foreign_key']}` NOT IN (SELECT `{$data['binding_key']}` FROM `{$data['target_table']}`);");
					$io->out();
				}
			}

			$io->out('SQL to fix (sets orphaned FKs to NULL):');
			$io->out();

			foreach ($issues as $model => $associations) {
				foreach ($associations as $assocName => $data) {
					$io->out("UPDATE `{$data['table']}` SET `{$data['foreign_key']}` = NULL WHERE `{$data['foreign_key']}` IS NOT NULL AND `{$data['foreign_key']}` NOT IN (SELECT `{$data['binding_key']}` FROM `{$data['target_table']}`);");
				}
			}
		} else {
			$io->out();
			$io->info('Tip: Use verbose mode (-v) to see SQL statements.');
		}

		if ($args->getOption('fix')) {
			$this->executeFixing($issues, $connection, $io, (bool)$args->getOption('delete'));
		}

		return static::CODE_SUCCESS;
	}

	/**
	 * Check a model for orphaned foreign key records.
	 *
	 * @param \Cake\ORM\Table $model The model to check
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param string $connection Connection name
	 * @return array<string, array<string, mixed>> Issues found
	 */
	protected function checkModel(Table $model, ConsoleIo $io, string $connection): array {
		$table = $model->getTable();
		if (!$table) {
			return [];
		}

		$io->verbose('### ' . $model->getAlias());

		$associations = $model->associations();
		$issues = [];

		foreach ($associations as $association) {
			// Only check BelongsTo associations (these have the FK on this table)
			if (!$association instanceof BelongsTo) {
				continue;
			}

			$foreignKey = $association->getForeignKey();
			$targetTable = $association->getTarget()->getTable();
			$bindingKey = $association->getBindingKey();

			if (!$foreignKey || !$targetTable || !$bindingKey) {
				continue;
			}

			// Handle composite foreign keys (skip for now)
			if (is_array($foreignKey) || is_array($bindingKey)) {
				$io->verbose(' - Skipping composite FK: ' . $association->getName());

				continue;
			}

			$orphanCount = $this->countOrphanedRecords($table, $foreignKey, $targetTable, $bindingKey, $connection);

			if ($orphanCount > 0) {
				$io->verbose(' - ' . $foreignKey . ' -> ' . $targetTable . ': ' . $orphanCount . ' orphaned');
				$issues[$association->getName()] = [
					'table' => $table,
					'foreign_key' => $foreignKey,
					'target_table' => $targetTable,
					'binding_key' => $bindingKey,
					'count' => $orphanCount,
				];
			}
		}

		return $issues;
	}

	/**
	 * Count orphaned records for a foreign key relationship.
	 *
	 * @param string $table Source table name
	 * @param string $foreignKey Foreign key column
	 * @param string $targetTable Target table name
	 * @param string $bindingKey Binding key column in target table
	 * @param string $connection Connection name
	 * @return int Number of orphaned records
	 */
	protected function countOrphanedRecords(
		string $table,
		string $foreignKey,
		string $targetTable,
		string $bindingKey,
		string $connection,
	): int {
		$db = $this->_getConnection($connection);

		$sql = "SELECT COUNT(*) as cnt
			FROM `{$table}`
			WHERE `{$foreignKey}` IS NOT NULL
			AND `{$foreignKey}` NOT IN (SELECT `{$bindingKey}` FROM `{$targetTable}`)";

		$result = $db->execute($sql)->fetch(PDO::FETCH_ASSOC);

		return (int)($result['cnt'] ?? 0);
	}

	/**
	 * Execute fixing of orphaned records.
	 *
	 * @param array<string, array<string, mixed>> $issues Issues found
	 * @param string $connection Connection name
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param bool $delete Whether to delete orphaned records instead of nullifying
	 * @return void
	 */
	protected function executeFixing(array $issues, string $connection, ConsoleIo $io, bool $delete): void {
		if (!$issues) {
			return;
		}

		$action = $delete ? 'DELETE orphaned records' : 'set orphaned FKs to NULL';
		$continue = $io->askChoice("Continue? This will {$action} in the DB!", ['y', 'n'], 'n');
		if ($continue !== 'y') {
			$io->abort('Aborted!');
		}

		$db = $this->_getConnection($connection);
		$fixed = 0;

		foreach ($issues as $model => $associations) {
			foreach ($associations as $assocName => $data) {
				if ($delete) {
					$sql = "DELETE FROM `{$data['table']}`
						WHERE `{$data['foreign_key']}` IS NOT NULL
						AND `{$data['foreign_key']}` NOT IN (SELECT `{$data['binding_key']}` FROM `{$data['target_table']}`)";
				} else {
					$sql = "UPDATE `{$data['table']}` SET `{$data['foreign_key']}` = NULL
						WHERE `{$data['foreign_key']}` IS NOT NULL
						AND `{$data['foreign_key']}` NOT IN (SELECT `{$data['binding_key']}` FROM `{$data['target_table']}`)";
				}

				$statement = $db->execute($sql);
				$fixed += $statement->rowCount();
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
				'help' => 'Fix orphaned records by setting FK to NULL (or delete with --delete).',
				'boolean' => true,
			],
			'delete' => [
				'short' => 'd',
				'help' => 'Delete orphaned records instead of setting FK to NULL. Use with --fix.',
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
