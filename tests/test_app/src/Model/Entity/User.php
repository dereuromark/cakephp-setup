<?php

namespace TestApp\Model\Entity;

use Tools\Model\Entity\Entity;

/**
 * @property string $password
 */
class User extends Entity {

	/**
	 * @param int|array|null $value
	 * @return string|array
	 */
	public static function statuses($value = null) {
		$options = [
			static::STATUS_INACTIVE => 'Inactive',
			static::STATUS_ACTIVE => 'Active',
		];

		return parent::enum($value, $options);
	}

	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;

}
