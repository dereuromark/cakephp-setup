<?php
declare(strict_types=1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandFactoryInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Setup\Healthcheck\Healthcheck;
use Setup\Healthcheck\HealthcheckCollector;

/**
 * Healthcheck command.
 */
class HealthcheckCommand extends Command {

	/**
	 * The name of this command.
	 *
	 * @var string
	 */
	protected string $name = 'healthcheck';

	/**
	 * @var \Setup\Healthcheck\Healthcheck
	 */
	protected Healthcheck $healthcheck;

	/**
	 * Get the default command name.
	 *
	 * @return string
	 */
	public static function defaultName(): string {
		return 'healthcheck';
	}

	/**
	 * Get the command description.
	 *
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Run healthcheck for the application (CLI version).';
	}

	/**
	 * @param \Cake\Console\CommandFactoryInterface|null $factory
	 */
	public function __construct(?CommandFactoryInterface $factory = null) {
		if ($factory) {
			parent::__construct($factory);
		}
		$this->healthcheck = new Healthcheck(new HealthcheckCollector());
	}

	/**
	 * Hook method for defining this command's option parser.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		return parent::buildOptionParser($parser)
			->setDescription(static::getDescription())
			->addArgument('domain', [
				'help' => 'The domain to check (' . implode(', ', $this->healthcheck->domains()) . '). If not provided, ALL domains will be checked.',
				'required' => false,
			]);
	}

	/**
	 * Implement this method with your command's logic.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int The exit code
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$passed = $this->healthcheck->run($args->getArgument('domain'));

		$result = $this->healthcheck->result();
		$totalCount = $result->unfold()->count();
		$io->verbose($totalCount . ' check(s) in ' . count($result) . ' domains(s)');

		foreach ($result as $domain => $checks) {
			$io->out();
			$io->out('### ' . $domain);
			foreach ($checks as $check) {
				$passed ? $io->success($check->name()) : $io->error($check->name());
				if (!$check->passed()) {
					if ($check->failureMessage()) {
						$io->error(implode(', ', $check->failureMessage()));
					}
					if ($check->warningMessage()) {
						$io->warning(implode(', ', $check->warningMessage()));
					}
				} else {
					if ($check->successMessage()) {
						$io->success(implode(', ', $check->successMessage()));
					}
				}

				if ($check->infoMessage()) {
					$io->verbose('Info:');
					foreach ($check->infoMessage() as $value) {
						$io->verbose('- ' . $value);
					}
				}
			}
		}

		$io->out();
		if ($passed) {
			$io->success('=> OK');
		} else {
			$io->error('=> FAIL');
		}

		return $passed ? static::CODE_SUCCESS : static::CODE_ERROR;
	}

}
