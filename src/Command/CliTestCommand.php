<?php
declare(strict_types = 1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Routing\Router;

/**
 * @author Mark Scherer
 * @license MIT
 */
class CliTestCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Test CLI env, e.g. Router for CLI usage.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$url = Router::url('/');
		$io->out('Router::url(\'/\'): ' . PHP_EOL . "\t" . $url);

		$arrayUrl = ['controller' => 'Test'];
		if ($args->getOption('prefix')) {
			/** @var string $prefix */
			$prefix = $args->getOption('prefix');
			$arrayUrl['prefix'] = $prefix;
		}
		if ($args->getOption('plugin')) {
			/** @var string $plugin */
			$plugin = $args->getOption('plugin');
			$arrayUrl['plugin'] = $plugin;
		}

		$url = Router::url($arrayUrl);
		$text = $this->_urlToText($arrayUrl);
		$io->out('Router::url([' . $text . ']): ' . PHP_EOL . "\t" . $url);

		$io->out($io->nl());
		$io->out('Full base URLs:');

		$url = Router::url('/', true);
		$io->out('Router::url(\'/\', true): ' . PHP_EOL . "\t" . $url);

		$url = Router::url($arrayUrl, true);
		$io->out('Router::url([' . $text . '], true): ' . PHP_EOL . "\t" . $url);
	}

	/**
	 * Hook action for defining this command's option parser.
	 *
	 *@see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 *
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);
		$parser->setDescription(static::getDescription());

		$parser->addOption('prefix', [
			'short' => 'p',
			'help' => 'Prefix.',
		]);
		$parser->addOption('plugin', [
			'short' => 'x',
			'help' => 'Plugin.',
		]);

		return $parser;
	}

	/**
	 * @param array<string, string> $arrayUrl
	 * @return string
	 */
	protected function _urlToText(array $arrayUrl): string {
		$url = [];
		foreach ($arrayUrl as $k => $v) {
			$url[] = "'$k' => '$v'";
		}

		return implode(', ', $url);
	}

}
