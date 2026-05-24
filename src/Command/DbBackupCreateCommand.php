<?php

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use RuntimeException;
use Setup\Command\Traits\DbBackupTrait;
use Setup\Command\Traits\DbToolsTrait;

/**
 * Dumps DB backup.
 *
 * - Custom backup path possible
 * - Custom tables possible (prompted or manually passed)
 * - Clear and cleanup
 * - Could be used in a cronjob environment (e.g.: backup every 12 hours)
 *
 * @author Mark Scherer
 * @license MIT
 */
class DbBackupCreateCommand extends Command {

	use DbToolsTrait;
	use DbBackupTrait;

	protected Arguments $args;

	protected ConsoleIo $io;

	/**
	 * Path to a temp .my.cnf-shaped credentials file passed to mysqldump via
	 * --defaults-extra-file so the password is never visible in argv. Cleaned up
	 * by {@see create()} in a finally block.
	 *
	 * @var string|null
	 */
	protected ?string $credentialsFile = null;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Dumps SQL database into a backup file.';
	}

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		if (!is_dir(BACKUPS)) {
			mkdir(BACKUPS, CHMOD_PUBLIC, true);
		}
	}

	/**
	 * Creates a new user including a freshly hashed password.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$this->args = $args;
		$this->io = $io;

		$db = $this->_getConnection();
		$config = $db->config();
		$usePrefix = empty($config['prefix']) ? '' : $config['prefix'];

		$file = $this->_path() . 'backup_' . date('Y-m-d--H-i-s');

		// Don't interpolate the password into argv — `ps auxf` would surface it for
		// any process on the box during the dump. Write it into a 0600 temp ini
		// file and reference via --defaults-extra-file, then unlink in the finally
		// block of create(). The username and host are not sensitive in the same
		// way, but routing them through the same file keeps argv clean and the
		// temp-file shape compatible with mysqldump's documented `[mysqldump]`
		// section.
		$this->credentialsFile = $this->writeCredentialsFile($config);

		$optionStrings = [
			'--defaults-extra-file=' . escapeshellarg($this->credentialsFile),
			'--default-character-set=' . escapeshellarg((string)($config['encoding'] ?? 'utf8')),
			'--databases ' . escapeshellarg((string)$config['database']),
			'--no-create-db',
		];
		if ($args->getOption('no-data')) {
			$optionStrings[] = '--no-data';
		}

		/** @var \Cake\Database\Schema\Collection $schemaCollection */
		$schemaCollection = $db->getSchemaCollection();
		$sources = $schemaCollection->listTables();

		if ($args->getOption('reset')) {
			$this->clearAll();
		}

		/** @var string|null $tables */
		$tables = $args->getOption('tables');
		if ($tables) {
			$sources = explode(',', $tables);
			foreach ($sources as $key => $val) {
				$sources[$key] = $usePrefix . $val;
			}
			$optionStrings[] = '--tables ' . implode(' ', array_map('escapeshellarg', $sources));
			$file .= '_custom';
		} elseif ($usePrefix) {
			foreach ($sources as $key => $source) {
				if (!str_starts_with((string) $source, (string) $usePrefix)) {
					unset($sources[$key]);
				}
			}
			$optionStrings[] = '--tables ' . implode(' ', array_map('escapeshellarg', $sources));
			$file .= '_' . rtrim((string) $usePrefix, '_');
		}
		$file .= '.sql';
		if ($args->getOption('compress')) {
			$optionStrings[] = '| gzip';
			$file .= '.gz';
		}
		$optionStrings[] = '> ' . escapeshellarg($file);

		$this->io->out('Backup will be written to:');
		$this->io->out(' - ' . $this->_path());
		if (!$this->args->getOption('force')) {
			$looksGood = $this->io->askChoice('Look okay?', ['y', 'n'], 'y');
			if ($looksGood !== 'y') {
				$this->io->abort('Aborted!');
			}
		}

		if ($this->create($optionStrings)) {
			$this->io->out('Done :)');
		}
	}

	/**
	 * @param array<string> $optionStrings
	 *
	 * @return bool
	 */
	protected function create(array $optionStrings): bool {
		$command = $this->_command('mysqldump');
		if ($optionStrings) {
			$command .= ' ' . implode(' ', $optionStrings);
		}

		try {
			if ($this->args->getOption('dry-run')) {
				$this->io->out($command);

				return true;
			}

			exec($command, $output, $ret);

			return $ret === static::CODE_SUCCESS;
		} finally {
			// Always clean up the temp credentials file, even on a thrown exception
			// from exec() or a forced abort. Leaving a 0600 cnf in /tmp is not
			// terribly leaky, but the process owns it and should drop it.
			if ($this->credentialsFile !== null && is_file($this->credentialsFile)) {
				@unlink($this->credentialsFile);
				$this->credentialsFile = null;
			}
		}
	}

	/**
	 * Write a `[mysqldump]`-section credentials file to a 0600-perm temp path.
	 *
	 * Returns the file path; the caller is responsible for unlinking it after
	 * the mysqldump invocation completes. Used in place of the previous
	 * `--user=...` / `--password=...` argv interpolation, which exposed the
	 * password to anyone able to read `ps`.
	 *
	 * @param array<string, mixed> $config Cake connection config (host, username, password).
	 * @return string Absolute path to the written credentials file.
	 */
	protected function writeCredentialsFile(array $config): string {
		$path = tempnam(sys_get_temp_dir(), 'cake-setup-mycnf-');
		if ($path === false) {
			throw new RuntimeException('Failed to create temp file for mysqldump credentials');
		}

		$content = "[mysqldump]\n";
		if (isset($config['username'])) {
			$content .= 'user="' . addslashes((string)$config['username']) . "\"\n";
		}
		if (isset($config['password'])) {
			$content .= 'password="' . addslashes((string)$config['password']) . "\"\n";
		}
		if (isset($config['host'])) {
			$content .= 'host="' . addslashes((string)$config['host']) . "\"\n";
		}

		file_put_contents($path, $content);
		chmod($path, 0600);

		return $path;
	}

	/**
	 * Deletes all sql backup files
	 *
	 * @return void
	 */
	protected function clearAll(): void {
		$files = $this->_getFiles(BACKUPS);
		$this->io->out(count($files) . ' existing files found');

		$dryRun = $this->args->getOption('dry-run');
		foreach ($files as $file) {
			if (!$dryRun) {
				unlink(BACKUPS . $file);
			}
		}
		$this->io->out(($dryRun ? 'Dry-Run' : 'Done') . ': ' . sprintf('%s deleted', count($files)));
		$this->io->out();
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$options = [
			'dry-run' => [
				'short' => 'd',
				'help' => 'Dry run the update, no files will actually be modified.',
				'boolean' => true,
			],
			'tables' => [
				'short' => 't',
				'help' => 'custom tables to dump (separate using , and NO SPACES - use no prefix). Use -t only for prompting tables.',
			],
			'compress' => [
				'short' => 'c',
				'help' => 'compress using gzip',
				'boolean' => true,
			],
			'path' => [
				'short' => 'p',
				'help' => 'Use a custom backup directory',
				'default' => '',
			],
			'force' => [
				'short' => 'f',
				'help' => 'Force the command, do not ask for confirmation.',
				'boolean' => true,
			],
			'reset' => [
				'short' => 'r',
				'help' => 'Remove all existing backup files in the process.',
				'boolean' => true,
			],
			'no-data' => [
				'short' => 'n',
				'help' => 'Only schema, no data.',
				'boolean' => true,
			],
		];

		return parent::getOptionParser()
			->setDescription(static::getDescription() . ' The advantage: It uses native CLI commands which save a lot of resources and are very fast.')
			->addArgument('file', [
				'help' => 'Use a specific backup file (needs to be an absolute path).',
				'optional' => true,
			])
			->addOptions($options);
	}

}
