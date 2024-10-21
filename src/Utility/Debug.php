<?php

namespace Setup\Utility;

/**
 * Used in configurations controller + debug helper
 */
class Debug {

	/**
	 * @see http://www.php.net/manual/en/class.soapclient.php
	 * @return bool
	 */
	public function soap() {
		return class_exists('SoapServer') && class_exists('SoapClient');
	}

	/**
	 * @deprecated ?
	 * @return string
	 */
	public function serverLoad() {
		$x = $this->getServerLoad();
		if (!$x) {
			return '<i>n/a (only for unix/linux server)</i>';
		}

		return '<b>' . $x[0] . ' - ' . $x[1] . ' - ' . $x[2] . '</b>';
	}

	/**
	 * Determines the server load (last 1 minute - last 5 minutes - last 15 minutes)
	 *
	 * @return array
	 */
	public function getServerLoad() {
		$load = [];
		$res = (string)exec('uptime');
		// last 1 minute : last 5 minutes : last 15 minutes
		if (preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $res, $load) <= 0) {

		}

		if (is_array($load) && count($load) > 2) {
			return [$load[1], $load[2], $load[3]];
		}

		return [];
	}

	/**
	 * @var string
	 */
	public $fillString = 'NA';

	/**
	 * @var int
	 */
	public $useFillString = 1;

	/**
	 * @deprecated ?
	 * @param string $space
	 * @return array|string
	 */
	public function serverUptime($space = '') {
		$command = 'uptime';
		exec($command, $output, $status);
		if ($status !== 0) { # zero => success
			return [];
		}

		return $output;
	}

	/**
	 * getUptime
	 *
	 * Returns system uptime, like Unix-Command "uptime"
	 *
	 * @param bool $digit set true in order to prepend leading "0" before minutes
	 * @return array uptime uptime in days, hours and minutes - or empty on failure
	 */
	public function getUptime($digit = false) {
		$result = [];

		$fh = fopen('/proc/uptime', 'r');
		if (!is_resource($fh)) {
			return $result;
		}
		$buffer = explode(' ', (string)fgets($fh, 4096));
		fclose($fh);

		$sysTicks = (int)trim($buffer[0]);

		$mins = $sysTicks / 60;
		$hours = $mins / 60;
		$days = floor($hours / 24);
		$hours = floor($hours - ($days * 24));
		$mins = floor($mins - ($days * 60 * 24) - ($hours * 60));

		if ($digit && ($mins < 10)) {
			$mins = "0$mins";
		}

		$result['days'] = $days;
		$result['hours'] = $hours;
		$result['mins'] = $mins;
		$result['timestamp'] = time() - (int)$sysTicks;

		return $result;
	}

	/**
	 * getKernelVersion
	 *
	 * Same as "uname --release"
	 *
	 * @return string Kernel-Version
	 */
	public function getKernelVersion() {
		$fh = fopen('/proc/version', 'r');
		if ($fh) {
			$buffer = (string)fgets($fh, 4096);
			fclose($fh);

			// search and grep the kernel-version
			if (preg_match('/version (.*?) /', $buffer, $matches)) {
				$result = $matches[1];
				if (preg_match('/SMP/', $buffer)) {
					$result .= ' (SMP)';
				}
			} else {
				if ($this->useFillString) {
					$result = $this->fillString;
				} else {
					$result = '';
				}
			}
		} else {
			if ($this->useFillString) {
				$result = $this->fillString;
			} else {
				$result = '';
			}
		}

		return $result;
	}

	/**
	 * getCpu
	 *
	 * Get CPU info, see /proc/spuinfo
	 *
	 * @return array
	 */
	public function getCpu() {
		$results = [];
		$buffer = [];

		$fh = fopen('/proc/cpuinfo', 'r');
		if (!$fh) {
			return [];
		}

		$processors = -1;

		while ($buffer = fgets($fh, 4096)) {
			if (!str_contains($buffer, ':')) {
				continue;
			}
			[$key, $value] = explode(':', trim($buffer), 2); //preg_split("/\s+:\s+/", trim($buffer), 2);

			$key = trim($key);
			$value = (float)trim($value);

			// Maybe you need some other tags if you run this on another architecture.
			// If you find or miss one, please tell me.
			switch ($key) {
				case 'model name': // for ix86
					$results[$processors]['model'] = $value;

					break;
				case 'cpu MHz':
					$results[$processors]['mhz'] = sprintf('%.2f', $value);

					break;
				case 'clock': // for PPC
					$results[$processors]['mhz'] = sprintf('%.2f', $value);

					break;
				case 'cpu': // for PPC
					$results[$processors]['model'] = $value;

					break;
				case 'cpu cores': // for PPC
					$results[$processors]['cores'] = $value;

					break;
				case 'revision': // for PPC arch
					$results[$processors]['model'] .= ' ( rev: ' . $value . ')';

					break;
				case 'cache size':
					$results[$processors]['cache'] = $value;

					break;
				case 'bogomips':
					if (!isset($results[$processors]['bogomips'])) {
						$results[$processors]['bogomips'] = 0;
					}
					$results[$processors]['bogomips'] += $value;

					break;
				case 'processor':
					$processors++;
					$results[$processors]['processor'] = $value + 1;

					break;
			}
		}
		fclose($fh);

		return $results;
	}

	/**
	 * Memory info
	 *
	 * @return array Array(total=>x, free=>y, used=>z)
	 */
	public function getRam() {
		$fh = fopen('/proc/meminfo', 'r');
		if (!$fh) {
			return [];
		}
		$results = [
			'total' => 0,
			'free' => 0,
		];

		while ($buffer = fgets($fh, 4096)) {
			if (strpos($buffer, ':') === false) {
				continue;
			}
			[$key, $value] = explode(':', trim($buffer), 2);

			switch ($key) {
				case 'MemTotal': // for ix86
					$results['total'] = round(sprintf('%d', $value) / 1024); // in MB

					break;
				case 'MemFree':
					$results['free'] = round(sprintf('%d', $value) / 1024); // in MB

					break;
			}
		}
		fclose($fh);
		$results['used'] = $results['total'] - $results['free'];

		return $results;
	}

	/**
	 * Get current memory usage
	 *
	 * @param bool $real
	 * @return int number of bytes ram currently in use. 0 if memory_get_usage() is not available.
	 */
	public static function memoryUsage($real = false) {
		if (!function_exists('memory_get_usage')) {
			return 0;
		}

		return memory_get_usage($real);
	}

	/**
	 * Get peak memory use
	 *
	 * @param bool $real
	 * @return int peak memory useage (in bytes). Returns 0 if memory_get_peak_usage() is not available
	 */
	public static function peakMemoryUsage($real = false) {
		if (!function_exists('memory_get_peak_usage')) {
			return 0;
		}

		return memory_get_peak_usage($real);
	}

	/**
	 * @param bool $inBytes
	 * @return string|int MemoryLimit
	 */
	public function memoryLimit($inBytes = false) {
		$res = ini_get('memory_limit');
		if ($inBytes) {
			return $this->returnInBytes($res);
		}

		return $res;
	}

	/**
	 * Tests if memory limit can be raised temporarily (necessary for image resizing etc)
	 *
	 * @return bool Success
	 */
	public function memoryLimitAdjustable() {
		$int = (int)$this->memoryLimit();
		$int--;
		if (ini_set('memory_limit', $int . 'M') === false) {
			return false;
		}
		if ($this->memoryLimit() !== $int . 'M') {
			return false;
		}

		return true;
	}

	/**
	 * If available: Searches DNS for MX records corresponding to hostname.
	 *
	 * @return bool
	 */
	public function getmxrrAvailable() {
		return function_exists('getmxrr');
	}

	/**
	 * If available: Searches DNS for records of type type corresponding to host.
	 *
	 * @return bool
	 */
	public function checkdnsrrAvailable() {
		return function_exists('checkdnsrr');
	}

	/**
	 * test if exec commands are allowed
	 *
	 * @return bool
	 */
	public function execAllowed() {
		$command = 'echo XYZ';
		//$backupFile = APP.date("Y-m-d_H-i-s").'.txt';
		//$command = "mysqldump --opt -h $dbhost -u $dbuser -p $dbpass $dbname > $backupFile";

		$res = exec($command);

		//$s = system($command);
		return $res ? true : false;
	}

	/**
	 * @return mixed True on success, string error otherwise
	 */
	public function wgetAllowed() {
		$transport = 'wget';
		//$return_var = null;
		//passthru("wget --version", $return_var);
		$returnVar = `wget --version`;
		if ($returnVar) {
			return true;
		}

		return $returnVar;
	}

	/**
	 * PHP Infos
	 *
	 * @return string
	 */
	public function phpVersion() {
		return phpversion();
	}

	/**
	 * @return string
	 */
	public function phpTime() {
		return date('Y-m-d H:i:s', time());
	}

	/**
	 * @deprecated Not in use.
	 * @return string
	 */
	public function phpUptime() {
		return (string)exec('uptime');
	}

	/**
	 * @return bool|null
	 */
	public function mbStringOverload() {
		$mbOvl = null;
		if (extension_loaded('mbstring')) {
			$mbOvl = ini_get('mbstring.func_overload') != 0;
		}

		return $mbOvl;
	}

	/**
	 * @return bool|null
	 */
	public function mbDefLang() {
		$mbDefLang = null;
		if (extension_loaded('mbstring')) {
			$mbDefLang = strtolower((string)ini_get('mbstring.language')) === 'neutral';
		}

		return $mbDefLang;
	}

	/**
	 * @return array
	 */
	public function loadedExtensions() {
		return get_loaded_extensions();
	}

	/**
	 * @param string $extensionName
	 *
	 * @return bool
	 */
	public function extensionLoaded($extensionName) {
		$e = $this->loadedExtensions();
		if (in_array(strtolower($extensionName), $e)) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the value from php.ini directly, while the ini_get returns the runtime config value
	 *
	 * @param string $var
	 * @return array|string|null
	 */
	public function configVar($var) {
		return get_cfg_var($var) ?: null;
	}

	/**
	 * Returns the runtime config value
	 *
	 * @param string $var
	 * @return string
	 */
	public function runtimeVar($var) {
		return (string)ini_get($var);
	}

	/**
	 * @param string $extension
	 * @return array with the names of the functions of a module or FALSE if extension is not available
	 */
	public function extensionFunctions($extension) {
		return get_extension_funcs($extension) ?: [];
	}

	/**
	 * Open base dir path
	 *
	 * @return array<string>
	 */
	public function openBasedir() {
		$var = (string)ini_get('open_basedir');
		if (str_contains($var, ':')) {
			$paths = explode(':', $var);
		} else {
			$paths = [];
		}

		return $paths;
	}

	/**
	 * @return bool
	 */
	public function displayErrors() {
		$res = (bool)ini_get('display_errors');

		return $res;
	}

	/**
	 * @return bool
	 */
	public function allowUrlFopen() {
		$res = (bool)ini_get('allow_url_fopen');

		return $res;
	}

	/**
	 * @return bool
	 */
	public function fileUpload() {
		$res = (bool)ini_get('file_uploads');

		return $res;
	}

	/**
	 * @param bool $inBytes
	 * @return string|int xM
	 */
	public function postMaxSize($inBytes = false) {
		$res = (string)ini_get('post_max_size');
		if ($inBytes) {
			return $this->returnInBytes($res);
		}

		return $res;
	}

	/**
	 * @param bool $inBytes
	 * @return string|int xM
	 */
	public function uploadMaxSize($inBytes = false) {
		$res = (string)ini_get('upload_max_filesize');
		if ($inBytes) {
			return $this->returnInBytes($res);
		}

		return $res;
	}

	/**
	 * Whether or not to register the old-style input arrays, HTTP_GET_VARS and friends. If you're not using them, it's recommended to turn them off, for performance reasons.
	 *
	 * @return bool
	 */
	public function registerLongArrays() {
		$res = (bool)ini_get('register_long_arrays');

		return $res;
	}

	/**
	 * This directive tells PHP whether to declare the argv&argc variables (that would contain the GET information). If you don't use these variables, you should turn it off for increased performance.
	 *
	 * @return bool
	 */
	public function registerArgcArgv() {
		$res = (bool)ini_get('register_argc_argv');

		return $res;
	}

	/**
	 * Maximum amount of time each script may spend executing
	 *
	 * @return int
	 */
	public function maxExecTime() {
		$res = (int)ini_get('max_execution_time');

		return $res;
	}

	/**
	 * HelperFunction to format 128M into the bytes
	 * Note: use CakeNumber here instead?
	 *
	 * @param string $val
	 * @return int
	 */
	public function returnInBytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		$val = (int)$val;
		switch ($last) {
				// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
				// Fallthrough
			case 'm':
				$val *= 1024;
				// Fallthrough
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

}
