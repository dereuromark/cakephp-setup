<?php
App::uses('Object', 'Core');
App::uses('Folder', 'Utility');

/**
 * @deprecated? use console instead!
 * @author Mark Scherer
 * @license MIT
 */
class SetupLib extends Object {

	protected $folderRights = array();

	/**
	 * Import the folder rights
	 */
	public function __construct() {
		$this->folderRights = (array)Configure::read('Setup.FolderRights');
	}

	/**
	 * Remove specific query strings (and for BC named params) from parsed url array
	 *
	 * @param string type
	 * @param array $url Array containing the current url
	 * @return array Cleaned url array
	 */
	public static function cleanedUrl($type, $urlArray) {
		$types = (array)$type;
		foreach ($types as $type) {
			if (isset($urlArray['named'][$type])) {
				unset($urlArray['named'][$type]);
			}
		}
		foreach ($types as $type) {
			if (isset($urlArray['?'][$type])) {
				unset($urlArray['?'][$type]);
			}
		}

		$named = $urlArray['named'];
		$pass = $urlArray['pass'];

		$returnArray = array();
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
		//TODO: more generic (other prefixes!)
		if (isset($urlArray['admin'])) {
			$returnArray['admin'] = $urlArray['admin'];
		}
		foreach ($named as $key => $val) {
			$returnArray[$key] = $val;
		}
		foreach ($pass as $val) {
			$returnArray[] = $val;
		}

		$returnArray['?'] = $urlArray['?'];

		return $returnArray;
	}

	/**
	 * TODO: make more generic?
	 *
	 * @return void
	 */
	 public function tmpStructure() {
		// check (tmp) folder integrity
		foreach ($this->folderRights as $folder => $right) {
			$handle = new Folder($folder, true, $right);
			if ($x = $handle->errors()) {
				$this->log('SetupComponent: ' . $x, E_ERROR);
			}
		}
		$handle = null;

		//$handle = new Folder(WWW_ROOT.'img'.DS.'content'.DS.'jabbers', true, 0777);

	 }

	/**
	 * File Cache
	 * TODO: make more generic?
	 *
	 * @return void
	 */
	public function clearCache($type) {
		$types = array('m' => 'models', 'model' => 'models', 'v' => 'views', 'views' => 'views', 'p' => 'persistent', 'persistent' => 'persistent');

			$typeArray = array();
			if (is_array($type)) {
				$this->log('Type for clearCaches should not be an array (' . implode(', ', $type) . ')');
				$type = array_shift($type);
			}
			if (strpos($type, '|') !== false) {
				$type = explode('|', $type);
				foreach ($typeArray as $key => $val) {
					if (!array_key_exists($val, $types)) {
						unset($typeArray[$key]);
					}
				}

			} elseif (array_key_exists($type, $types)) {
				$typeArray[] = $types[$type];
			}

		// defaults
		if (empty($typeArray)) {
			$typeArray = array('models', 'persistent', 'views');
		}

		foreach ($typeArray as $t) {
			clearCache(null, $t, null);
		}
	}

	/**
	 * Main Cache (File, Memcache, etc)
	 *
	 * @return void
	 */
	public function clearCache2($check = false, $config = 'default') {
		Cache::clear($check, $config);
	}

}
