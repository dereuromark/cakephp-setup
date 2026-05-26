<?php

namespace Setup\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Setup\Healthcheck\Check\Environment\PhpVersionCheck;
use Setup\Queue\Task\HealthcheckTask;
use Shim\TestSuite\ConsoleOutput;

class HealthcheckTaskTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [];

	/**
	 * @return void
	 */
	public function testRun(): void {
		Configure::write('Setup.Healthcheck.checks', [
			PhpVersionCheck::class,
		]);

		$out = new ConsoleOutput();
		$err = new ConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new HealthcheckTask($io);

		$task->run([], 0);

		$output = $out->output();
		$this->assertStringContainsString('Healthcheck: OK', $output);
		$this->assertStringContainsString('Summary: 1 check(s) in 1 domain(s); errors=0; warnings=0.', $output);
		$this->assertStringContainsString('See the configured log target for full healthcheck details.', $output);
	}

}
