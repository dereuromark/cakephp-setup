<?php

namespace Setup\Test\TestCase\Routing\Filter;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Setup\Routing\Filter\MaintenanceFilter;
use Tools\TestSuite\TestCase;

/**
 * Maintenance filter test case.
 */
class MaintenanceFilterTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
	}

/**
 * Tests that $response->checkNotModified() is called and bypasses
 * file dispatching
 *
 * @return void
 */
	public function testMaintenance() {
		$filter = new MaintenanceFilter();

		$response = $this->getMock('Cake\Network\Response', array('send'));
		$request = new Request('/');

		$event = new Event('DispatcherTest', $this, compact('request', 'response'));

		file_put_contents(TMP . 'maintenance.txt', time() + 2);

		$this->assertSame($response, $filter->beforeDispatch($event));
		$this->assertTrue($event->isStopped());

		unlink(TMP . 'maintenance.txt');
	}

/**
 * Test that no exceptions are thrown for //index.php type URLs.
 *
 * @return void
 */
	public function testNoMaintenance() {
		$filter = new MaintenanceFilter();

		$response = $this->getMock('Response', array('_sendHeader'));
		$request = new Request('/');
		$event = new Event('Dispatcher.beforeRequest', $this, compact('request', 'response'));

		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped());
	}

}
