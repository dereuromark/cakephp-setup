<?php

namespace Setup\Healthcheck\Check\Database;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Exception;
use Setup\Healthcheck\Check\Check;

class DatabaseCharsetCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the database is configured to use utf8mb4 charset for full Unicode support.';

	/**
	 * @var string Connection
	 */
	protected const DEFAULT = 'default';

	protected string $level = self::LEVEL_INFO;

	protected string $connection;

	/**
	 * @param string|null $connection
	 */
	public function __construct(?string $connection = null) {
		if ($connection === null) {
			$connection = static::DEFAULT;
		}

		$this->connection = $connection;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		try {
			/** @var \Cake\Database\Connection $connection */
			$connection = ConnectionManager::get($this->connection);
			$driver = $connection->getDriver();
			$driverClass = get_class($driver);

			// Only check MySQL/MariaDB
			if (!str_contains($driverClass, 'Mysql')) {
				$this->passed = true;
				$this->infoMessage[] = 'Charset check skipped for non-MySQL database.';

				return;
			}

			$this->checkMysqlCharset($connection);
		} catch (Exception $e) {
			$this->passed = true;
			$this->infoMessage[] = 'Could not check database charset: ' . $e->getMessage();
		}
	}

	/**
	 * @param \Cake\Database\Connection $connection
	 * @return void
	 */
	protected function checkMysqlCharset(Connection $connection): void {
		$config = $connection->config();
		$encoding = $config['encoding'] ?? null;

		if ($encoding === null) {
			$this->passed = true;
			$this->infoMessage[] = 'Database encoding not explicitly configured. Consider setting \'encoding\' => \'utf8mb4\' for full Unicode support (including emojis).';

			return;
		}

		if (strtolower($encoding) === 'utf8mb4') {
			$this->passed = true;
			$this->infoMessage[] = 'Database is configured to use utf8mb4 encoding.';

			return;
		}

		if (strtolower($encoding) === 'utf8' || strtolower($encoding) === 'utf8mb3') {
			$this->passed = true;
			$this->infoMessage[] = 'Database is using "' . $encoding . '" encoding. Consider upgrading to "utf8mb4" for full Unicode support (including emojis and some special characters).';

			return;
		}

		$this->passed = true;
		$this->infoMessage[] = 'Database is using "' . $encoding . '" encoding.';
	}

}
