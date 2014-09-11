<?php
App::uses('Folder', 'Utility');
App::uses('AppShell', 'Console/Command');

/**
 * A shell to find malicious code.
 *
 * Run it as `cake Setup.MalCode run`.
 *
 * @copyright 2011 Mark Scherer
 * @license MIT
 */
class MalCodeShell extends AppShell {

	public $settings = array(
		'files' => array('php', 'ctp')
	);

	protected $_paths = array();

	protected $_files = array();

	protected $_issues = array();

	/**
	 * Main execution function to indent a folder recursivly
	 *
	 * @return void
	 */
	public function run() {
		$folder = APP;
		if (empty($this->args)) {
			$this->args[] = 'app';
		}
		if (!empty($this->args[0]) && $this->args[0] !== 'app') {
			$folder = $this->args[0];
			if ($folder === '/') {
				$folder = APP;
			}

			$folder = realpath($folder);
			if (!file_exists($folder)) {
				return $this->error('folder not exists: ' . $folder . '');
			}
			$this->_paths[] = $folder;
		} elseif ($this->args[0] === 'app') {
			$this->_paths[] = APP;
		}

		if (!empty($this->params['files'])) {
			$this->settings['files'] = explode(',', $this->params['files']);
		}

		$this->out('Folder:');
		$this->out(' - ' . $folder);
		$this->out('Searching ...');
		$this->_searchFiles();

		$this->out('Files found: ' . count($this->_files));
		$this->out('Processing ...');
		$this->_processFiles();

		$this->out('Issues: ' . count($this->_issues));
		if ($this->_issues) {
			$this->out('== MALICIOUS CODE FOUND ==');
		}
		foreach ($this->_issues as $file) {
			$this->out('- ' . $file);
		}
	}

	/**
	 * TestsShell::_processFiles()
	 *
	 * @return void
	 */
	protected function _processFiles() {
		foreach ($this->_files as $file) {
			if (!empty($this->params['verbose'])) {
				$this->out('- ' . $file);
			}
			$contents = file_get_contents($file);
			if (!empty($this->params['strict']) && preg_match('/\beval\(/i', $contents)) {
				$this->_issues[] = $file;
			} elseif (preg_match('/\beval\(base64/i', $contents) || preg_match('/\beval\($_/i', $contents)) {
				$this->_issues[] = $file;
			}
		}
	}


	/**
	 * Search files that may contain translateable strings
	 *
	 * @return void
	 */
	protected function _searchFiles() {
		foreach ($this->_paths as $path) {
			$Folder = new Folder($path);
			$files = $Folder->findRecursive('.*\.(' . implode('|', $this->settings['files']) . ')', true);
			foreach ($files as $file) {
				if (strpos($file, DS . 'Vendor' . DS) !== false) {
					continue;
				}
				$this->_files[] = $file;
			}
		}
	}

	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'strict' => array(
					'short' => 's',
					'help' => __d('cake_console', 'Be more strict.'),
					'boolean' => true
				),
				'log' => array(
					'short' => 'l',
					'help' => __d('cake_console', 'Log all ouput to file log.txt in TMP dir'),
					'boolean' => true
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "Check for malicious code."))
			->addSubcommand('run', array(
				'help' => __d('cake_console', 'Check for eval() used/abused.'),
				'parser' => $subcommandParser
			));
	}

}
