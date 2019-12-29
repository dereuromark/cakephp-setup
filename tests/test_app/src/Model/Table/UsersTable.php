<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class UsersTable extends Table {

	/**
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config) {
		$this->setDisplayField('username');
	}

}
