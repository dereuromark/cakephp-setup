<?php
App::uses('Folder', 'Utility');
App::uses('AppShell', 'Console/Command');

if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0770);
}
if (!defined('TB')) {
	define('TB', "\t");
}

/**
 * A shell to to help automate testing in 2.x
 * - bake group test files
 * - assert that all classes are tested and if not create test files
 *
 * Please backup/commit any unsaved files before you start this shell
 *
 * Run it as `cake Setup.Tests`.
 *
 * @cakephp 2.x
 * @copyright 2011 Mark Scherer
 * @license MIT
 * @version 1.0
 */
class TestsShell extends AppShell {

	public $types = array(
		'Controller',
		'Model',
		'Component',
		'Helper',
		'Behavior',
		'Shell',
		'Task',
		'Lib',
		'Datasource',
	);

	// if specified (param deep [d]) it can match deep test folders (instead of flat ones)
	//TODO
	public $matches = array(
		'Helper' => 'View/Helper',
		'Behavior' => 'Model/Behavior',
		'Datasource' => 'Model/Datasource',
		'Task' => 'Shell/Task',
		'Shell' => 'Console/Command',
		'Component' => 'Controller/Component',
	);

	/**
	 * Analyse a specific file and its test to find out what methods are yet untested
	 *
	 * @return void
	 */
	public function analyze() {
	}

	/**
	 * Make sure all classes are tested
	 * use --create [-c] param to create missing ones
	 *
	 * @return void
	 */
	public function assert() {
		$fileList = array();
		foreach ($this->types as $type) {
			$pathType = $type;
			if (!empty($this->matches[$type])) {
				$pathType = $this->matches[$type];
			}
			$filePaths = App::path($pathType, $this->params['plugin']);
			foreach ($filePaths as $filePath) {
				$Folder = new Folder($filePath);
				$files = $Folder->find();
				foreach ($files as $file) {
					$fileList[$type][] = $file;
				}
			}
		}
		$this->_assert($fileList);
	}

	/**
	 * /Controller/Component
	 * /Conponent
	 * /components (1.3)
	 * if none exist, default to the default one again (first)
	 *
	 * @return string Path
	 */
	protected function _getTestPath($type, $testPath) {
		$defaultPath = !empty($this->matches[$type]) ? str_replace('/', DS, $this->matches[$type]) : $type;

		if (is_dir($testPath . $defaultPath)) {
			return $defaultPath;
		}
		if ($defaultPath != $type && is_dir($testPath . $type)) {
			return $type;
		}
		$path = strtolower(Inflector::pluralize($type));
		if (is_dir($testPath . $path)) {
			return $path;
		}
		return $defaultPath;
	}

	/**
	 * TestsShell::_assert()
	 *
	 * @param array $files
	 * @return void
	 */
	protected function _assert(array $files) {
		$testPath = $this->_path() . 'Case' . DS;

		foreach ($files as $type => $fileList) {
			$path = $this->_getTestPath($type, $testPath);
			$create = !empty($this->params['create']);

			foreach ($fileList as $key => $val) {
				$fileList[$key] = substr($val, 0, -4);
			}

			$Folder = new Folder($testPath . $path, $create, CHMOD_PUBLIC);
			$testFiles = $Folder->find();
			foreach ($testFiles as $key => $val) {
				$testFiles[$key] = substr($val, 0, -8);
			}

			$missing = array();
			$ok = array();
			$deleted = array();

			foreach ($fileList as $key => $file) {
				$excludeList = array('MyCakeTestCase');
				if (in_array($file, $excludeList)) {
					$ok[] = $file;
					continue;
				}

				if (!in_array($file, $testFiles)) {
					$missing[] = $file;
				} elseif ($this->_isEmptyTest($type, $file, $testPath . $path . DS)) {
					$this->out('Empty test: ' . $type . ' ' . $file);
					if (!empty($this->params['create'])) {
						$this->out('...Replacing test... ' . $file . 'Test');
						$this->_createTest($type, $file, $testPath . $path . DS);
					}
					$ok[] = $file;
				} else {
					$ok[] = $file;
				}
			}
			$deleted = $testFiles;
			foreach ($deleted as $key => $file) {
				if (in_array($file, $ok)) {
					unset($deleted[$key]);
				}
			}

			foreach ($missing as $file) {
				$this->out('Missing test: ' . $type . ' ' . $file);
				if (!empty($this->params['create'])) {
					$this->_createTest($type, $file, $testPath . $path . DS);
					$this->out('...Creating test... ' . $file . 'Test');
				}
			}

			if (empty($path)) {
				continue;
			}

			foreach ($deleted as $file) {
				$this->out('Unnecessary test (can be deleted): ' . $type . ' ' . $file);
				if (!empty($this->params['remove'])) {
					unlink($testPath . $path . DS . $file . 'Test.php');
					$this->out('...Deleting test... ' . $file . 'Test');
				}
			}
		}
	}

	/**
	 * Create test case file based on templates
	 * //TODO: make different templates per type
	 *
	 * @return bool Success
	 */
	protected function _isEmptyTest($type, $file, $path) {
		$fullPath = $path . $file . 'Test.php';
		$content = file_get_contents($fullPath);
		if (($count = substr_count($content, 'function test')) === 0 || $count === 1 && !empty($this->params['strict']) && strpos($content, 'function testObject') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * Create test case file based on templates
	 * //TODO: make different templates per type
	 *
	 * @return bool Success
	 */
	protected function _createTest($type, $class, $path) {

		$package = $type;
		if (!empty($this->matches[$type])) {
			$package = $this->matches[$package];
		}

		if (!empty($this->params['plugin'])) {
			$package = Inflector::camelize($this->params['plugin']) . '.' . $package;
		}

		$init = '$this->' . $class . ' = new ' . $class . '();';
		if ($type === 'Model') {
			$namespace = $class;
			if ($this->params['plugin']) {
				$namespace = $this->params['plugin'] . '.' . $class;
			}
			$init = '$this->' . $class . ' = ClassRegistry::init(\'' . $namespace . '\');';
		}

		$template = '<?php

App::uses(\'' . $class . '\', \'' . $package . '\');
App::uses(\'MyCakeTestCase\', \'Tools.TestSuite\');

/**
 * ' . $class . ' test case
 */
class ' . $class . 'Test extends MyCakeTestCase {

	public $' . $class . ';

	public function setUp() {
		parent::setUp();
		'.$init.'
	}

	public function testObject() {
		$this->assertInstanceOf(\'' . $class . '\', $this->' . $class . ');
	}

	//TODO

}';

		if (!is_dir($dir = dirname($fullPath = $path . $class . 'Test.php'))) {
			mkdir($dir, 0770, true);
		}
		return file_put_contents($fullPath, $template);
	}

	/**
	 * Bakes the group tests + the complete one-click group test
	 * will only create a group test file if the type contains at least one class to test
	 * tip: use -p * to bake all plugins at once
	 *
	 * @return void
	 */
	public function group() {
		if (!empty($this->params['plugin']) && $this->params['plugin'] === '*') {
			$plugins = CakePlugin::loaded();
			foreach ($plugins as $plugin) {
				$this->params['plugin'] = $plugin;
				foreach ($this->types as $type) {
					$this->_bake($type);
				}
				$this->_bake($this->types);
			}
			return;
		}
		foreach ($this->types as $type) {
			$this->_bake($type);
		}
		$this->_bake($this->types);
	}

	/**
	 * TestsShell::_bake()
	 *
	 * @param array $types
	 * @return void
	 */
	protected function _bake($types = array()) {
		$content = $this->_groupTemplate($types);
		$name = $this->_getName($types);
		$path = $this->_path() . 'Case' . DS;
		$file = 'All' . $name . 'Test.php';
		if (!empty($this->params['verbose'])) {
			$this->out('Checking ' . $this->_getScope() . '... ' . $file);
		}
		if (!$content) {
			if (!empty($this->params['remove']) && file_exists($path . $file)) {
				unlink($path . $file);
				$this->out('Removing unnecessary group ' . $this->_getScope() . '... ' . $file);
			}
			return;
		}

		file_put_contents($path . $file, $content);
		$this->out('Baking ' . $this->_getScope() . '... ' . $file);
	}

	/**
	 * TestsShell::_getScope()
	 *
	 * @return string
	 */
	protected function _getScope() {
		if (!empty($this->params['plugin'])) {
			return 'PLUGIN [' . Inflector::camelize($this->params['plugin']) . ']';
		}
		return 'APP';
	}

	/**
	 * TestsShell::_getName()
	 *
	 * @param array $types
	 * @return string
	 */
	protected function _getName($types = array()) {
		$types = (array)$types;

		if (count($types) > 1) {
			$name = 'App';
			if (!empty($this->params['plugin'])) {
				$name = Inflector::camelize($this->params['plugin']);
			}
		} else {
			$type = array_shift($types);
			$name = $type;
		}
		return $name;
	}

	/**
	 * - auto detects new (Helper) or or old (helpers) syntax for folders
	 * - includes only new one if present
	 * - expects flat test folder hierarchie (all folders in /Test/Case/)
	 */
	protected function _groupTemplate($testTypes = array()) {
		$testPath = $this->_path() . 'Case' . DS;
		$testTypes = (array)$testTypes;

		$types = array();
		foreach ($testTypes as $testType) {
			$path = $this->_getTestPath($testType, $testPath);

			if (!is_dir($path)) {
				$path = null;
			}
			if (empty($path)) {
				continue;
			}
			if (!$this->_containsTestFiles($testPath . $path)) {
				continue;
			}
			$types[$testType] = $path;
		}

		if (empty($types)) {
			return false;
		}

		$tests = array();
		foreach ($types as $type => $path) {
			$path = str_replace(DS, '\' . DS . \'', $path);
			$tests[] = '$Suite->addTestDirectory($path . DS . \'' . $path . '\');';
		}
		$name = $this->_getName($testTypes);

		$scope = !empty($this->params['plugin']) ? $this->params['plugin'] : 'app';

		$template = '<?php
/**
 * Group test - ' . $scope . '
 */
class All' . $name . 'Test extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite(\'All ' . $name . ' tests\');
		$path = dirname(__FILE__);
		' . implode(PHP_EOL . TB . TB, $tests) . '
		return $Suite;
	}
}
';
		return $template;
	}

	/**
	 * TestsShell::_path()
	 *
	 * @return string Path
	 */
	protected function _path() {
		if (!empty($this->params['plugin'])) {
			return App::pluginPath($this->params['plugin']) . 'Test' . DS;
		}
		return TESTS;
	}

	/**
	 * TestsShell::_containsTestFiles()
	 *
	 * @param mixed $path
	 * @return bool Success
	 */
	protected function _containsTestFiles($path) {
		$Folder = new Folder($path);
		$content = $Folder->find();
		return !empty($content);
	}

	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'plugin' => array(
					'short' => 'p',
					'help' => __d('cake_console', 'The plugin to bake group tests for. Only the specified plugin will be baked then.'),
					'default' => ''
				),
				'remove' => array(
					'short' => 'r',
					'boolean' => true,
					'help' => __d('cake_console', 'Remove unnecessary group tests.')
				),
				'strict' => array(
					'short' => 's',
					'boolean' => true,
					'help' => __d('cake_console', 'Count tests with only testObject as empty, as well.')
				),
			)
		);
		$subcommandParserAssert = $subcommandParser;
		$subcommandParserAssert['options']['create'] = array(
			'short' => 'c',
			'boolean' => true,
			'help' => __d('cake_console', 'Create missing folder and files.')
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "A shell to help automate testing in 2.x"))
			->addSubcommand('group', array(
				'help' => __d('cake_console', 'Bake GroupTest files'),
				'parser' => $subcommandParser
			))
			->addSubcommand('assert', array(
				'help' => __d('cake_console', 'Assert test files'),
				'parser' => $subcommandParserAssert
			))
			->addSubcommand('analyze', array(
				'help' => __d('cake_console', 'Analyse methods of a test file'),
				'parser' => $subcommandParserAssert
			));
	}

}
