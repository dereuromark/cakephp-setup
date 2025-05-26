<?php

namespace Setup\Test\TestCase\Queue\Task;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Setup\Queue\Task\HealthcheckTask;

class HealthcheckTaskTest extends TestCase {

	/**
	 * @var list<string>
	 */
	protected array $fixtures = [
	];

	/**
	 * @return void
	 */
	#[DoesNotPerformAssertions]
	public function testRun(): void {
		$task = new HealthcheckTask();

		$task->run([], 0);
	}

}
