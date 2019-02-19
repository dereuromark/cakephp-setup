<?php
namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestCase;

class BackendControllerTest extends IntegrationTestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		//'app.Users',
	];

	/**
	 * @return void
	 */
	public function testPhpinfo() {
		$this->session(['Auth' => ['User' => ['id' => 1]]]);

		$this->get(['prefix' => 'admin', 'plugin' => 'Setup', 'controller' => 'Backend', 'action' => 'phpinfo']);

		$this->assertResponseCode(200);
	}

}
