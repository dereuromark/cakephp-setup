<?php

namespace Setup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\TestSuite\TestCase;
use Setup\Shell\TestCliShell;
use Tools\TestSuite\ConsoleOutput;

class TestCliShellTest extends TestCase {

	/**
	 * @var \Setup\Shell\TestCliShell|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Shell;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('App.fullBaseUrl', 'example.local');

		$this->out = new ConsoleOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMockBuilder(TestCliShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();

		Router::defaultRouteClass(DashedRoute::class);
		Router::scope('/', function (RouteBuilder $routes) {
			$routes->fallbacks();
		});
		Router::prefix('Admin', function (RouteBuilder $routes) {
			$routes->fallbacks();
		});
	}

	/**
	 * tearDown
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset($this->Shell);

		Configure::delete('App.fullBaseUrl');
	}

	/**
	 * Test clean command
	 *
	 * @return void
	 */
	public function testRouter() {
		$this->Shell->runCommand(['router']);
		$output = $this->out->output();

		$this->assertTextContains('Router::url(\'/\')', $output, print_r($output, true));
		$this->assertTextContains('example.local/test', $output, print_r($output, true));
	}

	/**
	 * Test clean command
	 *
	 * @return void
	 */
	public function testRouterPrefix() {
		$this->Shell->runCommand(['router', '-x', 'Admin']);
		$output = $this->out->output();

		$this->assertTextContains('Router::url([\'controller\' => \'Test\', \'prefix\' => \'Admin\'], true)', $output, print_r($output, true));
		$this->assertTextContains('/admin/test', $output, print_r($output, true));
	}

}
