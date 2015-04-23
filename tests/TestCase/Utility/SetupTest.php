<?php
namespace Setup\Test\TestCase\Utility;

use Setup\Utility\Setup;
use Tools\TestSuite\TestCase;

class SetupTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$this->SetupLib = new Setup();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('Setup\Utility\Setup', $this->SetupLib);
	}

	/**
	 * SetupTest::testCleanedUrl()
	 *
	 * @return void
	 */
	public function testCleanedUrl() {
		$url = array('controller' => 'ControllerName', 'action' => 'action_name', '?' => array('clearcache' => 1, 'foo' => 'bar'));
		$result = Setup::cleanedUrl('clearcache', $url);
		$expected = array('controller' => 'ControllerName', 'action' => 'action_name', '?' => array('foo' => 'bar'));
		$this->assertSame($expected, $result);
	}

}
