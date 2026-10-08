<?php

namespace Setup\Healthcheck\Check;

use InvalidArgumentException;
use RuntimeException;

abstract class Check implements CheckInterface {

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

	protected ?int $cacheTtl = null;

	protected int $revision = 0;

	/**
	 * @var array<string|callable>
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
	 * @return array<string|callable>
	 */
	public function scope(): array {
		return $this->scope;
	}

	/**
	 * @param string $level
	 * @return $this
	 */
	public function adjustLevel(string $level) {
		if (!in_array($level, [CheckInterface::LEVEL_ERROR, CheckInterface::LEVEL_WARNING, CheckInterface::LEVEL_INFO], true)) {
			throw new InvalidArgumentException('Invalid healthcheck level: ' . $level);
		}

		$this->level = $level;
		$this->revision++;

		return $this;
	}

	/**
	 * Bumped by every adjust*() call so cached results keyed on the old configuration are not reused.
	 *
	 * @return int
	 */
	public function revision(): int {
		return $this->revision;
	}

	/**
	 * @return int|null
	 */
	public function cacheTtl(): ?int {
		return $this->cacheTtl;
	}

	/**
	 * @param int|null $seconds
	 * @return $this
	 */
	public function adjustCacheTtl(?int $seconds) {
		if ($seconds !== null && $seconds < 0) {
			throw new InvalidArgumentException('Cache TTL must not be negative.');
		}

		$this->cacheTtl = $seconds;
		$this->revision++;

		return $this;
	}

	/**
	 * @param int $priority
	 * @return $this
	 */
	public function adjustPriority(int $priority) {
		assert($this->priority > 0 && $this->priority < 10);

		$this->priority = $priority;
		$this->revision++;

		return $this;
	}

	/**
	 * @param array<string|callable> $scope
	 * @return $this
	 */
	public function adjustScope(array $scope) {
		$this->scope = $scope;
		$this->revision++;

		return $this;
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
