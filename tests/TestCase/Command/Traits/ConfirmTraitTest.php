<?php

namespace Setup\Test\TestCase\Command\Traits;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Console\TestSuite\StubConsoleInput;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Setup\Command\Traits\ConfirmTrait;

/**
 * @uses \Setup\Command\Traits\ConfirmTrait
 */
class ConfirmTraitTest extends TestCase {

	protected StubConsoleOutput $err;

	/**
	 * @return void
	 */
	public function testForceSkipsPrompt(): void {
		$this->confirm(['force' => true, 'quiet' => true], []);
		$this->assertSame([], $this->err->messages());
	}

	/**
	 * @return void
	 */
	public function testQuietWithoutForceAborts(): void {
		$this->expectException(StopException::class);
		try {
			$this->confirm(['force' => false, 'quiet' => true], ['y']);
		} finally {
			$this->assertStringContainsString('--force', $this->err->output());
		}
	}

	/**
	 * @return void
	 */
	public function testYesContinues(): void {
		$this->confirm(['force' => false, 'quiet' => false], ['y']);
		$this->assertSame([], $this->err->messages());
	}

	/**
	 * @return void
	 */
	public function testEmptyAnswerDefaultsToNo(): void {
		$this->expectException(StopException::class);
		$this->confirm(['force' => false, 'quiet' => false], ['']);
	}

	/**
	 * @param array<string, bool> $options
	 * @param array<string> $input
	 * @return void
	 */
	protected function confirm(array $options, array $input): void {
		$this->err = new StubConsoleOutput();
		$io = new ConsoleIo(new StubConsoleOutput(), $this->err, new StubConsoleInput($input));
		$subject = new class {

			use ConfirmTrait {
				confirmOrAbort as public;
			}

		};
		$subject->confirmOrAbort(new Arguments([], $options, []), $io, 'Sure?');
	}

}
