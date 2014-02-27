<?php
App::uses('AppShell', 'Console/Command');

/**
 * Test CLI for Router and alike.
 *
 * @author Mark Scherer
 * @license MIT
 */
class TestCliShell extends AppShell {

	/**
	 * Test that urls are generated properly.
	 *
	 * @return void
	 */
	public function router() {
		$url = Router::url('/');
		$this->out('Router::url(\'/\'): ' . NL . TB . $url);

		$url = Router::url(array('controller' => 'test'));
		$this->out('Router::url(array(\'controller\' => \'test\')): ' . NL . TB . $url);

		$url = Router::url('/', true);
		$this->out('Router::url(\'/\', true): ' . NL . TB . $url);

		$url = Router::url(array('controller' => 'test'), true);
		$this->out('Router::url(array(\'controller\' => \'test\'), true): ' . NL . TB . $url);
	}

	/**
	 * RimsShell::getOptionParser()
	 *
	 * @return
	 */
	public function getOptionParser() {
		return parent::getOptionParser()
			->description('Test CLI')
			->addSubcommand('router', array(
				'help' => 'Test router environment',
			));
	}

}
