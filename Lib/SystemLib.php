<?php

/**
 * Get disk space and other useful system information
 *
 * @author Mark Scherer
 * @license MIT
 */
class SystemLib {

	/**
	* Returns upload limit on configurations.
	*
	* @return int
	*/
	public static function uploadLimit() {
		$maxUpload = (int)ini_get('upload_max_filesize');
		$maxPost = (int)ini_get('post_max_size');
		$memoryLimit = (int)ini_get('memory_limit');
		return min($maxUpload, $maxPost, $memoryLimit);
	}

	/**
	 * Turn bitmasked level into readable string
	 *
	 * @param int Error levels as bitmask
	 * @return string Errors separated by pipe (|)
	 */
	public static function error2string($value) {
		$levelNames = array(
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE');
		if (defined('E_STRICT')) {
			$levelNames[E_STRICT] = 'E_STRICT';
		}
		$levels = array();
		if (($value & E_ALL) == E_ALL) {
			$levels[] = 'E_ALL';
			$value &= ~ E_ALL;
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
	 * @param string Errors separated by pipe (|)
	 * @return int Error levels as bitmask
	 */
	public static function string2error($string) {
		$levelNames = array(
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
			'E_ALL');
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
		if (WINDOWS) {
			return array();
		}
		ob_start();
		system('locale -a');
		$str = ob_get_contents();
		ob_end_clean();
		return explode("\n", trim($str));
	}

	/**
	 * Returns free and used space in bytes
	 *
	 * @return array array(used, available, percent_used, percent_available) - or empty on error
	 */
	public static function freeDiskSpace($mounted = '/') {
		$space = array(
			'percent_used' => 0,
			'percent_available' => 0,
			'total' => 0,
			'used' => 0,
			'available' => 0);
		if (WINDOWS) {
			return $space;
		}
		$command = sprintf('df');
		exec($command, $output, $status);
		if ($status !== 0) { # zero => success
			return $space;
		}
		$space = $output;
		$revelant = $space[1];
		/*
		foreach ($space as $key => $value) {
			$sizeAndPath = explode(' ', $value);
			//preg_match_all('/(\s*?)(.*)(\s*?)/', $value, $sizeAndPath);
			$array = array();
			foreach ($sizeAndPath as $key => $value) {
				if (($value = trim($value)) !== '') {
					$array[] = $value;
				}
			}
		} */

		$sizeAndPath = explode(' ', $revelant);
		$array = array();
		foreach ($sizeAndPath as $key => $value) {
			if (($value = trim($value)) !== '') {
				$array[] = $value;
			}
		}

		$space = array(
			'percent_used' => (int)$array[4],
			'percent_available' => 100 - (int)$array[4],
			'total' => $array[1] * 1024,
			'used' => $array[2] * 1024,
			'available' => $array[3] * 1024);
		return $space;
	}

	/**
	 * Calculate disk space.
	 *
	 * @param rootPath: from where to start
	 * @return array
	 */
	public function diskSpace($rootPath) {
		$space = array();
		if (!WINDOWS) {
			// b = bytes, a = files too, c = grand total, x = only current file system
			// h = human readable
			$command = sprintf('du -bx %s', $rootPath);
			exec($command, $output, $status);
			if ($status === 0) { # zero => success
				$space = $output;
			}
		}
		$this->_processDirData($space, $rootPath);
		return $space;
	}

	/**
	 * Return dir data in usable array structure.
	 *
	 * @param data from exec call
	 * @param root (e.g. CORE_PATH.DS.APP_DIR)
	 * @return void|array
	 */
	public function _processDirData(&$data, $root = null) {
		if (empty($data)) {
			return $data;
		}
		$res = array();
		if ($root !== null) {
			//$rootKey = array_keys($data, $root);
			$rootKey = count($data) - 1;

		}
		if ($root === null) {
			// shortest string?
			$root = $this->_findRoot($data);
		}
		if ($root === null) {
			return $res;
		}

		$sizeAndPath = explode(TB, $data[$rootKey]);
		$urlToRoot = trim($sizeAndPath[1]);
		//pr($urlToRoot);

		foreach ($data as $key => $value) {
			$sizeAndPath = explode(TB, $value);
			$size = $sizeAndPath[0];
			$url = str_replace($urlToRoot, '', $sizeAndPath[1]);
			if (empty($url)) {
				$url = '/';
			}
			$pieces = explode(DS, $url);
			$data[$key] = array(
				'size' => $size,
				'path' => $url,
				'pieces' => $pieces);
		}
		//return $data;
	}

	/** TODO: js file tree **/

	public function dirToTree(&$data, $root = null) {
		$res = array();
		if ($root !== null) {
			$rootKey = array_keys($root, $data);
			$root === null;
			if (isset($rootKey[0])) {
				$root = $rootKey[0];
			}
		}
		if ($root === null) {
			// shortest string?
			$root = $this->_findRoot($data);
		}
		if ($root === null) {
			return $res;
		}

		$res = $this->_generateTree($data, $root);
		return $res;
	}

	public function _generateTree(&$data, $root) {
		$res = array();
		$sizeAndPath = explode(TB, $data[$root]);
		$urlToRoot = $sizeAndPath[1];
		$sizeOfRoot = $sizeAndPath[0];
		// root
		$res[DS] = array(
			'size' => $sizeOfRoot,
			'url' => $urlToRoot,
			'children' => array());

		for ($i = $root; $i >= 0; $i--) {
			$sizeAndPath = explode(TB, $data[$i]);

			$name = str_replace($urlToRoot, DS, $sizeAndPath[1]);
			// split by DS
			$pieces = explode(DS, $name);

			// insert into array
			//TODO
			//$res[$name] = array();
		}
		return $res;
	}

	/*
	foreach ($list as $v) {
	$s = &$this->structure; // $s is a moving reference

	$v = explode($sep, $v);

	// Loop thru each path segment
	foreach ($v as $j) {
	if (!isset($s[$j])) {
	$s[$j] = array();
	}

	$s = &$s[$j];
	}
	}
	*/

	protected function _leaf($data) {
		//return array('size'=>$size, 'url'=>$url, 'children'=>null);
	}

	protected function _node($data) {
		//return array('size'=>$size, 'url'=>$url, 'children'=>array());
	}

	protected function _findRoot(&$data) {
		$length = 0;
		$index = null;
		foreach ($data as $key => $value) {
			if (!$length || ($newLength = mb_strlen($value)) < $length) {
				$length = $newLength;
				$index = $key;
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
