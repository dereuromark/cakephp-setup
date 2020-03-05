<?php

namespace Setup\Test\TestCase\Utility;

use Setup\Utility\Setup;
use Shim\TestSuite\TestCase;

class SetupTest extends TestCase {

	/**
	 * @return void
	 */
	public function testCleanedUrl() {
		$url = ['controller' => 'ControllerName', 'action' => 'action_name', '?' => ['clearcache' => 1, 'foo' => 'bar']];
		$result = Setup::cleanedUrl('clearcache', $url);
		$expected = ['controller' => 'ControllerName', 'action' => 'action_name', '?' => ['foo' => 'bar']];
		$this->assertSame($expected, $result);
	}

}
