<?php
App::uses('AppHelper', 'View/Helper');
App::uses('Utility', 'Tools.Utility');

class TestHelper extends AppHelper {

	public $helpers = array('Html');

	public static $int;

	public static $s;

	/**
	 * @param string $session
	 * @return string
	 */
	public static function color($session) {
		static $colors = array('green', 'yellow', '#FFB4A5', '#7BC4FF', 'orange');
		if (!TestHelper::$s || TestHelper::$s !== $session) {
			TestHelper::$int = (TestHelper::$int + 1) % count($colors);
			TestHelper::$s = $session;
		}
		return $colors[TestHelper::$int];
	}

	/**
	 * TestHelper::own()
	 *
	 * @param mixed $ip
	 * @return bool Success
	 */
	public static function own($ip) {
		if ($ip === Utility::getClientIP()) {
			return true;
		}
		return false;
	}

}
