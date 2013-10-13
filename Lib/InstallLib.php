<?php
App::uses('String', 'Utility');

/**
 * Used in configurations controller + debug helper
 */
class InstallLib {

	const DATABASE_TEMPLATE = 'database.tmp';

	const DATABASE_TEMPLATE_ENHANCED = 'database.enhanced.tmp';

	/**
	 */
	public static function configDir() {
		return APP . DS . 'Config' . DS;
	}

	/**
	 */
	public static function databaseConfigurationExists() {
		return file_exists(self::configDir() . 'database.php');
	}

	/**
	 */
	public static function databaseConfigurationStatus() {
		if (!self::databaseConfigurationExists()) {
			return false;
		}
		$res = true;
		try {
			$db = ConnectionManager::getDataSource('default');
			$res = $db->enabled();
		} catch (Exception $e) {
			return $e->getMessage();
		}
		return $res;
	}

	/**
	 */
	public static function writeTemplate($params) {
		$file = CakePlugin::path('Setup') . 'files' . DS;
		if (!$params['enhanced_database_class']) {
			$file .= self::DATABASE_TEMPLATE;
		} else {
			$enhanced = true;
			$file .= self::DATABASE_TEMPLATE_ENHANCED;
		}
		$file = file_get_contents($file);

		$content = array();
		if (empty($enhanced)) {
			unset($params['name']);
			unset($params['environment']);
		}
		unset($params['enhanced_database_class']);

		foreach ($params as $key => $val) {
			if (in_array($key, array('persistent'))) {
				$val = $val ? 'true' : 'false';
			} else {
				$val = '\'' . $val . '\'';
			}
			$content[] = TB . TB . '\'' . $key . '\' => ' . $val;
		}

		$testContent = '\'merge\' => true';
		if (empty($enhanced)) {
			$testContent = '';
		}

		$content = ltrim(implode(',' . PHP_EOL, $content));
		$content = String::insert($file, array('fields' => $content, 'testFields' => $testContent));

		$target = InstallLib::configDir() . 'database.php';
		return file_put_contents($target, $content);
	}

}
