<?php

namespace Setup\Utility;

/**
 * @author Mark Scherer
 * @license MIT
 */
class Setup {

	/**
	 * Removes specific query strings from parsed URL array.
	 *
	 * @param array|string $type Type to remove
	 * @param array $urlArray Array containing the current URL
	 * @return array Cleaned URL array
	 */
	public static function cleanedUrl($type, $urlArray) {
		$types = (array)$type;
		foreach ($types as $type) {
			if (isset($urlArray['?'][$type])) {
				unset($urlArray['?'][$type]);
			}
		}

		$pass = !empty($urlArray['pass']) ? $urlArray['pass'] : [];

		$returnArray = [];
		if (isset($urlArray['controller'])) {
			$returnArray['controller'] = $urlArray['controller'];
		}
		if (isset($urlArray['action'])) {
			$returnArray['action'] = $urlArray['action'];
		}
		if (isset($urlArray['plugin'])) {
			$returnArray['plugin'] = $urlArray['plugin'];
		}
		if (isset($urlArray['prefix'])) {
			$returnArray['prefix'] = $urlArray['prefix'];
		}
		foreach ($pass as $val) {
			$returnArray[] = $val;
		}

		$returnArray['?'] = $urlArray['?'];

		return $returnArray;
	}

}
