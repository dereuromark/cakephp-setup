<?php

namespace Setup\Command;

use Cake\Collection\Collection;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Exception\CakeException;
use Cake\Utility\Hash;
use PDO;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Can provide fixing for Mysql8+ and tinyint(1) bool which must be signed (unsigned false).
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbIntegrityBoolsCommand extends Command {

	use DbToolsTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Check database integrity issues regarding Mysql 5 to 8 upgrade and boolean (tinyint 1) fields.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$tables = $this->_getTables();

		$io->out('Checking ' . count($tables) . ' tables:', 1, ConsoleIo::VERBOSE);
		$modified = [];
		foreach ($tables as $table) {
			try {
				$modified += $this->checkTable($table, $io);
			} catch (CakeException $e) {
				$io->error('Skipping due to errors: ' . $e->getMessage());

				continue;
			}
		}

		$io->out();
		if ($modified) {
			$io->warning(count($modified) . ' tables found with possible bool issues.');
			foreach ($modified as $table => $fields) {
				$io->out(' - ' . $table . ':');
				foreach ($fields as $field => $config) {
					$io->out('   * ' . $field);
				}
			}

		} else {
			$io->success('Done :) No unsigned issues around bools found.');
		}

		if ($modified && !$args->getOption('verbose')) {
			$io->out();
			$io->info('Tip: Use verbose mode to have a ready-to-use migration file content generated for you.');
		}

		$result = [];
		foreach ($modified as $table => $fields) {
			foreach ($fields as $field => $data) {
				$snippet = 'ALTER TABLE `' . $table . '` CHANGE `' . $field . '` `' . $field . '` ';
				$type = $data['Type'];
				$snippet .= $type;
				if ($data['Null'] === 'YES') {
					$snippet .= ' NULL';
				} else {
					$snippet .= ' NOT NULL';
				}
				if ($data['Default'] !== null) {
					$snippet .= ' DEFAULT \'' . $data['Default'] . '\'';
				}
				if ($data['Comment'] !== null) {
					$snippet .= ' COMMENT \'' . $data['Comment'] . '\'';
				}

				$result[] = $snippet . ';';
			}
		}

		if ($modified && $args->getOption('verbose')) {
			$io->out();
			$io->out('Add the following as migration to your config:');
			$io->out();

			$io->out($result);
		}

		if ($modified && $args->getOption('execute')) {
			$continue = $io->askChoice('Continue? This will modify the DB now!', ['y', 'n'], 'n');
			if ($continue !== 'y') {
				$io->abort('Aborted!');
			}

			$sql = implode(PHP_EOL, $result);

			$db = $this->_getConnection();
			$db->execute($sql);
		}
	}

	/**
	 * @param string $table
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function checkTable(string $table, ConsoleIo $io): array {
		// Structure
		$sql = 'SHOW FULL COLUMNS from `' . $table . '`;';
		$io->verbose('- ' . $sql);

		$db = $this->_getConnection();
		$res = $db->execute($sql)->fetchAll(Pdo::FETCH_ASSOC);
		$schema = (new Collection($res))->toArray();

		$io->verbose('### ' . $table);

		$schema = Hash::combine($schema, '{n}.Field', '{n}');

		$fields = [];

		$columns = array_keys($schema);
		foreach ($columns as $column) {
			$fieldSchema = $schema[$column];
			if ($this->isBoolField($fieldSchema)) {
				if (str_ends_with($fieldSchema['Type'], 'unsigned')) {
					$fieldSchema['Type'] = trim(substr($fieldSchema['Type'], 0, -8));
					$fields[$column] = $fieldSchema;
				}
			}
		}

		return $fields ? [$table => $fields] : [];
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'execute' => [
				'short' => 'e',
				'help' => 'Execute directly instead of generating migration file. DANGER! TODO',
				'boolean' => true,
			],
		];
		$arguments = [
			'table' => [
				'help' => 'Specific table',
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription())
			->addOptions($options)
			->addArguments($arguments);
	}

	/**
	 * @param array<string, mixed> $field
	 *
	 * @return bool
	 */
	protected function isBoolField(array $field): bool {
		if (str_starts_with($field['Type'], 'tinyint(1)')) {
			return true;
		}

		return false;
	}

}
