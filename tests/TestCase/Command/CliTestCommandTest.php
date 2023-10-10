<?php

namespace Setup\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

class CliTestCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testBasic() {
		$this->exec('cli_test');
		$this->assertOutputContains('Router::url([\'controller\' => \'Test\'], true)');
		$this->assertOutputContains('/test');
	}

	/**
	 * @return void
	 */
	public function testPrefix() {
		$builder = Router::createRouteBuilder('/');
		$builder->setRouteClass(DashedRoute::class);
		$builder->scope('/', function (RouteBuilder $routes): void {
			$routes->fallbacks();
		});
		$builder->prefix('Admin', function (RouteBuilder $routes): void {
			$routes->fallbacks();
		});

		$this->exec('cli_test --prefix Admin');

		$this->assertOutputContains('/admin/test');
	}

}
