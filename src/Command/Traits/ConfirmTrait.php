<?php

namespace Setup\Command\Traits;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

trait ConfirmTrait {

	protected function confirmOrAbort(Arguments $args, ConsoleIo $io, string $question): void {
		if ($args->getOption('force')) {
			return;
		}
		if ($args->getOption('quiet')) {
			$io->abort('Refusing to continue without confirmation in non-interactive mode. Pass --force to skip the prompt.');
		}
		if ($io->askChoice($question, ['y', 'n'], 'n') !== 'y') {
			$io->abort('Aborted!');
		}
	}

}
