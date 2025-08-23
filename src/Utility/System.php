<?php

namespace Setup\Utility;

/**
 * Get disk space and other useful system information
 *
 * @author Mark Scherer
 * @license MIT
 */
class System {

	/**
	 * Turn bitmasked level into readable string
	 *
	 * @param int $value Error levels as bitmask
	 * @return string Errors separated by pipe (|)
	 */
	public static function error2string($value) {
		$levelNames = [
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED => 'E_DEPRECATED',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE',
			E_USER_DEPRECATED => 'E_USER_DEPRECATED',
		];

		$levels = [];
		if (($value & E_ALL) === E_ALL) {
			$levels[] = 'E_ALL';
			$value &= ~E_ALL;
		}
		foreach ($levelNames as $level => $name) {
			if (($value & $level) == $level) {
				$levels[] = $name;
			}
		}

		return implode(' | ', $levels);
	}

	/**
	 * Turn readable string into bitmasked level
	 *
	 * @param string $string Errors separated by pipe (|)
	 * @return int Error levels as bitmask
	 */
	public static function string2error($string) {
		$levelNames = [
			'E_ERROR',
			'E_WARNING',
			'E_PARSE',
			'E_NOTICE',
			'E_CORE_ERROR',
			'E_CORE_WARNING',
			'E_COMPILE_ERROR',
			'E_COMPILE_WARNING',
			'E_USER_ERROR',
			'E_USER_WARNING',
			'E_USER_NOTICE',
			'E_ALL',
		];
		if (defined('E_STRICT')) {
			$levelNames[] = 'E_STRICT';
		}
		$value = 0;
		$levels = explode('|', $string);
		foreach ($levels as $level) {
			$level = trim($level);
			if (defined($level)) {
				$value |= (int)constant($level);
			}
		}

		return $value;
	}

	/**
	 * List of system locales
	 *
	 * Note: only for *nix systems
	 *
	 * @return array
	 */
	public static function systemLocales() {
		ob_start();
		system('locale -a');
		$str = (string)ob_get_contents();
		ob_end_clean();

		return explode("\n", trim($str));
	}

	/**
	 * Returns free and used space in bytes
	 *
	 * @return array array(used, available, percent_used, percent_available) - or empty on error
	 */
	public static function freeDiskSpace() {
		$space = [
			'percent_used' => 0,
			'percent_available' => 0,
			'total' => 0,
			'used' => 0,
			'available' => 0,
		];
		$command = sprintf('df');
		exec($command, $output, $status);
		if ($status !== 0) { # zero => success
			return $space;
		}
		$space = $output;
		$relevant = $space[1];

		$sizeAndPath = explode(' ', $relevant);
		/** @var array<int, int> $array */
		$array = [];
		foreach ($sizeAndPath as $value) {
			$value = trim($value);
			if ($value !== '') {
				$array[] = (int)$value;
			}
		}

		return [
			'percent_used' => $array[4],
			'percent_available' => 100 - $array[4],
			'total' => $array[1] * 1024,
			'used' => $array[2] * 1024,
			'available' => $array[3] * 1024,
		];
	}

	/**
	 * Calculate disk space.
	 *
	 * @param string $rootPath RootPath: from where to start
	 * @return array
	 */
	public function diskSpace($rootPath) {
		$space = [];

		// b = bytes, a = files too, c = grand total, x = only current file system
		// h = human readable
		$command = sprintf('du -bx %s', $rootPath);
		exec($command, $output, $status);
		if ($status === 0) { # zero => success
			$space = $output;
		}

		$this->_processDirData($space, $rootPath);

		return $space;
	}

	/**
	 * Return dir data in usable array structure.
	 *
	 * @param array $data Data from exec call
	 * @param string|null $root (e.g. CORE_PATH.DS.APP_DIR)
	 * @return void
	 */
	public function _processDirData(&$data, $root = null) {
		if (empty($data)) {
			return;
		}

		$rootKey = null;
		if ($root !== null) {
			$rootKey = count($data) - 1;
		}
		if ($root === null) {
			// shortest string?
			$root = $this->_findRoot($data);
		}
		if ($root === null) {
			return;
		}

		$sizeAndPath = explode("\t", $data[$rootKey]);
		$urlToRoot = trim($sizeAndPath[1]);

		foreach ($data as $key => $value) {
			$sizeAndPath = explode("\t", $value);
			$size = $sizeAndPath[0];
			$url = str_replace($urlToRoot, '', $sizeAndPath[1]);
			if (empty($url)) {
				$url = '/';
			}
			$pieces = explode(DS, $url);
			$data[$key] = [
				'size' => $size,
				'path' => $url,
				'pieces' => $pieces,
			];
		}
	}

	/**
	 * @param array $data
	 * @param array|null $root
	 * @return array
	 */
	public function dirToTree(&$data, $root = null) {
		$res = [];
		$rootElement = null;
		if ($root !== null) {
			$rootKey = array_keys($root, $data);
			if (isset($rootKey[0])) {
				$rootElement = (int)$rootKey[0];
			}
		}
		if ($rootElement === null) {
			// shortest string?
			$rootElement = $this->_findRoot($data);
		}
		if ($rootElement === null) {
			return $res;
		}

		return $this->_generateTree($data, $rootElement);
	}

	/**
	 * @param array $data
	 * @param int $root
	 * @return array
	 */
	public function _generateTree(&$data, $root) {
		$res = [];
		$sizeAndPath = explode("\t", $data[$root]);
		$urlToRoot = $sizeAndPath[1];
		$sizeOfRoot = $sizeAndPath[0];
		// root
		$res[DS] = [
			'size' => $sizeOfRoot,
			'url' => $urlToRoot,
			'children' => [],
		];

		for ($i = $root; $i >= 0; $i--) {
			$sizeAndPath = explode("\t", $data[$i]);

			$name = str_replace($urlToRoot, "\t", $sizeAndPath[1]);
			// split by DS
			$pieces = explode(DS, $name);

			// insert into array
			//TODO
			//$res[$name] = array();
		}

		return $res;
	}

	/**
	 * @param array $data
	 * @return int|null
	 */
	protected function _findRoot(&$data) {
		$length = 0;
		$index = null;
		foreach ($data as $key => $value) {
			$newLength = mb_strlen($value);
			if (!$length || $newLength < $length) {
				$length = $newLength;
				$index = (int)$key;
			}
		}

		return $index;
	}

}

/*
zb:

[7540] => 906	/var/www/app/libs/localized/de_validation.php
[7541] => 5002	/var/www/app/libs/localized
[7542] => 11222	/var/www/app/libs/debug_lib.php
[7543] => 1950	/var/www/app/libs/calc_lib.php
[7544] => 3882	/var/www/app/libs/google_translate_lib.php
[7545] => 3245	/var/www/app/libs/op_code_cache_lib.php
[7546] => 57276	/var/www/app/libs
[7547] => 546	/var/www/app/app_error.php
[7548] => 95120647	/var/www/app

*/
