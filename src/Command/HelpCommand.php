<?php

namespace Setup\Command;

use ArrayIterator;
use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\CommandCollection;
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use SimpleXMLElement;

/**
 * Compact Help Command
 *
 * Provides a super-compact command list with bracket notation for subcommands.
 * Replaces core help command when Setup.compactHelp config is enabled.
 */
class HelpCommand extends BaseCommand implements CommandCollectionAwareInterface {

	/**
	 * The command collection to get help on.
	 *
	 * @var \Cake\Console\CommandCollection
	 */
	protected CommandCollection $commands;

	/**
	 * @inheritDoc
	 */
	public function setCommandCollection(CommandCollection $commands): void {
		$this->commands = $commands;
	}

	/**
	 * Main function Prints out the list of commands.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$commands = $this->commands->getIterator();
		if ($commands instanceof ArrayIterator) {
			$commands->ksort();
		}

		// Filter by command prefix if provided
		$filter = $args->getArgument('command');
		if ($filter) {
			$commands = $this->filterByPrefix($commands, $filter);
		}

		if ($args->getOption('xml')) {
			$this->asXml($io, $commands);

			return static::CODE_SUCCESS;
		}

		$verbose = $io->level() >= ConsoleIo::VERBOSE;
		$this->asText($io, $commands, $verbose);

		return static::CODE_SUCCESS;
	}

	/**
	 * Filter commands by prefix.
	 *
	 * @param iterable<string, string|object> $commands The command collection.
	 * @param string $prefix The prefix to filter by.
	 * @return array<string, string|object> Filtered commands.
	 */
	protected function filterByPrefix(iterable $commands, string $prefix): array {
		$filtered = [];
		foreach ($commands as $name => $class) {
			if (str_starts_with($name, $prefix . ' ') || $name === $prefix) {
				$filtered[$name] = $class;
			}
		}

		return $filtered;
	}

	/**
	 * Output text.
	 *
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @param iterable<string, string|object> $commands The command collection to output.
	 * @param bool $verbose Whether to show verbose output with descriptions.
	 * @return void
	 */
	protected function asText(ConsoleIo $io, iterable $commands, bool $verbose = false): void {
		$invert = [];
		foreach ($commands as $name => $class) {
			if (is_object($class)) {
				$class = $class::class;
			}
			$invert[$class] ??= [];
			$invert[$class][] = $name;
		}

		$commandList = [];
		foreach ($invert as $class => $names) {
			preg_match('/^(.+)\\\\Command\\\\/', $class, $matches);
			if (!$matches) {
				continue;
			}
			$shortestName = $this->getShortestName($names);
			if (str_contains($shortestName, '.')) {
				[, $shortestName] = explode('.', $shortestName, 2);
			}

			$commandList[] = [
				'name' => $shortestName,
				'description' => is_subclass_of($class, BaseCommand::class) ? $class::getDescription() : '',
			];
		}
		sort($commandList);

		if ($verbose) {
			$this->outputPaths($io);
			$this->outputGrouped($io, $invert);
		} else {
			$io->out('<info>Available Commands:</info>', 2);
			$this->outputCompactCommands($io, $commandList);
			$io->out('');
		}

		$root = $this->getRootName();
		$io->out("To run a command, type <info>`{$root} command_name [args|options]`</info>");
		$io->out("To get help on a specific command, type <info>`{$root} command_name --help`</info>");
		if (!$verbose) {
			$io->out("To see full descriptions and plugin grouping, use <info>`{$root} --help -v`</info>", 2);
		} else {
			$io->out('', 2);
		}
	}

	/**
	 * Output commands grouped by plugin/namespace (verbose mode).
	 *
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @param array<string, array<string>> $invert Inverted command map (class => names).
	 * @return void
	 */
	protected function outputGrouped(ConsoleIo $io, array $invert): void {
		$grouped = [];
		$plugins = Plugin::loaded();
		foreach ($invert as $class => $names) {
			preg_match('/^(.+)\\\\Command\\\\/', $class, $matches);
			if (!$matches || $names === []) {
				continue;
			}
			$namespace = str_replace('\\', '/', $matches[1]);
			$prefix = 'app';
			if ($namespace === 'Cake') {
				$prefix = 'cakephp';
			} elseif (method_exists($class, 'getGroup')) {
				$prefix = $class::getGroup();
			} elseif (in_array($namespace, $plugins, true)) {
				$prefix = Inflector::underscore($namespace);
			}
			$shortestName = $this->getShortestName($names);
			if (str_contains($shortestName, '.')) {
				[, $shortestName] = explode('.', $shortestName, 2);
			}

			$grouped[$prefix][] = [
				'name' => $shortestName,
				'description' => is_subclass_of($class, BaseCommand::class) ? $class::getDescription() : '',
			];
		}
		ksort($grouped);

		if (isset($grouped['app'])) {
			$app = $grouped['app'];
			unset($grouped['app']);
			$grouped = ['app' => $app] + $grouped;
		}

		$io->out('<info>Available Commands:</info>', 2);
		foreach ($grouped as $prefix => $names) {
			$io->out("<info>{$prefix}</info>:");
			sort($names);
			foreach ($names as $data) {
				$io->out(' - ' . $data['name']);
				if ($data['description']) {
					$io->info(str_pad(" \u{2514}", 13, "\u{2500}") . ' ' . $data['description']);
				}
			}
			$io->out('');
		}
	}

	/**
	 * Output commands in super-compact bracket format.
	 *
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @param array<array{name: string, description: string}> $commands List of commands.
	 * @return void
	 */
	protected function outputCompactCommands(ConsoleIo $io, array $commands): void {
		$maxWidth = $this->getTerminalWidth();

		// Group commands by their first word (prefix)
		$baseCommands = [];
		foreach ($commands as $data) {
			$name = $data['name'];
			$parts = explode(' ', $name, 2);
			$base = $parts[0];
			$sub = $parts[1] ?? null;

			$baseCommands[$base] ??= [];
			if ($sub !== null) {
				$baseCommands[$base][] = $sub;
			}
		}

		// Output each base command with subcommands in brackets
		foreach ($baseCommands as $base => $subs) {
			if ($subs === []) {
				$io->out(' - ' . $base);

				continue;
			}

			// Group subs by their first word to detect nesting
			$subGroups = [];
			foreach ($subs as $sub) {
				$subParts = explode(' ', $sub, 2);
				$subBase = $subParts[0];
				$subSub = $subParts[1] ?? null;

				$subGroups[$subBase] ??= [];
				if ($subSub !== null) {
					$subGroups[$subBase][] = $subSub;
				}
			}

			// Collect standalone subs (no nested commands)
			$standaloneSubs = [];
			foreach ($subGroups as $subBase => $subSubs) {
				if ($subSubs === []) {
					$standaloneSubs[] = $subBase;
				}
			}

			// Output main command with standalone subs
			if ($standaloneSubs !== []) {
				$this->outputWrappedCommand($io, ' - ' . $base . ' ', $standaloneSubs, $maxWidth);
			}

			// Output nested subcommands separately
			foreach ($subGroups as $subBase => $subSubs) {
				if ($subSubs !== []) {
					$this->outputWrappedCommand($io, ' - ' . $base . ' ' . $subBase . ' ', $subSubs, $maxWidth);
				}
			}
		}
	}

	/**
	 * Output a command with its subcommands in bracket notation.
	 *
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @param string $prefix The command prefix (e.g., " - bake ")
	 * @param array<string> $subcommands List of subcommand names
	 * @param int $maxWidth Maximum line width
	 * @return void
	 */
	protected function outputWrappedCommand(
		ConsoleIo $io,
		string $prefix,
		array $subcommands,
		int $maxWidth,
	): void {
		$indent = str_repeat(' ', strlen($prefix));
		$line = $prefix . '[';
		$first = true;

		foreach ($subcommands as $sub) {
			$separator = $first ? '' : '|';
			$addition = $separator . $sub;

			if (!$first && strlen($line . $addition . ']') > $maxWidth) {
				$io->out($line . '|');
				$line = $indent . $sub;
			} else {
				$line .= $addition;
			}
			$first = false;
		}

		$io->out($line . ']');
	}

	/**
	 * Get terminal width for line wrapping.
	 *
	 * @return int Terminal width in columns
	 */
	protected function getTerminalWidth(): int {
		$columns = getenv('COLUMNS');
		if ($columns !== false && is_numeric($columns) && (int)$columns > 0) {
			return (int)$columns;
		}

		if (str_contains(strtolower(PHP_OS), 'win') === false) {
			$result = null;
			$output = exec('tput cols 2>/dev/null', result_code: $result);
			if ($result === 0 && is_numeric($output) && (int)$output > 0) {
				return (int)$output;
			}

			$output = exec('stty size 2>/dev/null', result_code: $result);
			if ($result === 0 && $output !== false && preg_match('/^\d+\s+(\d+)$/', $output, $matches)) {
				return (int)$matches[1];
			}
		}

		return 120;
	}

	/**
	 * Output relevant paths if defined
	 *
	 * @param \Cake\Console\ConsoleIo $io IO object.
	 * @return void
	 */
	protected function outputPaths(ConsoleIo $io): void {
		$paths = [];
		if (Configure::check('App.dir')) {
			$appPath = rtrim(Configure::read('App.dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			$paths['app'] = ' ' . $appPath;
		}
		if (defined('ROOT')) {
			$paths['root'] = rtrim(ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
		if (defined('CORE_PATH')) {
			$paths['core'] = rtrim(CORE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
		if ($paths === []) {
			return;
		}
		$io->out('<info>Current Paths:</info>', 2);
		foreach ($paths as $key => $value) {
			$io->out("* {$key}: {$value}");
		}
		$io->out('');
	}

	/**
	 * @phpstan-param non-empty-array<string> $names
	 * @param array<string> $names Names
	 * @return string
	 */
	protected function getShortestName(array $names): string {
		usort($names, function ($a, $b) {
			return strlen($a) - strlen($b);
		});

		return array_shift($names);
	}

	/**
	 * Output as XML
	 *
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @param iterable<string, string|object> $commands The command collection to output
	 * @return void
	 */
	protected function asXml(ConsoleIo $io, iterable $commands): void {
		$shells = new SimpleXMLElement('<shells></shells>');
		foreach ($commands as $name => $class) {
			if (is_object($class)) {
				$class = $class::class;
			}
			$shell = $shells->addChild('shell');
			$shell->addAttribute('name', $name);
			$shell->addAttribute('call_as', $name);
			$shell->addAttribute('provider', $class);
			$shell->addAttribute('help', $name . ' -h');
		}
		$io->setOutputAs(ConsoleOutput::RAW);
		$io->out((string)$shells->saveXML());
	}

	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser->setDescription(
			'Get the list of available commands for this application (compact format).',
		)->addArgument('command', [
			'help' => 'Filter commands by prefix (e.g., "bake" to show only bake commands).',
		])->addOption('xml', [
			'help' => 'Get the listing as XML.',
			'boolean' => true,
		]);

		return $parser;
	}

}
