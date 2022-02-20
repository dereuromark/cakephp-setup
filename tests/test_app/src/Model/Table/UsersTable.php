<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class UsersTable extends Table {

	/**
	 * @param array<string, mixed> $config
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->setDisplayField('username');
	}

}
