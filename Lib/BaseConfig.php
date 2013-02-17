<?php

/**
 * Database Base Config
 *
 * You should define both `environment` and `path` to be able to switch
 * dynamically in CLI mode and normal frontend mode.
 * Define `name` to manually switch using Configure::write('Environment.name').
 *
 * It also automatically sets the test environment based on the default settings:
 * If no `test` config is set it will use the default settings except for prefix.
 * You can also define some custom settings and if `merge` is set to `true` in your test config
 * it will then merge with `default` afterwards.
 *
 * @author Mark Scherer
 * @copyright Mark Scherer
 * @cakephp 2
 * @license MIT
 * 2011-07-30 ms
 */
class BaseConfig {

	protected $_environments = array('default');

	protected $_defaults = array(
		'encoding' => 'utf8',
		'persistent' => false,
	);

	public $default = array();

	/**
	 * switch between local and live site(s) automatically by domain
	 * or manually by Configure::read('Environment.name')
	 * 2009-05-29 ms
	 */
	public function __construct() {
		$vars = get_object_vars($this);
		foreach ($vars as $var => $config) {
			if (strpos($var, '_') !== 0 && !in_array($var, $this->_environments)) {
				$this->_environments[] = $var;
			}
		}
		$this->default = array_merge($this->_defaults, $this->default);
		$environment = $this->getEnvironmentName();
		if ($environment && isset($this->{$environment})) {
			$this->default = array_merge($this->default, $this->{$environment});
		}

		if (!isset($this->test)) {
			$this->test = $this->default;
			if (isset($this->test['prefix'])) {
				unset($this->test['prefix']);
			}
		}
		if (empty($this->default['name'])) {
			$this->default['name'] = $environment;
		}
		$this->test['name'] = 'test';
		if (!isset($this->test['prefix'])) {
			$this->test['prefix'] = 'zzz_';
		}
		if (!empty($this->test['merge'])) {
			$this->test = array_merge($this->default, $this->test);
			unset($this->test['merge']);
		}
	}

	/**
	 * @return string
	 */
	public function getEnvironmentName() {
		$environment = (String) Configure::read('Environment.name');
		# if no manual setting available, use host to decide which config to use
		if (empty($environment) && !empty($_SERVER['HTTP_HOST'])) {
			$server = (String) $_SERVER['HTTP_HOST'];
			foreach ($this->_environments as $e) {
				if (isset($this->{$e}) && isset($this->{$e}['environment']) && in_array($server, (array) $this->{$e}['environment'])) {
					$environment = $e;
					break;
				}
			}
		}
		if (empty($environment) && $serverPath = $this->_getEnvironmentPath()) {
			foreach ($this->_environments as $e) {

				if (isset($this->{$e}) && isset($this->{$e}['path']) && in_array($serverPath, (array) $this->{$e}['path'])) {
					$environment = $e;
					break;
				}
			}
		}
		return $environment;
	}


	/**
	 * return current name (or at least the settings itself...)
	 * @param bool $nameOnly
	 * @return mixed nameString/configArray
	 * 2009-06-02 ms
	 */
	public function current($nameOnly = false) {
		if ($nameOnly) {
			if (!empty($this->default['name'])) {
				return $this->default['name'];
			} else {
				# return [$this->default];
			}
			return 'n/a (no name given)';
		}
		return $this->default;
	}

	/**
	 * wrapper to handle symlinks properly, as well
	 * @return string $path
	 */
	protected function _getEnvironmentPath() {
		$path = realpath(APP);
		if (substr($path, -1, 1) !== DS) {
			$path .= DS;
		}
		return $path;
	}

}
