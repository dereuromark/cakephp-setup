<?php

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;

if (!defined('FORMAT_DB_DATETIME')) {
	define('FORMAT_DB_DATETIME', 'Y-m-d H:i:s');
}

if (!defined('SECOND')) {
	define('SECOND', 1);
	define('MINUTE', 60);
	define('HOUR', 3600);
	define('DAY', 86400);
	define('WEEK', 604800);
	define('MONTH', 2592000);
	define('YEAR', 31536000);
}

if (!function_exists('dd')) {
	/**
	 * @param mixed $var
	 * @param bool|null $showHtml
	 * @return void
	 */
	function dd($var, $showHtml = null) {
		if (!Configure::read('debug')) {
			return;
		}

		debug($var, $showHtml, false);

		$backtrace = debug_backtrace(false, 1);
		pr('dd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
		exit(1);
	}
}

if (!function_exists('prd')) {
	/**
	 * @param mixed $var
	 * @return void
	 */
	function prd($var) {
		if (!Configure::read('debug')) {
			return;
		}

		pr($var);

		$backtrace = debug_backtrace(false, 1);
		pr('prd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
		exit(1);
	}
}

if (!function_exists('vd')) {
	/**
	 * @param mixed $var
	 * @return void
	 */
	function vd($var) {
		if (!Configure::read('debug')) {
			return;
		}

		echo '<pre>';
		var_dump($var);
		echo '</pre>';

		$backtrace = debug_backtrace(false, 1);
		pr('vd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
	}
}

if (!function_exists('vdd')) {
	/**
	 * @param mixed $var
	 * @return void
	 */
	function vdd($var) {
		if (!Configure::read('debug')) {
			return;
		}

		echo '<pre>';
		var_dump($var);
		echo '</pre>';

		$backtrace = debug_backtrace(false, 1);
		pr('vdd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
		exit();
	}
}

if (PHP_SAPI === 'cli') {
	EventManager::instance()->on('Bake.initialize', function (EventInterface $event): void {
		$event->getSubject()->loadHelper('Setup.SetupBake');
	});
}
