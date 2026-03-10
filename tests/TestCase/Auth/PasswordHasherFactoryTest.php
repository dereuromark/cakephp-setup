<?php

declare(strict_types=1);

namespace Setup\Test\TestCase\Auth;

use Cake\TestSuite\TestCase;
use RuntimeException;
use Setup\Auth\AbstractPasswordHasher;
use Setup\Auth\DefaultPasswordHasher;
use Setup\Auth\PasswordHasherFactory;

/**
 * @uses \Setup\Auth\PasswordHasherFactory
 */
class PasswordHasherFactoryTest extends TestCase {

	/**
	 * Test build with string class name
	 *
	 * @return void
	 */
	public function testBuildWithString(): void {
		$hasher = PasswordHasherFactory::build('Default');

		$this->assertInstanceOf(DefaultPasswordHasher::class, $hasher);
		$this->assertInstanceOf(AbstractPasswordHasher::class, $hasher);
	}

	/**
	 * Test build with array configuration
	 *
	 * @return void
	 */
	public function testBuildWithArray(): void {
		$hasher = PasswordHasherFactory::build([
			'className' => 'Default',
			'hashType' => PASSWORD_BCRYPT,
		]);

		$this->assertInstanceOf(DefaultPasswordHasher::class, $hasher);
	}

	/**
	 * Test build throws exception for non-existent class
	 *
	 * @return void
	 */
	public function testBuildWithNonExistentClass(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Password hasher class "NonExistent" was not found.');

		PasswordHasherFactory::build('NonExistent');
	}

}
