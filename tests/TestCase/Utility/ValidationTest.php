<?php

declare(strict_types=1);

namespace Setup\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Setup\Utility\Validation;

/**
 * @uses \Setup\Utility\Validation
 */
class ValidationTest extends TestCase {

	/**
	 * Test ipOrSubnet with valid IPv4 address
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithValidIpv4(): void {
		$this->assertTrue(Validation::ipOrSubnet('192.168.1.1'));
		$this->assertTrue(Validation::ipOrSubnet('10.0.0.1'));
		$this->assertTrue(Validation::ipOrSubnet('172.16.0.1'));
	}

	/**
	 * Test ipOrSubnet with valid IPv6 address
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithValidIpv6(): void {
		$this->assertTrue(Validation::ipOrSubnet('::1'));
		$this->assertTrue(Validation::ipOrSubnet('2001:db8::1'));
		$this->assertTrue(Validation::ipOrSubnet('fe80::1'));
	}

	/**
	 * Test ipOrSubnet with valid IPv4 subnet
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithValidIpv4Subnet(): void {
		$this->assertTrue(Validation::ipOrSubnet('192.168.1.0/24'));
		$this->assertTrue(Validation::ipOrSubnet('10.0.0.0/8'));
		$this->assertTrue(Validation::ipOrSubnet('192.168.0.0/32'));
		$this->assertTrue(Validation::ipOrSubnet('0.0.0.0/0'));
	}

	/**
	 * Test ipOrSubnet with valid IPv6 subnet
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithValidIpv6Subnet(): void {
		$this->assertTrue(Validation::ipOrSubnet('2001:db8::/32'));
		$this->assertTrue(Validation::ipOrSubnet('::1/128'));
		$this->assertTrue(Validation::ipOrSubnet('::/0'));
	}

	/**
	 * Test ipOrSubnet with invalid IPv4 subnet mask
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithInvalidIpv4Mask(): void {
		$this->assertFalse(Validation::ipOrSubnet('192.168.1.0/33'));
		$this->assertFalse(Validation::ipOrSubnet('192.168.1.0/-1'));
		$this->assertFalse(Validation::ipOrSubnet('192.168.1.0/abc'));
	}

	/**
	 * Test ipOrSubnet with invalid IPv6 subnet mask
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithInvalidIpv6Mask(): void {
		$this->assertFalse(Validation::ipOrSubnet('2001:db8::/129'));
	}

	/**
	 * Test ipOrSubnet with invalid IP address
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithInvalidIp(): void {
		$this->assertFalse(Validation::ipOrSubnet('256.256.256.256'));
		$this->assertFalse(Validation::ipOrSubnet('not-an-ip'));
		$this->assertFalse(Validation::ipOrSubnet(''));
	}

	/**
	 * Test ipOrSubnet with non-string value
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithNonString(): void {
		$this->assertFalse(Validation::ipOrSubnet(123));
		$this->assertFalse(Validation::ipOrSubnet(null));
		$this->assertFalse(Validation::ipOrSubnet(['192.168.1.1']));
	}

	/**
	 * Test ipOrSubnet with type parameter for IPv4 only
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithIpv4Type(): void {
		$this->assertTrue(Validation::ipOrSubnet('192.168.1.1', 'ipv4'));
		$this->assertTrue(Validation::ipOrSubnet('192.168.1.0/24', 'ipv4'));
		$this->assertFalse(Validation::ipOrSubnet('::1', 'ipv4'));
	}

	/**
	 * Test ipOrSubnet with type parameter for IPv6 only
	 *
	 * @return void
	 */
	public function testIpOrSubnetWithIpv6Type(): void {
		$this->assertTrue(Validation::ipOrSubnet('::1', 'ipv6'));
		$this->assertTrue(Validation::ipOrSubnet('2001:db8::/32', 'ipv6'));
		$this->assertFalse(Validation::ipOrSubnet('192.168.1.1', 'ipv6'));
	}

}
