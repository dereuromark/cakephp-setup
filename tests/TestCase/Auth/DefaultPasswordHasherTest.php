<?php

declare(strict_types=1);

namespace Setup\Test\TestCase\Auth;

use Cake\TestSuite\TestCase;
use Setup\Auth\DefaultPasswordHasher;

/**
 * @uses \Setup\Auth\DefaultPasswordHasher
 */
class DefaultPasswordHasherTest extends TestCase {

	protected DefaultPasswordHasher $hasher;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->hasher = new DefaultPasswordHasher();
	}

	/**
	 * Test hash generates a valid hash
	 *
	 * @return void
	 */
	public function testHash(): void {
		$password = 'secret123';
		$hash = $this->hasher->hash($password);

		$this->assertNotEmpty($hash);
		$this->assertNotSame($password, $hash);
		$this->assertTrue(password_verify($password, $hash));
	}

	/**
	 * Test check verifies correct password
	 *
	 * @return void
	 */
	public function testCheckWithCorrectPassword(): void {
		$password = 'secret123';
		$hash = $this->hasher->hash($password);

		$this->assertTrue($this->hasher->check($password, $hash));
	}

	/**
	 * Test check rejects incorrect password
	 *
	 * @return void
	 */
	public function testCheckWithIncorrectPassword(): void {
		$password = 'secret123';
		$hash = $this->hasher->hash($password);

		$this->assertFalse($this->hasher->check('wrongpassword', $hash));
	}

	/**
	 * Test needsRehash returns false for recently hashed password
	 *
	 * @return void
	 */
	public function testNeedsRehashWithCurrentHash(): void {
		$password = 'secret123';
		$hash = $this->hasher->hash($password);

		$this->assertFalse($this->hasher->needsRehash($hash));
	}

	/**
	 * Test needsRehash returns true for weak hash
	 *
	 * @return void
	 */
	public function testNeedsRehashWithWeakHash(): void {
		// MD5 hash (very weak, should need rehash)
		$weakHash = md5('secret123');

		$this->assertTrue($this->hasher->needsRehash($weakHash));
	}

	/**
	 * Test constructor with custom config
	 *
	 * @return void
	 */
	public function testConstructorWithConfig(): void {
		$hasher = new DefaultPasswordHasher([
			'hashType' => PASSWORD_BCRYPT,
			'hashOptions' => ['cost' => 10],
		]);

		$password = 'secret123';
		$hash = $hasher->hash($password);

		$this->assertTrue($hasher->check($password, $hash));
	}

	/**
	 * Test different hash types produce valid hashes
	 *
	 * @return void
	 */
	public function testHashWithBcrypt(): void {
		$hasher = new DefaultPasswordHasher([
			'hashType' => PASSWORD_BCRYPT,
		]);

		$password = 'secret123';
		$hash = $hasher->hash($password);

		$this->assertNotEmpty($hash);
		$this->assertTrue($hasher->check($password, $hash));
	}

}
