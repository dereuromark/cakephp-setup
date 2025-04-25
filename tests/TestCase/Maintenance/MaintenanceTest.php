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

		if (file_exists(TMP . 'maintenance.txt')) {
			unlink(TMP . 'maintenance.txt');
		}
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
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

		$this->Maintenance->setMaintenanceMode(true);
		$status = $this->Maintenance->isMaintenanceMode();
		$this->assertTrue($status);

		$this->assertFileExists(TMP . 'maintenance.txt');
	}

	/**
	 * @return void
	 */
	public function testWhitelist() {
		$result = $this->Maintenance->whitelist();
		$this->assertEmpty($result);

		$whitelist = ['192.168.0.1'];
		$this->Maintenance->addToWhitelist($whitelist);

		$result = $this->Maintenance->whitelist();
		$this->assertNotSame([], $result);

		$this->Maintenance->clearWhitelist(['192.111.111.111']);
		$result = $this->Maintenance->whitelist();

		$this->assertSame($whitelist, $result);

		$this->Maintenance->clearWhitelist();
		$result = $this->Maintenance->whitelist();
		$this->assertSame([], $result);
	}

	/**
	 * @return void
	 */
	public function testWhitelistSubnet() {
		$result = $this->Maintenance->whitelist();
		$this->assertEmpty($result);

		$whitelist = ['5.146.197.0/24'];
		$this->Maintenance->addToWhitelist($whitelist);

		$result = $this->Maintenance->whitelist();
		$this->assertNotEmpty($result);

		$result = $this->Maintenance->whitelisted('5.146.197.255');
		$this->assertTrue($result);

		$this->Maintenance->clearWhitelist(['5.146.197.0/24']);
		$result = $this->Maintenance->whitelist();
		$this->assertSame([], $result);

		$this->Maintenance->clearWhitelist();
		$result = $this->Maintenance->whitelist();
		$this->assertEmpty($result);
	}

}
