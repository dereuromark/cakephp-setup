<?php

namespace Setup\Test\TestCase\Queue\Task;

use Cake\TestSuite\TestCase;
use Setup\Queue\Task\HealthcheckTask;

class HealthcheckTaskTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [];

	/**
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testRun(): void {
		$task = new HealthcheckTask();

		$task->run([], 0);
	}

}
