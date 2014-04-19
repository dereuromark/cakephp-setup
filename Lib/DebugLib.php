<?php
App::uses('File', 'Utility');

/**
 * Used in configurations controller + debug helper
 */
class DebugLib {

	public $debugFileCache = array();

/** log files **/

	public function hasContent($type) {
		$file = TMP . 'logs' . DS . $type . '.log';
		if (isset($this->debugFileCache[$type])) {
			return $this->debugFileCache[$type];
		}
		if (!file_exists($file) || !($content = file_get_contents($file))) {
			$this->debugFileCache[$type] = false;
			return false;
		}
		$this->debugFileCache[$type] = $content;
		return true;
	}

	public function logFileContent($logFiles) {
		$logFileContent = array();
		foreach ($logFiles as $name) {
			$File = new File(TMP . 'logs' . DS . $name . '.log');

			if ($File->exists()) {
				$logFileContent[$name] = array(
					'name' => $File->name(),
					'size' => $File->size(),
					'content' => $File->read(),
					'modified' => $File->lastChange(),
					'file' => $File->name() . '.' . $File->ext(),
				);
			}
		}
		return $logFileContent;
	}

	/**
	 * http://stackoverflow.com/questions/1510141/read-last-line-from-file
	 * //TODO: test
	 */
	public function lastLinesOfFile($file, $lines = 10) {
		$line = '';

		$f = fopen($file, 'r');
		$cursor = -1;

		fseek($f, $cursor, SEEK_END);
		$char = fgetc($f);

		// Trim trailing newline chars of the file
		while ($char === "\n" || $char === "\r") {
			fseek($f, $cursor--, SEEK_END);
			$char = fgetc($f);
		}

		// Read until the start of file or first newline char
		while ($char !== false && $char !== "\n" && $char !== "\r") {
			// Prepend the new char
			$line = $char . $line;
			fseek($f, $cursor--, SEEK_END);
			$char = fgetc($f);
		}
		$lines[] = $line;
		return $lines;
	}

/** other **/

	/**
	 * @see http://www.php.net/manual/en/class.soapclient.php
	 */
	public function soap() {
		return class_exists('SoapServer') && class_exists('SoapClient');
	}

	/**
	 * @deprecated?
	 */
	public function serverLoad() {
		$x = $this->getServerLoad();
		if ($x === false) {
			return '<i>n/a (only for unix/linux server)</i>';
		}
		return '<b>' . $x[0] . ' - ' . $x[1] . ' - ' . $x[2] . '</b>';
	}

	/**
	 * Determines the server load (last 1 minute - last 5 minutes - last 15 minutes)
	 *
	 * @return string
	 */
	public function getServerLoad() {
		if (!WINDOWS) {
			$load = array();
			$res = (string) @exec('uptime');
			// last 1 minute : last 5 minutes : last 15 minutes
			if (preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $res, $load) <= 0) {

			}

			if (is_array($load) && count($load) > 2) {
				return array($load[1], $load[2], $load[3]);
			}
		}
		return false;
	}

	public $fillString = 'NA';

	public $useFillString = 1;

	/**
	 * @deprecated?
	 */
	public function serverUptime() {
		if (WINDOWS) {
			return $space;
		}
		$command = 'uptime';
		exec($command, $output, $status);
		if ($status !== 0) { # zero => success
			return array();
		}
		//$space = $output;
		//die(returns($space));
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
		$result = array();

		$fh = @fopen("/proc/uptime", "r");
		if (!is_resource($fh)) {
			return $result;
		}
		$buffer = split(" ", fgets($fh, 4096));
		fclose($fh);

		$sysTicks = trim($buffer[0]);

		$mins = $sysTicks / 60;
		$hours = $mins / 60;
		$days = floor($hours / 24);
		$hours = floor($hours - ($days * 24));
		$mins = floor($mins - ($days * 60 * 24) - ($hours * 60));

		if ($digit && ($mins < 10)) {
			$mins = "0$mins";
		}

		$result["days"] = $days;
		$result["hours"] = $hours;
		$result["mins"] = $mins;
		$result['timestamp'] = time() - (int)$sysTicks;

		return $result;
	}

	/**
	 * @return array (database, table, size in mb)
	 */
	public function getDatabaseSize($database = null, $table = null) {
		if ($database == null) {
			$db = $database = '%' . $db . '%';
		}
		$query = "SELECT TABLE_SCHEMA AS 'database', TABLE_NAME AS 'table',
ROUND(((DATA_LENGTH + INDEX_LENGTH - DATA_FREE) / 1024 / 1024), 2) AS size FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '" . $database .
			"', ";
		$res = mysql_query($query);
		if ($table) {
			//TODO

			// nothing found, return false
			return false;
		}
		return $res;
	}

	/**
	 * getKernelVersion
	 *
	 * Same as "uname --release"
	 *
	 * @return string Kernel-Version
	 */
	public function getKernelVersion() {
		if ($fh = @fopen("/proc/version", "r")) {
			$buffer = fgets($fh, 4096);
			fclose($fh);

			// search and grep the kernel-version
			if (preg_match("/version (.*?) /", $buffer, $matches)) {
				$result = $matches[1];
				if (preg_match("/SMP/", $buffer)) {
					$result .= " (SMP)";
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
		$results = array();
		$buffer = array();

		if (!($fh = @fopen("/proc/cpuinfo", "r"))) {
			return false;
		}

		$processors = -1;

		while ($buffer = fgets($fh, 4096)) {
			if (empty($buffer) || strpos($buffer, ':') === false) {
				continue;
			}
			list($key, $value) = explode(':', trim($buffer), 2); //preg_split("/\s+:\s+/", trim($buffer), 2);

			$key = trim($key);
			$value = trim($value);

			// Maybe you need some other tags if you run this on another architecture.
			// If you find or miss one, please tell me.
			switch ($key) {
				case "model name": // for ix86
					$results[$processors]['model'] = $value;
					break;
				case "cpu MHz":
					$results[$processors]['mhz'] = sprintf("%.2f", $value);
					break;
				case "clock": // for PPC
					$results[$processors]['mhz'] = sprintf("%.2f", $value);
					break;
				case "cpu": // for PPC
					$results[$processors]['model'] = $value;
					break;
				case "cpu cores": // for PPC
					$results[$processors]['cores'] = $value;
					break;
				case "revision": // for PPC arch
					$results[$processors]['model'] .= " ( rev: " . $value . ")";
					break;
				case "cache size":
					$results[$processors]['cache'] = $value;
					break;
				case "bogomips":
					if (!isset($results[$processors]['bogomips'])) {
						$results[$processors]['bogomips'] = 0;
					}
					$results[$processors]['bogomips'] += $value;
					break;
				case "processor":
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
	 * @return array(total=>x, free=>y, used=>z)
	 */
	public function getRam() {
		if (!($fh = @fopen("/proc/meminfo", "r"))) {
			return false;
		}
		$results = array();

		while ($buffer = fgets($fh, 4096)) {
			if (empty($buffer) || strpos($buffer, ':') === false) {
				continue;
			}
			list($key, $value) = explode(':', trim($buffer), 2);

			switch ($key) {
				case "MemTotal": // for ix86
					$results['total'] = round(sprintf("%d", $value) / 1024); // in MB
					break;
				case "MemFree":
					$results['free'] = round(sprintf("%d", $value) / 1024); // in MB
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
	 * @return int number of bytes ram currently in use. 0 if memory_get_usage() is not available.
	 * @static
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
	 * @return int peak memory useage (in bytes).  Returns 0 if memory_get_peak_usage() is not available
	 * @static
	 */
	public static function peakMemoryUsage($real = false) {
		if (!function_exists('memory_get_peak_usage')) {
			return 0;
		}
		return memory_get_peak_usage($real);
	}

	/**
	 * @return string MemoryLimit
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
	 * @static
	 */
	public function getmxrrAvailable() {
		return function_exists('getmxrr');
	}

	/**
	 * If available: Searches DNS for records of type type corresponding to host.
	 * @static
	 */
	public function checkdnsrrAvailable() {
		return function_exists('checkdnsrr');
	}

	/** test if exec commands are allowed **/
	public function execAllowed() {
		$command = 'echo XYZ';
		//$backupFile = APP.date("Y-m-d_H-i-s").'.txt';
		//$command = "mysqldump --opt -h $dbhost -u $dbuser -p $dbpass $dbname > $backupFile";

		$res = exec($command);
		//$s = system($command);
		return !empty($res) ? true : false;
	}

	/**
	 * @returns true on success, string error otherwise
	 */
	public function wgetAllowed() {
		$transport = 'wget';
		//$return_var = null;
		//passthru("wget --version", $return_var);
		$returnVar = `wget --version`;
		//die(returns($return_var));
		if (!empty($returnVar)) {
			return true;
		}
		return $returnVar;
	}

/** PHP Infos **/

	public function phpVersion() {
		return @phpversion();
	}

	public function phpTime() {
		return date('Y-m-d H:i:s', time());
	}

	/**
	 * @deprecated
	 */
	public function phpUptime() {
		return @exec('uptime');
	}

	public function mbStringOverload() {
		$mbOvl = null;
		if (extension_loaded('mbstring')) {
			$mbOvl = ini_get('mbstring.func_overload') != 0;
		}
		return $mbOvl;
	}

	public function mbDefLang() {
		$mbDefLang = null;
		if (extension_loaded('mbstring')) {
			$mbDefLang = strtolower(ini_get('mbstring.language')) === 'neutral';
		}
		return $mbDefLang;
	}

	public function loadedExtensions() {
		return get_loaded_extensions();
	}

	public function extensionLoaded($extensionName) {
		$e = $this->loadedExtensions();
		if (in_array(strtolower($extensionName), $e)) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the value from php.ini directly, while the ini_get returns the runtime config value
	 */
	public function configVar($var) {
		return get_cfg_var($var);
	}

	/**
	 * Returns the runtime config value
	 */
	public function runtimeVar($var) {
		return ini_get($var);
	}

	/**
	 * @return array with the names of the functions of a module or FALSE if extension is not available
	 */
	public function extensionFunctions($extension) {
		return get_extension_funcs($extension);
	}

	/**
	 * Open base dir path
	 * @return string
	 */
	public function openBasedir() {
		$var = ini_get('open_basedir');
		if (strpos($var, ':') !== false) {
			$paths = explode(':', $var);
		} else {
			$paths = array();
		}
		return $paths;
	}

	/**
	 * @return bool
	 */
	public function magicQuotesGpc() {
		$res = (bool)get_magic_quotes_gpc();
		return $res;
	}

	/**
	 * @return
	 */
	public function registerGlobals() {
		$res = (bool)ini_get('register_globals');
		return $res;
	}

	/**
	 * @return
	 */
	public function displayErrors() {
		$res = (bool)ini_get('display_errors');
		return $res;
	}

	public function allowUrlFopen() {
		$res = (bool)ini_get('allow_url_fopen');
		return $res;
	}

	/**
	 * 2010-07-30 ms
	 * @return bool
	 */
	public function fileUpload() {
		$res = (bool)ini_get('file_uploads');
		return $res;
	}

	/**
	 * 2010-07-30 ms
	 * @return string xM
	 */
	public function postMaxSize($inBytes = false) {
		$res = (int)ini_get('post_max_size');
		if ($inBytes) {
			return $this->returnInBytes($res);
		}
		return $res;
	}

	/**
	 * 2010-07-30 ms
	 * @return string xM
	 */
	public function uploadMaxSize($inBytes = false) {
		$res = ini_get('upload_max_filesize');
		if ($inBytes) {
			return $this->returnInBytes($res);
		}
		return $res;
	}

	/** Whether or not to register the old-style input arrays, HTTP_GET_VARS and friends.  If you're not using them, it's recommended to turn them off, for performance reasons.
	 */
	public function registerLongArrays() {
		$res = (bool)ini_get('register_long_arrays');
		return $res;
	}

	/**
	 * This directive tells PHP whether to declare the argv&argc variables (that would contain the GET information).  If you don't use these variables, you should turn it off for increased performance.
	 */
	public function registerArgcArgv() {
		$res = (bool)ini_get('register_argc_argv');
		return $res;
	}

	/**
	 * Maximum amount of time each script may spend executing
	 * @return int
	 */
	public function maxExecTime() {
		$res = (int)ini_get('max_execution_time');
		return $res;
	}

	/**
	 * Maximum amount of time each script may spend parsing request data
	 * @return int
	 */
	public function maxInputTime() {
		$res = (int)ini_get('max_input_time');
		return $res;
	}

	/**
	 * Allow the <? tag.  Otherwise, only <?php and <script> tags are recognized. Using short tags should be avoided when developing applications or libraries that are meant for redistribution
	 * @return bool
	 */
	public function shortOpenTag() {
		$res = (bool)ini_get('short_open_tag');
		return $res;
	}

	/**
	 * Should be OFF
	 * @return bool
	 */
	public function safeMode() {
		$res = (bool)ini_get('safe_mode');
		return $res;
	}

	/**
	 * Gets information about the server
	 * @return string  Server information
	 */
	public function serverSoftware() {
		if (isset($_SERVER['SERVER_SOFTWARE'])) {
			return $_SERVER['SERVER_SOFTWARE'];
		}
		if (($sf = getenv('SERVER_SOFTWARE'))) {
			return $sf;
		}
		return 'n/a';
	}

/** test stuff - or deprecated **/

	public function getOpenBasedir() {
		$ret = array('ok' => 0, 'value' => implode('<br/>', $tthis->openBasedir()), 'descr' => 'open basedir restrictions');
		return $ret;
	}

	/**
	 * displayErrors
	 * >= 5: ok
	 * >= 4: warning
	 * <4 : error
	 */
	/*
	public function getPhpVersion() {
	$v = $this->phpVersion();
	$ok = (int)$v;
	$ok = ($ok>=5?2:($ok>=4?1:-1));
	$ret = array(
	'ok' => $ok,
	'value' => $v,
	'descr'=>'should be 5 or higher'
	);
	return $ret;
	}
	*/

	/** Database Infos **/

	public function dbClientEncoding() {
		$link = mysql_connect('localhost', 'mysql_user', 'mysql_password');
		return mysql_client_encoding($link);
	}

	public function dbUptime() {
		$uptime = $this->Configuration->query('show status like "Uptime"');
		$value = $uptime[0]['STATUS']['Value'];
		$dbUptime = intval($value / 3600) . 'h ' . str_pad(intval(($value / 60) % 60), 2, '0', STR_PAD_LEFT) . 'm';
		return $dbUptime;
	}

	public function dbTime() {
		$time = $this->Configuration->query('select now() as datetime');
		return $time[0][0]['datetime'];
	}

	/**
	 * Uses DB query, foolprove!
	 * >= 5: OK
	 * < 5: error
	 */
	public function dbServerVersion() {
		$v = $this->_getDbServerVersion();
		$ok = (int)$v;
		$ok = ($ok >= 5 ? 2 : -1);
		$ret = array('ok' => $ok, 'value' => $v, 'descr' => 'must be 5 or higher');
		return $ret;
	}

	public function _getDbServerVersion() {
		//return @mysql_get_server_info(); # does not always work...
		$mysqlServerInfo = $this->Configuration->query('select version() as version'); # DateBase Version?
		return $mysqlServerInfo[0][0]['version'];
	}

	/** deprecated **/

	/**
	 * //should be in model?
	 * @return int: size in bytes
	 */
	public function fullDatabaseSize() {

		$tables = mysql_list_tables($database, $db);
		if (!$tables) {
			return - 1;
		}

		$tableCount = mysql_num_rows($tables);
		$size = 0;

		for ($i = 0; $i < $tableCount; $i++) {
			$tname = mysql_tablename($tables, $i);
			$r = mysql_query("SHOW TABLE STATUS FROM " . $database . " LIKE '" . $tname . "'");
			$data = mysql_fetch_array($r);
			$size += ($data['Index_length'] + $data['Data_length']);
		}
		return $size;
	}

	/** old **/

	/**
	 * Calculates the total size of a MySQL database in KB/MB or GB...
	 */
	public function calcFullDatabaseSize($database, $db) {

		$tables = mysql_list_tables($database, $db);
		if (!$tables) {
			return - 1;
		}

		$tableCount = mysql_num_rows($tables);
		$size = 0;

		for ($i = 0; $i < $tableCount; $i++) {
			$tname = mysql_tablename($tables, $i);
			$r = mysql_query("SHOW TABLE STATUS FROM " . $database . " LIKE '" . $tname . "'");
			$data = mysql_fetch_array($r);
			$size += ($data['Index_length'] + $data['Data_length']);
		}

		$units = array(' B', ' KB', ' MB', ' GB', ' TB');
		for ($i = 0; $size > 1024; $i++) {
			$size /= 1024;
		}
		return round($size, 2) . $units[$i];
	}

	/**
	 * HelperFunction to format 128M into the bytes
	 * Note: use CakeNumber here instead?
	 *
	 */
	public function returnInBytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		switch ($last) {
				// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

}
