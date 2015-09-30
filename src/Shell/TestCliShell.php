<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Routing\Router;

/**
 * Test CLI for Router and alike.
 *
 * @author Mark Scherer
 * @license MIT
 */
class TestCliShell extends Shell {

	/**
	 * Test that urls are generated properly.
	 *
	 * @return void
	 */
	public function router() {
		//TODO: opt in plugin and prefix

		$url = Router::url('/');
		$this->out('Router::url(\'/\'): ' . PHP_EOL . "\t" . $url);

		$url = Router::url(['controller' => 'Test']);
		$this->out('Router::url(array(\'controller\' => \'test\')): ' . PHP_EOL . "\t" . $url);

		$url = Router::url('/', true);
		$this->out('Router::url(\'/\', true): ' . PHP_EOL . "\t" . $url);

		$url = Router::url(['controller' => 'Test'], true);
		$this->out('Router::url(array(\'controller\' => \'test\'), true): ' . PHP_EOL . "\t" . $url);
	}

	/**
	 * RimsShell::getOptionParser()
	 *
	 * @return
	 */
	public function getOptionParser() {
		return parent::getOptionParser()
			->description('Test CLI')
			->addSubcommand('router', [
				'help' => 'Test router environment',
			]);
	}

}
