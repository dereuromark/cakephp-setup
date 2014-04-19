<?php

/**
 */
class OpCodeCacheLib {

	public static $engines = array(
		'Xcache',
		'Wincache',
		'Apc',
		'Eaccelerator',
		'Ioncube',
		'Zend',
		'Nusphere'
	);

	/**
	 * If opcode cache is enabled
	 * @return bool Success
	 */
	public static function isEnabled() {
		$is = self::detect();
		foreach ($is as $engine) {
			if ($engine) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array detectedEngines for general lookup or bool true/false for specific engine
	 */
	public static function detect($engine = null) {
		if ($engine !== null) {
			$engine = ucfirst(strtolower($engine));
			if (in_array($engine, self::$engines)) {
				$engine = 'has' . $engine;
				return (bool)DetectOpCodeCache::$engine();
			}
			return null;
		}

		$res = array();
		foreach (self::$engines as $engine) {
			$method = 'has' . $engine;
			$res[$engine] = (bool)DetectOpCodeCache::$method();
		}
		return $res;
	}

}

/**
 * PHP Opcode-Cache detection
 *
 * @author Alexander Over <phpclasses@quadrat4.de>
 * @example DetectOpCodeCache::checkAll();
 * @example DetectOpCodeCache::hasApc();
 */
class DetectOpCodeCache {

	/**
	 * @public $extensions
	 */
	protected $extensions = array();

	/**
	 * @public $instance
	 */
	private static $instance;

	public function __construct() {
		$this->extensions = get_loaded_extensions();
	}

	public static function getInstance() {
		if (empty( self::$instance )) {
			self::$instance = new DetectOpCodeCache();
		}
		return self::$instance;
	}

	final public static function checkAll() {

		$object = self::getInstance();

		return ($object->hasXcache() ||
			$object->hasWincache() ||
			$object->hasApc() ||
			$object->hasEaccelerator() ||
			$object->hasIoncube() ||
			$object->hasZend() ||
			$object->hasNusphere()
		);
	}

	/**
	 * check if we have Xcache

	* @link http://xcache.lighttpd.net
	* @return bool
	*/
	public static function hasXcache() {
		return function_exists('xcache_isset' );
	}

	/**
	 * check if we have Wincache

	* @link http://www.iis.net/expand/WinCacheForPHP
	* @return bool
	*/
	public static function hasWincache() {
		return function_exists('wincache_fcache_fileinfo');
	}

	/**
	 * check if we have Alternative PHP Cache

	* @link http://pecl.php.net/package/apc
	* @return bool
	*/
	public static function hasApc() {
		return function_exists('apc_add' );
	}

	/**
	 * check if we have eAccelerator

	* @link http://eaccelerator.net
	* @return bool
	*/
	public static function hasEaccelerator() {
		// !empty doesn't work, because no variable
		return (bool)strlen( ini_get('eaccelerator.enable' ));
	}

	/**
	 * check if we have ionCube Loader

	* @link http://www.php-accelerator.co.uk
	* @return bool
	*/
	public static function hasIoncube() {
		return (bool)strlen( ini_get('phpa' ));
	}

	/**
	 * check if we have Zend Optimizer+

	* @link http://www.zend.com/products/server
	* @return bool
	*/
	public static function hasZend() {
		return (bool)strlen( ini_get('zend_optimizer.enable_loader' ));
	}

	/**
	 * check if we have nuSphere phpExpress

	* @link http://www.nusphere.com/products/phpexpress.htm
	* @return bool
	*/
	public static function hasNusphere() {
		// in_array() check is slower then function_exists(), so don't use in production environment
		$object = self::getInstance();
		return in_array('phpexpress', $object->extensions );
	}

}
