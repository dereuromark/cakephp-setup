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

		$this->assertTrue($this->Maintenance->setMaintenanceMode(true));
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

	public function testIpv6SubnetWhitelist(): void {
		$this->Maintenance->addToWhitelist(['2001:db8:abcd:1200::/56']);
		$this->assertTrue($this->Maintenance->whitelisted('2001:db8:abcd:12ff::1'));
		$this->assertFalse($this->Maintenance->whitelisted('2001:db8:abcd:1300::1'));
		$this->assertFalse($this->Maintenance->whitelisted('192.0.2.1'));
	}

	public function testInvalidSubnetDoesNotGrantAccess(): void {
		foreach (['192.0.2.0/33', '192.0.2.0/1.5', '192.0.2.0/1e1', '::/129'] as $range) {
			$this->Maintenance->addToWhitelist([$range]);
		}
		$this->assertFalse($this->Maintenance->whitelisted('192.0.2.1'));
		$this->assertFalse($this->Maintenance->whitelisted('::1'));
	}

	public function testWhitelistAddsAddressContainedInAnotherAddress(): void {
		$this->Maintenance->addToWhitelist(['192.0.2.10', '192.0.2.1']);
		$this->assertTrue($this->Maintenance->whitelisted('192.0.2.1'));
	}

	public function testExactIpv6WhitelistNormalizesAddress(): void {
		$this->Maintenance->addToWhitelist(['2001:0DB8:0000:0000:0000:0000:0000:0001']);
		$this->assertTrue($this->Maintenance->whitelisted('2001:db8::1'));
		$this->assertFalse($this->Maintenance->whitelisted('2001:db8::2'));
	}

}
