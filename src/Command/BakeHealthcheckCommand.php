<?php

namespace Setup\Command;

use Bake\Command\SimpleBakeCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;

/**
 * Command class for generating healthcheck files and their tests.
 */
class BakeHealthcheckCommand extends SimpleBakeCommand {

	/**
	 * Task name used in path generation.
	 *
	 * @var string
	 */
	public string $pathFragment = 'Healthcheck/Check/';

	/**
	 * @var string
	 */
	protected string $_name;

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'bake healthcheck';
	}

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|null
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$this->extractCommonProperties($args);

		$type = $args->getArgument('type');

		$name = $args->getArgument('name');
		if (!$name) {
			$io->err('You must provide a name to bake a ' . $this->name());
			$this->abort();
		}

		$name = $this->_getName($name);

		$this->_name = $type . '/' . $name . 'Check';

		$name = Inflector::camelize($name);
		$name = $type . '/' . $name;
		$this->bake($name, $args, $io);
		$this->bakeTest($name, $args, $io);

		return static::CODE_SUCCESS;
	}

	/**
	 * Generate a test case.
	 *
	 * @param string $name The class to bake a test for.
	 * @param \Cake\Console\Arguments $args The console arguments
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return void
	 */
	public function bakeTest(string $name, Arguments $args, ConsoleIo $io): void {
		if ($args->getOption('no-test')) {
			return;
		}

		$className = $name . 'Check';
		$io->out('Generating: ' . $className . ' test class');

		$plugin = (string)$args->getOption('plugin');
		$namespace = $plugin ? str_replace('/', DS, $plugin) : Configure::read('App.namespace');

		$content = $this->generateTaskTestContent($className, $namespace);
		$path = $plugin ? Plugin::path($plugin) : ROOT . DS;
		$path .= 'tests/TestCase/Healthcheck/Check/' . $className . 'Test.php';

		$io->createFile($path, $content, (bool)$args->getOption('force'));
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 *
	 * @return string
	 */
	protected function generateTaskTestContent(string $name, string $namespace): string {
		$testName = $name . 'Test';
		$subNamespace = '';
		$pos = strrpos($testName, '/');
		if ($pos !== false) {
			$subNamespace = '\\' . substr($testName, 0, $pos);
			$testName = substr($testName, $pos + 1);
		}
		$taskClassNamespace = $namespace . '\Healthcheck\\Check\\' . str_replace(DS, '\\', $name);

		if (strpos($name, '/') !== false) {
			$parts = explode('/', $name);
			$name = array_pop($parts);
		}

		$content = <<<TXT
<?php

namespace $namespace\Test\TestCase\Healthcheck\Check$subNamespace;

use Cake\TestSuite\TestCase;
use $taskClassNamespace;

class $testName extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		\$healthcheck = new $name();

		//TODO
		\$healthcheck->check();
		\$passed = \$healthcheck->passed();
		\$this->assertTrue(\$passed, implode('; ', \$healthcheck->failureMessage()));
	}

}

TXT;

		return $content;
	}

	/**
	 * @inheritDoc
	 */
	public function template(): string {
		return 'Setup.Healthcheck/check';
	}

	/**
	 * @inheritDoc
	 */
	public function templateData(Arguments $arguments): array {
		$name = $this->_name;
		$namespace = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$namespace = $this->_pluginNamespace($this->plugin);
			$pluginPath = $this->plugin . '.';
		}

		$namespace .= '\\Healthcheck\\Check';

		$namespacePart = null;
		if (strpos($name, '/') !== false) {
			$parts = explode('/', $name);
			$name = array_pop($parts);
			$namespacePart = implode('\\', $parts);
		}
		if ($namespacePart) {
			$namespace .= '\\' . $namespacePart;
		}

		return [
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath,
			'namespace' => $namespace,
			'subNamespace' => $namespacePart ? ($namespacePart . '/') : '',
			'name' => $name,
			'add' => $arguments->getOption('add'),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function name(): string {
		return 'healthcheck';
	}

	/**
	 * @inheritDoc
	 */
	public function fileName(string $name): string {
		return $name . 'Check.php';
	}

	/**
	 * Gets the option parser instance and configures it.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser Parser instance
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser->addArgument('type', [
			'help' => 'Type/Group',
			'required' => true,
		]);
		$parser = parent::buildOptionParser($parser);

		return $parser;
	}

}
