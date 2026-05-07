<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, array{available: bool, className: string, reason?: string}> $availability
 * @var array<string> $availableNames
 * @var array<string, string> $unavailable
 * @var array<string, array<string, array{ms: float, opsPerSec: float}|array{error: string}>>|null $results
 */
// placeholder - full implementation in Task 4
foreach ($availability as $name => $entry) {
	echo h($name) . PHP_EOL;
}
if ($results !== null) {
	foreach ($results as $engineName => $ops) {
		echo h($engineName) . PHP_EOL;
	}
}
