<?php
declare(strict_types=1);

namespace Setup\Healthcheck\Check;

interface CheckInterface {

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
