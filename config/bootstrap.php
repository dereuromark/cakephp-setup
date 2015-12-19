<?php

use Cake\Core\Configure;

function dd($data, $showHtml = null) {
	if (Configure::read('debug')) {
		debug($data, $showHtml, false);

		$backtrace = debug_backtrace(false, 1);
		pr('dd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
		die();
	}
}

function prd($data) {
	if (Configure::read('debug')) {
		pr($data);

		$backtrace = debug_backtrace(false, 1);
		pr('prd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
		die();
	}
}

function vd($var) {
	if (Configure::read('debug')) {
		echo '<pre>';
		echo var_dump($var);
		echo '</pre>';

		$backtrace = debug_backtrace(false, 1);
		pr('vd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
	}
}

function vdd($var) {
	if (Configure::read('debug')) {
		echo '<pre>';
		echo var_dump($var);
		echo '</pre>';

		$backtrace = debug_backtrace(false, 1);
		pr('vdd-location: ' . $backtrace[0]['file'] . ':' . $backtrace[0]['line']);
		die();
	}
}
