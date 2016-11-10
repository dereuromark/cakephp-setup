<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Core\Plugin;

/**
 * @author Mark Scherer
 * @license MIT
 */
class MailmapShell extends Shell {

	/**
	 * @var array
	 */
	protected $map = [];

	/**
	 * Generates .mailmap file.
	 *
	 * @param string|null $path
	 * @return void
	 */
	public function generate($path = null) {
		$path = $this->getPath($path);

		$this->out('Reading ' . $path . '.mailmap');

		$existingMap = $this->parseMailmap($path);

		$this->out('Found ' . count($this->map) . ' existing entries');

		$rows = $this->parseHistory($path, $existingMap);

		$array = [];
		foreach ($rows as $row) {
			$key = strtolower($row['email']);

			$array[$key][] = $row;
		}

		$map = $this->map;

		foreach ($array as $elements) {
			if (count($elements) < 2) {
				continue;
			}

			$primary = array_shift($elements);
			$map[] = $primary['content'];
			foreach ($elements as $element) {
				$map[] = $primary['content'] . ' ' . $element['content'];
			}
		}

		$file = $path . '.mailmap';
		if ($this->params['dry-run']) {
			$file = TMP . '.mailmap';
		}

		file_put_contents($file, implode(PHP_EOL, $map));
		$this->out((count($map) - count($this->map)) . ' additional rows written to ' . $file);
	}

	/**
	 * @param string|null $path
	 * @return string
	 */
	protected function getPath($path = null) {
		if ($path === null) {
			$path = ROOT . DS;
		} elseif ($path === 'core') {
			$path = CORE_PATH;
		} elseif (Plugin::loaded($path)) {
			$path = Plugin::path($path);
		}

		return $path;
	}

	/**
	 * @param string $folder
	 * @return array
	 * @throws \Exception
	 */
	protected function parseMailmap($folder) {
		$file = $folder . '.mailmap';
		if (!file_exists($file)) {
			return [];
		}

		$content = file_get_contents($file);
		$content = explode(PHP_EOL, $content);
		$this->map = array_filter($content);

		$array = [];
		foreach ($content as $row) {
			if (trim($row) === '') {
				continue;
			}

			preg_match('/^.+?\<(.+?)\>/', $row, $matches);
			if (!$matches) {
				throw new \Exception($row);
			}

			$key = strtolower($matches[1]);

			$array[$key][] = $row;
		}

		return $array;
	}

	/**
	 * @param string $folder
	 * @param array $existingMap
	 * @return array
	 * @throws \Exception
	 */
	protected function parseHistory($folder, array $existingMap) {
		exec('cd ' . $folder . ' && git shortlog -sne', $output);

		$array = [];
		foreach ($output as $row) {
			preg_match('/^\s*[0-9]+\s+(.+)$/', $row, $matches);
			if (!$matches) {
				throw new \Exception($row);
			}
			$content = $matches[1];

			preg_match('/^(.+) \<(.+)\>$/', $content, $matches);
			if (!$matches) {
				throw new \Exception($content);
			}
			$name = $matches[1];
			$email = $matches[2];

			//TODO: improve with also name and others
			if (isset($existingMap[strtolower($email)])) {
				continue;
			}

			$array[] = [
				'content' =>  $content,
				'name' => $name,
				'email' => $email
			];
		}

		return $array;
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry-runs the command, saves result into TMP/.mailmap file',
					'boolean' => true
				]
			],
			'arguments' => [
				'path' => [
					'name' => 'path',
					'help' => 'Path, plugin name or "core" for CakePHP core, defaults to app root',
				]
			]
		];

		return parent::getOptionParser()
			->description('The Mailmap Shell generates a mailmap file')
			->addSubcommand('generate', [
				'help' => 'Generate',
				'parser' => $subcommandParser
			]);
	}

}
