<?php
namespace Setup\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class UserFixture
 */
class UsersFixture extends TestFixture {

	/**
	 * fields property
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'username' => ['type' => 'string', 'null' => true],
		'email' => ['type' => 'string', 'null' => true],
		'password' => ['type' => 'string', 'null' => true],
		'created' => ['type' => 'timestamp', 'null' => true],
		'updated' => ['type' => 'timestamp', 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * records property
	 *
	 * @var array
	 */
	public $records = [
		[
			'username' => 'mariano',
			'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
			'email' => 'example@example.org',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31',
		],
	];

}
