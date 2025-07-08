<?php
declare(strict_types=1);

namespace Setup\Healthcheck\Check;

interface CheckInterface {

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
	 * Performs the actual check.
	 *
	 * @return void
	 */
	public function check(): void;

	/**
	 * The check domain key this check belongs to.
	 *
	 * @return string
	 */
	public function domain(): string;

	/**
	 * If check is passed or not.
	 *
	 * @return bool
	 */
	public function passed(): bool;

	/**
	 * The severity level of this check.
	 *
	 * @return string
	 */
	public function level(): string;

	/**
	 * @return int
	 */
	public function priority(): int;

	/**
	 * The scope of this check.
	 *
	 * @return array<string>
	 */
	public function scope(): array;

	/**
	 * Returns the message to display when passed.
	 *
	 * @return array<string>
	 */
	public function successMessage(): array;

	/**
	 * Returns the message to display when warnings occur.
	 *
	 * @return array<string>
	 */
	public function warningMessage(): array;

	/**
	 * Returns the message to display when failed.
	 *
	 * @return array<string>
	 */
	public function failureMessage(): array;

	/**
	 * Returns the message to display additional info.
	 *
	 * @return array<string>
	 */
	public function infoMessage(): array;

	/**
	 * Returns the name of this check.
	 *
	 * @return string
	 */
	public function name(): string;

}
