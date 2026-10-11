<?php
declare(strict_types=1);

namespace Setup\Command;

use Cake\Command\Command;
use Cake\Console\CommandInterface;

/**
 * Outputs the current configuration
 * - DB (default and test)
 * - Cache
 * - ...
 *
 * @author Mark Scherer
 * @license MIT
 */
class CurrentConfigPhpinfoCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Display phpinfo() for CLI.
Use `bin/cake current_config phpinfo | grep xdebug` for example to get all xdebug relevant info from it.
Use the /admin/setup-extra/configuration/phpinfo backend to see phpinfo() for non-CLI (can differ!).';
	}

	/**
	 * @return int|null|void The exit code or null for success
	 */
	public function execute() {
		$args = $this->args;
		$io = $this->io;
		ob_start();
		phpinfo();
		/** @var string $phpinfo */
		$phpinfo = ob_get_contents();
		ob_end_clean();

		$io->out($phpinfo);

		return CommandInterface::CODE_SUCCESS;
	}

}
