<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class UsersTable extends Table {

	public function initialize(array $config) {
		$this->displayField('username');
	}

}
