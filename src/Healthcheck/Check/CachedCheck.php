<?php

namespace Setup\Healthcheck\Check;

class CachedCheck implements CheckInterface {

	/**
	 * @param array<string, mixed> $data
	 */
	final protected function __construct(private readonly array $data) {
	}

	/**
	 * @param \Setup\Healthcheck\Check\CheckInterface $check
	 * @param int|null $time
	 * @return static
	 */
	public static function fromCheck(CheckInterface $check, ?int $time = null): static {
		return new static([
			'name' => $check->name(),
			'domain' => $check->domain(),
			'passed' => $check->passed(),
			'level' => $check->level(),
			'priority' => $check->priority(),
			'successMessage' => $check->successMessage(),
			'warningMessage' => $check->warningMessage(),
			'failureMessage' => $check->failureMessage(),
			'infoMessage' => $check->infoMessage(),
			'cachedAt' => $time ?? time(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return $this->data;
	}

	/**
	 * @param array<string, mixed> $data
	 * @return static
	 */
	public static function fromArray(array $data): static {
		return new static($data);
	}

	/**
	 * @return void
	 */
	public function check(): void {
	}

	/**
	 * @return array<string|callable>
	 */
	public function scope(): array {
		return [];
	}

	/**
	 * @return string
	 */
	public function name(): string {
		return $this->data['name'];
	}

	/**
	 * @return string
	 */
	public function domain(): string {
		return $this->data['domain'];
	}

	/**
	 * @return bool
	 */
	public function passed(): bool {
		return $this->data['passed'];
	}

	/**
	 * @return string
	 */
	public function level(): string {
		return $this->data['level'];
	}

	/**
	 * @return int
	 */
	public function priority(): int {
		return $this->data['priority'];
	}

	/**
	 * @return array<string>
	 */
	public function successMessage(): array {
		return $this->data['successMessage'];
	}

	/**
	 * @return array<string>
	 */
	public function warningMessage(): array {
		return $this->data['warningMessage'];
	}

	/**
	 * @return array<string>
	 */
	public function failureMessage(): array {
		return $this->data['failureMessage'];
	}

	/**
	 * @return array<string>
	 */
	public function infoMessage(): array {
		return $this->data['infoMessage'];
	}

	/**
	 * @return int
	 */
	public function cachedAt(): int {
		return $this->data['cachedAt'];
	}

}
