<?php

namespace Setup\Test\TestCase\Middleware;

use InvalidArgumentException;
use Setup\Middleware\SecurityTxt;
use Shim\TestSuite\TestCase;

class SecurityTxtTest extends TestCase {

	/**
	 * @return void
	 */
	public function testToFieldsOmitsNullsAndOrdersContactFirst(): void {
		$document = new SecurityTxt(
			contact: 'mailto:security@example.com',
			canonical: 'https://example.com/.well-known/security.txt',
			preferredLanguages: 'en, de',
		);

		$fields = $document->toFields();

		$this->assertSame(
			['Contact', 'Canonical', 'Preferred-Languages'],
			array_keys($fields),
		);
		$this->assertArrayNotHasKey('Policy', $fields);
		$this->assertArrayNotHasKey('Expires', $fields);
	}

	/**
	 * @return void
	 */
	public function testToFieldsKeepsMultipleContacts(): void {
		$document = new SecurityTxt(
			contact: ['https://example.com/s', 'mailto:security@example.com'],
		);

		$this->assertSame(
			['https://example.com/s', 'mailto:security@example.com'],
			$document->toFields()['Contact'],
		);
	}

	/**
	 * @return void
	 */
	public function testThrowsOnEmptyContact(): void {
		$this->expectException(InvalidArgumentException::class);

		new SecurityTxt(contact: '');
	}

	/**
	 * @return void
	 */
	public function testThrowsOnBlankContactList(): void {
		$this->expectException(InvalidArgumentException::class);

		new SecurityTxt(contact: ['', '   ']);
	}

	/**
	 * @return void
	 */
	public function testNormalizeTrimsAndDropsEmpties(): void {
		$this->assertSame(['a', 'b'], SecurityTxt::normalize([' a ', '', 'b', '  ']));
		$this->assertSame(['x'], SecurityTxt::normalize('x'));
		$this->assertSame([], SecurityTxt::normalize(null));
	}

}
