<?php

namespace TestApp\Model\Entity;

use Tools\Model\Entity\Entity;

/**
 * @property string $password
 */
class User extends Entity {

	/**
	 * @param array|int|null $value
	 * @return array|string
	 */
	public static function statuses($value = null) {
		$options = [
			static::STATUS_INACTIVE => 'Inactive',
			static::STATUS_ACTIVE => 'Active',
		];

		return parent::enum($value, $options);
	}

	/**
	 * @var int
	 */
	public const STATUS_INACTIVE = 0;

	/**
	 * @var int
	 */
	public const STATUS_ACTIVE = 1;

}
