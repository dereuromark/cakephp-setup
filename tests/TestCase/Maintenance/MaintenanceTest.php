<?php

namespace Setup\Test\TestCase\Maintenance;

use Setup\Maintenance\Maintenance;
use Shim\TestSuite\TestCase;

class MaintenanceTest extends TestCase {

	/**
	 * @var \Setup\Maintenance\Maintenance
	 */
	public $Maintenance;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Maintenance = new Maintenance();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->Maintenance->setMaintenanceMode(false);
		$this->Maintenance->clearWhitelist();
	}

	/**
	 * MaintenanceLibTest::testStatus()
	 *
	 * @return void
	 */
	public function testStatus() {
		$status = $this->Maintenance->isMaintenanceMode();
		$this->assertFalse($status);

		$this->Maintenance->setMaintenanceMode(0);
		$status = $this->Maintenance->isMaintenanceMode();
		$this->assertTrue($status);

		$this->Maintenance->setMaintenanceMode(1);
		$status = $this->Maintenance->isMaintenanceMode();
		$this->assertTrue($status);

		$content = file_get_contents(TMP . 'maintenance.txt');
		$this->assertWithinRange(time() + MINUTE, $content, 2);
	}

	/**
	 * MaintenanceLibTest::testWhitelist()
	 *
	 * @return void
	 */
	public function testWhitelist() {
		$result = $this->Maintenance->whitelist();
		$this->assertEmpty($result);

		$whitelist = ['192.168.0.1'];
		$result = $this->Maintenance->whitelist($whitelist);
		$this->assertTrue($result);

		$result = $this->Maintenance->whitelist();
		$this->assertNotEmpty($result);

		$result = $this->Maintenance->clearWhitelist(['192.111.111.111']);
		$this->assertTrue($result);
		$result = $this->Maintenance->whitelist();
		$this->assertSame($whitelist, $result);

		$result = $this->Maintenance->clearWhitelist();
		$result = $this->Maintenance->whitelist();
		$this->assertEmpty($result);
	}

}
