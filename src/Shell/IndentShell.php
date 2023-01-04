<?php

namespace Setup\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Utility\Text;
use Shim\Filesystem\Folder;

if (!defined('TB')) {
	define('TB', "\t");
}
if (!defined('NL')) {
	define('NL', "\n");
}
if (!defined('CR')) {
	define('CR', "\r");
}

/**
 * Indent Shell
 *
 * Correct indentation of files in a folder recursively.
 * Useful if files contain either only spaces or even a mixture of spaces and tabs.
 * It can be a bitch to get this straightened out. Mix in a mixture of different space
 * lengths and it is a nightmare.
 * Using IDE specific beautifier is not always an option, either. They usually reformat
 * arrays and other things in a way you don't want. No matter how hard you try to set it
 * up correctly.
 *
 * This addresses the issue in a clean way and only modifies whitespace at the beginning
 * of a line.
 * Single "accidental" spaces will be filtered out automatically.
 *
 * Tip: For different space lengths use multiple times from largest to smallest length.
 * E.g "-s 8", then "-s 4" and maybe even "-s 2".
 *
 * Oh, and: Use TABS for indentation of code - ALWAYS.
 *
 * @author Mark Scherer
 * @license MIT
 */
class IndentShell extends Shell {

	/**
	 * @var array
	 */
	public $settings = [
		'files' => ['php', 'ctp', 'inc', 'tpl'],
		'againWithHalf' => false, # if 4, go again with 2 afterwards
		'outputToTmp' => false, # write to filename_.ext
		'debug' => false, # add debug info after each line
	];

	/**
	 * @var bool|null
	 */
	protected $_changes;

	/**
	 * @var array
	 */
	protected array $_paths = [];

	/**
	 * @var array
	 */
	protected array $_files = [];

	/**
	 * Main execution function to indent a folder recursivly
	 *
	 * @return int|null|void
	 */
	public function folder() {
		if (!empty($this->params['extensions'])) {
			$this->settings['files'] = Text::tokenize($this->params['extensions']);
		}
		if (!empty($this->params['again'])) {
			$this->settings['againWithHalf'] = true;
		}

		$folder = null;
		if ($this->args) {
			if (!empty($this->args[0]) && $this->args[0] !== 'app') {
				$folder = $this->args[0];
				if ($folder === '/') {
					$folder = APP;
				}

				$folder = realpath($folder);
				if (!file_exists($folder)) {
					$this->abort('folder not exists: ' . $folder . '');
				}
				$this->_paths[] = $folder;
			} elseif ($this->args[0] === 'app') {
				$this->_paths[] = APP;
			}

			if (!empty($this->params['files'])) {
				$this->settings['files'] = explode(',', $this->params['files']);
			}

			$this->out($folder);
			$this->out('searching...');
			$this->_searchFiles();

			$this->out('found: ' . count($this->_files));
			if (empty($this->params['dry-run'])) {
				if (!$this->params['force']) {
					$continue = $this->in('Modifying files! Continue?', ['y', 'n'], 'n');
					if (mb_strtolower($continue) !== 'y' && mb_strtolower($continue) !== 'yes') {
						$this->abort('...aborted');
					}
				}

				$this->_correctFiles();
				$this->out('DONE');
			}

		} else {
			$this->out('Usage: cake intend folder');
			$this->out('"folder" is then intended recursivly');
			$this->out('default file types are');
			$this->out('[' . implode(', ', $this->settings['files']) . ']');

			$this->out('');
			$this->out('Specify file types manually:');
			$this->out('-files php,js,css');
		}
	}

	/**
	 * @param string $file
	 * @param array<string> $texts
	 * @return bool Success
	 */
	protected function _write($file, $texts) {
		$text = implode(PHP_EOL, $texts);
		if ($this->settings['outputToTmp']) {
			$filename = pathinfo($file, PATHINFO_FILENAME);
			if (mb_substr($filename, -1, 1) === '_') {
				return false;
			}
			$file = pathinfo($file, PATHINFO_DIRNAME) . DS . $filename . '_.' . pathinfo($file, PATHINFO_EXTENSION);
		}

		return (bool)file_put_contents($file, $text);
	}

	/**
	 * IndentShell::_read()
	 *
	 * @param string $file
	 * @return array
	 */
	protected function _read($file) {
		$text = file_get_contents($file);
		if (empty($text)) {
			return [];
		}
		$pieces = explode(NL, $text);

		return $pieces;
	}

	/**
	 * NEW TRY!
	 * idea: just count spaces and replace those
	 *
	 * @return void
	 */
	protected function _correctFiles() {
		foreach ($this->_files as $file) {
			$this->_changes = false;
			$textCorrect = [];

			$pieces = $this->_read($file);
			$spacesPerTab = $this->params['spaces'];

			foreach ($pieces as $piece) {
				$tmp = $this->_process($piece, $spacesPerTab);
				if ($this->settings['againWithHalf'] && $spacesPerTab % 2 === 0 && $spacesPerTab > 3) {
					$tmp = $this->_process($tmp, $spacesPerTab / 2);
				}
				$tmp = $this->_processSpaceErrors($tmp, 1);
				$textCorrect[] = $tmp;
			}

			if ($this->_changes) {
				$this->_write($file, $textCorrect);
			}
		}
	}

	/**
	 * @param string $piece
	 * @param int $spacesPerTab
	 * @return string
	 */
	protected function _process($piece, $spacesPerTab) {
		$pos = -1;
		$spaces = $mod = $tabs = 0;
		$debug = '';

		$newPiece = $piece;
		if ($spacesPerTab) {
			//TODO
			while (mb_substr($piece, $pos + 1, 1) === ' ' || mb_substr($piece, $pos + 1, 1) === TB) {
				$pos++;
			}
			$piece1 = mb_substr($piece, 0, $pos + 1);
			$piece1 = str_replace(str_repeat(' ', $spacesPerTab), TB, $piece1, $count);
			if ($count > 0) {
				$this->_changes = true;
			}

			$piece2 = mb_substr($piece, $pos + 1);

			$newPiece = $piece1 . $piece2;
		}

		$newPiece = rtrim($newPiece) . $debug;
		if ($newPiece != $piece || strlen($newPiece) !== strlen($piece)) {
			$this->_changes = true;
		}

		return $newPiece;
	}

	/**
	 * NEW TRY!
	 * idea: hardcoded replaceing
	 *
	 * @param string $piece
	 * @param int $space
	 * @return string
	 */
	protected function _processSpaceErrors($piece, $space = 1) {
		$newPiece = $piece;
		$spaceChar = str_repeat(' ', $space);

		// At the beginning of the line
		if (mb_substr($newPiece, 0, $space) === $spaceChar && mb_substr($newPiece, $space, 1) === TB) {
			$newPiece = mb_substr($newPiece, $space);
		}
		// In the middle
		$pos = mb_strpos($newPiece, (string)$space);
		if ($pos > 0 && mb_substr($newPiece, $pos - 1, 1) === TB
			&& mb_substr($newPiece, $pos + 1, 1) === TB) {
			$newPiece = mb_substr($newPiece, $pos) . mb_substr($newPiece, $pos + 2);
		}
		$newPiece = str_replace(TB . $spaceChar . TB, TB . TB, $newPiece);
		if ($newPiece !== $piece) {
			$this->_changes = true;
		}

		return $newPiece;
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

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the update, no files will actually be modified.',
					'boolean' => true,
				],
				'log' => [
					'short' => 'l',
					'help' => 'Log all ouput to file log.txt in TMP dir.',
					'boolean' => true,
				],
				'force' => [
					'short' => 'f',
					'help' => 'Force without confirmation prompting.',
					'boolean' => true,
				],
				'spaces' => [
					'short' => 's',
					'help' => 'Spaces per Tab.',
					'default' => '4',
				],
				'extensions' => [
					'short' => 'e',
					'help' => 'Extensions (comma-separated).',
					'default' => '',
				],
				'again' => [
					'short' => 'a',
					'help' => 'Again (with half) afterwards.',
					'boolean' => true,
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('Correct indentation of files.')
			->addSubcommand('folder', [
				'help' => 'Indent all files in a folder.',
				'parser' => $subcommandParser,
			]);
	}

}
