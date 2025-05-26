<?php

namespace Setup\Healthcheck\Check\Database;

use Cake\Datasource\ConnectionManager;
use Exception;
use Setup\Healthcheck\Check\Check;

class ConnectCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the database can be connected to.';

	/**
	 * @var string Connection
	 */
	protected const DEFAULT = 'default';

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
		$this->assertConnection();
	}

	/**
	 * @return void
	 */
	protected function assertConnection(): void {
		try {
			/** @var \Cake\Database\Connection $connection */
			$connection = ConnectionManager::get($this->connection);
			$connection->getDriver()->connect();
			$this->passed = true;
		} catch (Exception $connectionError) {
			$this->passed = false;
		}

		if (!$this->passed) {
			$this->failureMessage[] = 'Cannot connect to database on connection `' . $this->connection . '`: ' . $connectionError->getMessage();

			return;
		}

		$this->infoMessage[] = 'The PHP upload limit is set to `' . ini_get('upload_max_filesize') . '` and the post limit is set to `' . ini_get('post_max_size') . '`.';
	}

	/**
	 * @param string $val
	 * @return int
	 */
	protected function toBytes(string $val): int {
		$val = trim($val);
		$unit = strtolower($val[strlen($val) - 1]);
		$bytes = (int)$val;

		switch ($unit) {
			case 'g':
				$bytes *= 1024;
				// Continue
			case 'm':
				$bytes *= 1024;
				// Continue
			case 'k':
				$bytes *= 1024;
				// Continue
		}

		return $bytes;
	}

}
