<?php

namespace Setup\Healthcheck\Check;

use RuntimeException;

abstract class Check implements CheckInterface {

	/**
	 * @var string
	 */
	public const SCOPE_WEB = 'web';

	/**
	 * @var string
	 */
	public const SCOPE_CLI = 'cli';

	/**
	 * @var string
	 */
	public const LEVEL_ERROR = 'error';

	/**
	 * @var string
	 */
	public const LEVEL_WARNING = 'warning';

	/**
	 * @var string
	 */
	public const LEVEL_INFO = 'info';

	/**
	 * @var bool|null
	 */
	protected ?bool $passed = null;

	protected array $failureMessage = [];

	protected array $warningMessage = [];

	protected array $successMessage = [];

	protected array $infoMessage = [];

	protected string $level = self::LEVEL_ERROR;

	protected int $priority = 5;

	/**
	 * @var array<string>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
		self::SCOPE_CLI,
	];

	/**
	 * @return bool
	 */
	public function passed(): bool {
		if ($this->passed === null) {
			throw new RuntimeException('check() was not run yet.');
		}

		return $this->passed;
	}

	/**
	 * @return string
	 */
	public function level(): string {
		return $this->level;
	}

	/**
	 * @return int
	 */
	public function priority(): int {
		assert($this->priority > 0 && $this->priority < 10);

		return $this->priority;
	}

	/**
	 * @return string[]
	 */
	public function scope(): array {
		return $this->scope;
	}

	/**
	 * @return string
	 */
	public function name(): string {
		// Read from last namespace part
		$name = explode('\\', static::class);

		return (string)array_pop($name);
	}

	/**
	 * @return string
	 */
	public function domain(): string {
		// Read from last namespace part
		$domain = explode('\\', static::class);
		array_pop($domain);

		return (string)array_pop($domain);
	}

	/**
	 * @return array<string>
	 */
	public function infoMessage(): array {
		return $this->infoMessage;
	}

	/**
	 * @return array<string>
	 */
	public function successMessage(): array {
		return $this->successMessage;
	}

	/**
	 * @return array<string>
	 */
	public function warningMessage(): array {
		return $this->warningMessage;
	}

	/**
	 * @return array<string>
	 */
	public function failureMessage(): array {
		return $this->failureMessage;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo(): array {
		$result = [
			'passed' => $this->passed,
		];
		if ($this->failureMessage) {
			$result['failureMessage'] = $this->failureMessage;
		}
		if ($this->warningMessage) {
			$result['warningMessage'] = $this->warningMessage;
		}
		if ($this->successMessage) {
			$result['successMessage'] = $this->successMessage;
		}
		if ($this->infoMessage) {
			$result['infoMessage'] = $this->infoMessage;
		}

		return $result;
	}

}
