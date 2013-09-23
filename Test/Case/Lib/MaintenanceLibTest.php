<?php
App::uses('MaintenanceLib', 'Setup.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MaintenanceLibTest extends MyCakeTestCase {

	public $Maintenance;

	public function setUp() {
		parent::setUp();

		$this->Maintenance = new MaintenanceLib();
	}

	public function tearDown() {
		parent::tearDown();

		$this->Maintenance->setMaintenanceMode(false);
		$this->Maintenance->clearWhitelist();
	}

	public function testMaintenanceLib() {
		$this->assertInstanceOf('MaintenanceLib', $this->Maintenance);
	}

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
		$this->assertWithinMargin($content, time() + MINUTE, 2);
	}

	public function testWhitelist() {
		$result = $this->Maintenance->whitelist();
		$this->assertEmpty($result);

		$whitelist = array('192.168.0.1');
		$result = $this->Maintenance->whitelist($whitelist);
		$this->assertTrue($result);

		$result = $this->Maintenance->whitelist();
		$this->assertNotEmpty($result);

		$result = $this->Maintenance->clearWhitelist(array('192.111.111.111'));
		$this->assertTrue($result);
		$result = $this->Maintenance->whitelist();
		$this->assertSame($whitelist, $result);

		$result = $this->Maintenance->clearWhitelist();
		$result = $this->Maintenance->whitelist();
		$this->assertEmpty($result);
	}

}
