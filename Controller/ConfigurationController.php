<?php
App::uses('Folder', 'Utility');
App::uses('FolderLib', 'Tools.Utility');

App::uses('File', 'Utility');
App::uses('SetupAppController', 'Setup.Controller');

/**
 * For XSS security:
 * http://ha.ckers.org/xssAttacks.xml
 *
 */
class ConfigurationController extends SetupAppController {

	public $components = array('Cookie');

	public $DebugLib = null;

	public function beforeFilter() {
		parent::beforeFilter();

		if (isset($this->Auth)) {
			$this->Auth->allow('index', 'uptime', 'uptime_status', 'admin_cache', 'admin_clearcache', 'admin_clearjs', 'admin_clearcss', 'admin_check_mail');
		}

		// temp:
		//$this->Auth->allow();

		App::uses('DebugLib', 'Setup.Lib');
		$this->DebugLib = new DebugLib();
	}

/*** user ***/

	public function index() {
	}

	/**
	 * Check uptime with http://www.livewatch.de
	 */
	public function uptime() {
		if (!isset($_REQUEST['key'])) {
			echo "Alles OK";
		} else {
			if (preg_match('/^[a-f0-9]{32}$/', $_REQUEST['key'])) {
				echo $_REQUEST['key'];
			}
		}
		die();
	}

	/**
	 * Uptime status (public display is necessary for free service to work)
	 */
	public function uptime_status() {

		$this->admin_uptime();
	}

	public function admin_uptime() {
		$uptimeId = Configure::read('Livewatch.id');
		$out = '<html><body>';
		if ($uptimeId) {
			$out .= '<a href="http://www.livewatch.de" target="_blank"><img src="http://www.server-uptime.de/uptime/' . $uptimeId . '" border="0" alt="Serverüberwachung / Servermonitoring mit Livewatch.de"></a>';
		}
		$out .= '</body></html>';
		$this->response->body($out);
		$this->response->send();
		die();
	}

/*** admin ***/

	/**
	 * Session infos
	 */
	public function admin_session() {
	}

	public function admin_cookies() {
		$cookies = $this->Cookie->read();
		$this->set(compact('cookies'));
	}

	public function admin_clearcookies($key = null) {
		if (!empty($this->request->data['Form']['key'])) {
			$this->Cookie->write($this->request->data['Form']['key'], $this->request->data['Form']['content']);
			$this->Common->flashMessage('OK', 'success');
			$this->request->data = array();
		}

		if ($this->request->query('reset')) {
			$this->Cookie->destroy();
			$this->Common->flashMessage('Done', 'success');
			return $this->redirect(array('action' => 'clearcookies'));
		}

		if (!empty($key)) {
			$key = urldecode($key);
			$key = str_replace('|', '.', $key);
			$this->Cookie->delete($key);
			$this->Common->flashMessage('Done', 'success');
			return $this->redirect(array('action' => 'clearcookies'));
		}

		$cookieData = $this->Cookie->read();

		$this->set(compact('cookieData'));
	}

	public function admin_clearsession($key = null) {
		if ($this->request->query('reset')) {
			$this->Session->destroy();
			$this->Common->flashMessage('Done', 'success');
			return $this->redirect(array('action' => 'clearsession'));
		}

		if (!empty($key)) {
			$key = urldecode($key);
			$key = str_replace('|', '.', $key);
			$this->Session->delete($key);
			$this->Common->flashMessage('Done', 'success');
			return $this->redirect(array('action' => 'clearsession'));
		}
	}

	/**
	 * Session and cookie content
	 * readonly!
	 */
	public function admin_global_vars() {
		$globalVars = array();
		$globalVars['session'] = $this->Session->read();
		$globalVars['cookie'] = $this->Cookie->read();

		$this->set(compact('globalVars'));
	}

	/**
	 * Linux only?
	 */
	public function admin_disk_space() {
		App::uses('SystemLib', 'Setup.Lib');
		$this->System = new SystemLib();

		$appPath = ROOT . DS . APP_DIR;

		$freeSpace = $this->System->freeDiskSpace();

		$space['app'] = $this->System->diskSpace($appPath);

		$space['cake'] = $this->System->diskSpace(ROOT . DS . 'lib' . DS . 'Cake' . DS);

		$space['vendors'] = $this->System->diskSpace(VENDORS);
		// Cache now!

		$this->set(compact('freeSpace', 'space', 'appPath'));
	}

	public function admin_cache() {
	}

	public function admin_locale() {
		if ($this->Common->isPosted()) {
			$dateString = time();
			$dateFormat = $this->request->data['Form']['format'];
			$locale = $this->request->data['Form']['locale'];
			$res = setlocale(LC_TIME, $locale);
			if ($res === false) {
				$this->Common->flashMessage('Locale not supported', 'warning');
			}
			App::uses('TimeLib', 'Tools.Utility');
			$result = TimeLib::localDate($dateString, $dateFormat);
			$this->set(compact('result'));
		} else {
			$this->request->data['Form']['format'] = '%A, %B %Y - %H:%M';
		}

		$save = setlocale(LC_ALL, 0);
		if (WINDOWS) {
			$localeOptions = array('german', 'english', 'french', 'spanish', 'russian', 'austria', 'switzerland', 'turkish'); # windows
		} else {
			$localeOptions = array('de_DE.utf8', 'de_CH.utf8', 'de_AT.utf8', 'de_BE.utf8', 'de_LU.utf8', 'de_LI.utf8', 'en_US.utf8', 'en_GB.utf8', 'tr_TR.utf8'); # linux
		}

		$localeSettings = array();
		foreach ($localeOptions as $option) {
			$res = setlocale(LC_ALL, $option);
			$content = $res === false ? array() : localeconv();
			$localeSettings[$option] = array('res' => $res, 'content' => $content);
		}

		App::uses('SystemLib', 'Setup.Lib');
		$this->System = new SystemLib();
		$systemLocales = $this->System->systemLocales();
		$this->set(compact('localeSettings', 'systemLocales'));

		setlocale(LC_ALL, $save);
	}

	/**
	 * Defined stuff
	 */
	public function admin_vars() {
		echo 'defined_vars:';
		$vars = get_defined_vars();
		pr($vars);

		echo BR . BR;

		echo 'defined_functions:';
		$vars = get_defined_functions();
		pr($vars);

		echo BR . BR;

		echo 'defined_constants:';
		$vars = get_defined_constants();
		pr($vars);

		echo BR . BR;

		echo 'declared_classes:';
		$vars = get_declared_classes();
		pr($vars);

		echo BR . BR;

		echo 'declared_interfaces:';
		$vars = get_declared_interfaces();
		pr($vars);

		//echo BR.BR;
		//echo 'object_vars:';
		//$vars = get_object_vars($this);
		//pr ($vars);

		$this->autoRender = false;
	}

	/**
	 * Universal mail tester
	 */
	public function admin_mail() {
		//Configure::write('debug', 0);
		//die('E');
		$this->Contact = ClassRegistry::init('Tools.ContactForm');
		$this->Contact->validate['to_email'] = $this->Contact->validate['from_email'] = $this->Contact->validate['reply_email'] = $this->Contact->validate['email'];
		if ($this->request->query('reset')) {
			$this->Session->delete('ConfigurationTest');
			$this->Common->flashMessage('Email config back to defaults', 'success');
			return $this->redirect(array('action' => 'mail'));
		}

		if ($this->Common->isPosted()) {
			$this->Contact->set($this->request->data);
			if ($this->Contact->validates()) {
				//die(returns($this->request->data));
				$this->Session->write('ConfigurationTest', $this->request->data);
				foreach ($this->request->data['Mail'] as $key => $config) {
					Configure::write('Mail.' . $key, $config);
				}

				App::uses('EmailLib', 'Tools.Lib');
				$this->Email = new EmailLib();
				$this->Email->to($this->request->data['ContactForm']['to_email'], $this->request->data['ContactForm']['to_name']);
				$this->Email->replyTo($this->request->data['ContactForm']['reply_email'], $this->request->data['ContactForm']['reply_name']);
				$this->Email->from($this->request->data['ContactForm']['from_email'], $this->request->data['ContactForm']['from_name']);
				$attachments = 0;
				if (!empty($this->request->data['ContactForm']['attachment']['tmp_name'])) {
					$attachments++;
					$this->Email->addAttachment($this->request->data['ContactForm']['attachment']['tmp_name'], $this->request->data['ContactForm']['attachment']['name']);
				}
				//echo pre($this->Email->attachments); die();

				$this->Email->subject($this->request->data['ContactForm']['subject']);
				$this->Email->viewVars(array('text' => $this->request->data['ContactForm']['message']));

				$this->Email->template('simple_email');

				if ($this->Email->send()) {
					$this->Common->flashMessage('Email sent', 'success');
					$this->Common->flashMessage($attachments . ' attachments', 'info');
				} else {
					$this->Common->flashMessage('Email could not get sent', 'error');
				}
			}

		} elseif ($sessionData = $this->Session->read('ConfigurationTest')) {
			$this->request->data['ContactForm'] = $sessionData['ContactForm'];
			$this->request->data['Mail'] = $sessionData['Mail'];

		} else {
			if (Auth::hasRole(ROLE_SUPERADMIN)) {
				$this->request->data['Mail'] = Configure::read('Mail');
			}
			$this->request->data['ContactForm']['from_email'] = Configure::read('Config.adminEmail');
			$this->request->data['ContactForm']['from_name'] = Configure::read('Config.adminEmailname');
			$this->request->data['ContactForm']['reply_email'] = Configure::read('Config.noReplyEmail');
			$this->request->data['ContactForm']['reply_name'] = Configure::read('Config.noReplyEmailname');
			$this->request->data['ContactForm']['subject'] = 'Test';
			$this->request->data['ContactForm']['message'] = 'Dies ist ein äußerst sinnvoller Test.';
		}
	}

	/**
	 * Send test email
	 */
	public function admin_check_mail($email = null, $username = null) {
		if (empty($email)) {
			$email = Configure::read('Config.adminEmail');
		}
		if (empty($username)) {
			$username = Configure::read('Config.adminEmailname');
		}

		App::uses('EmailLib', 'Tools.Lib');
		$res = $this->_checkOwnMail($email, $username);
		/*
		} else {
			$res = $this->_checkCoreMail($email, $username);
		}
		*/
		echo returns($res);

		$this->autoRender = false;
	}

	public function admin_serverlogs() {
		$defaultSettings = array(
			'path' => ROOT,
			'ext' => 'log',
			'files' => array('error') // 'referer', 'access' TOO BIG
		);
		$settings = (array)Configure::read('ServerLog');
		$settings = array_merge($defaultSettings, $settings);

		if (!endsWith($settings['path'], DS)) {
			$settings['path'] .= DS;
		}

		$logFileContent = array();
		foreach ($settings['files'] as $name) {
			$log = $settings['path'] . $name . '.' . $settings['ext'];

			if (file_exists($log)) {
				$file = new File($log);
				$logFileContent[$name] = array(
						'size' => ($file->size()),
						'content' => $file->read(),
						'modified' => $file->lastChange(),
						'file' => $file->name() . '.' . $file->ext(),
					);
			}
		}
		$this->_setShowCount(50);
		$this->set(compact('logFileContent'));
	}

	public function admin_trace_file($name = null) {
		$filename = TMP . 'logs' . DS . 'traces' . DS . $name . '.log';
		if (file_exists($filename)) {
		$h = file_get_contents($filename);
		echo nl2br(h($h));
		}
		$this->autoRender = false;
	}

	/**
	 */
	public function admin_sql_logs() {
		$folder = TMP . 'logs' . DS . 'sql' . DS;
		$Handle = new Folder($folder);
		$content = $Handle->read(true, true);
		$files = $content[1];

		if ($this->request->query('reset')) {
			foreach ($files as $file) {
				unlink($folder . $file);
			}
			$this->Common->flashMessage(__('logFileEmptied'), 'success');
			return $this->redirect(array('action' => 'sql_logs'));
		}

		$sqlContent = array();
		$count = 0;
		foreach ($files as $file) {
			if ($count > 100) {
				break;
			}
			$count++;
			$sqlContent[] = $this->_parse($folder . $file);
		}
		// newest on top
		$sqlContent = array_reverse($sqlContent);

		$this->set(compact('sqlContent'));
		$this->Common->loadHelper(array('Tools.Geshi'));
	}

	/**
	 * @return array(header, time, location, data)
	 */
	protected function _parse($u) {
		$content = file_get_contents($u);
		//die(returns(h($content)));
		$res = array();
		$lines = explode(NL, $content);
		$header = array_shift($lines);
		//$content = implode(NL, $lines);

		$fileInfo = explode('_', extractPathInfo('file', basename($u)));

		$res['header'] = $header;
		$res['time'] = $fileInfo[1]; //filemtime($u);
		$res['location'] = $fileInfo[2];
		// import like csv file
		$tmp = array();
		foreach ($lines as $line) {
			$t = explode(TB, $line);
			if (count($t) < 6) {
				continue;
			}

			// remove garbige
			if (!empty($t[1]) &&
				(strpos($t[1], 'SHOW FULL ') === 0 || strpos($t[1], 'SELECT CHARACTER_SET_NAME FROM ') === 0)
			) {
				continue;
			}

			$tmp[] = $t;
			//$res[''] = ;
		}
		$res['data'] = $tmp;
		return $res;
	}

	public function admin_logs() {
		ini_set('memory_limit', '128M');
		$defaultLogFiles = array('error', 'debug'); // sorted by priority

		$logFiles = (array)Configure::read('System.logFiles');
		$logFiles = array_merge($defaultLogFiles, $logFiles);
		$logFileContent = $this->DebugLib->logFileContent($logFiles);

		$Handle = new Folder(TMP . 'logs');
		$files = $Handle->read(true, true);
		$logFiles = $files[1];
		foreach ($logFiles as $key => $logFile) {
			if (!$this->DebugLib->hasContent($filename = extractPathInfo('file', $logFile))) {
				unset($logFiles[$key]);
			}
			$logFiles[$key] = $filename;
		}

		// not listed ones will be listed beneight them (NO, right now only those above are read out!)
		//$this->log('dfdf', LOG_NOTICE);
		//CakeLog::write('mail', 'teststring');

		if ($this->request->query('empty')) {
			if ($this->request->query('empty') === 'all') {
				App::uses('FolderLib', 'Tools.Utility');
				$Folder = new FolderLib(TMP . 'logs' . DS);
				$Folder->clear();
				$this->Common->flashMessage(__('logFileEmptied'), 'success');
				return $this->redirect(array('action' => 'logs'));
			}
			if (in_array($this->request->query('empty'), $logFiles)) {
				$File = new File(TMP . 'logs' . DS . $this->request->query('empty') . '.log');
				$File->delete();
				$this->Common->flashMessage(__('logFileEmptied'), 'success');
				return $this->redirect(array('action' => 'logs'));
			}
		}

		$this->_setShowCount(10);
		$this->set(compact('logFiles', 'logFileContent'));
	}

	public function admin_log($type = null) {
		if ($this->request->query('empty')) {
			return $this->redirect(array('action' => 'logs', '?' => array('empty' => $this->request->query('empty'))));
		}
		if (empty($type) || !$this->DebugLib->hasContent($type)) {
			$this->Common->flashMessage(__('logFileDoesNotExist'), 'error');
			return $this->redirect(array('action' => 'logs'));
		}

		$logFileContent = $this->DebugLib->logFileContent(array($type));
		$this->_setShowCount(10);
		$this->set(compact('logFileContent'));
	}

	// ???

	protected function _setShowCount($default = 10, $returnOnly = false) {
		if ($this->request->query('show')) {
			$show = (int)$this->request->query('show');
		}
		if (empty($show) || $show <= 0) {
			$show = $default;
		}

		if ($returnOnly === true) {
			return $show;
		}
		$this->set(compact('show'));
	}

	public function admin_index() {
		if ($this->Configuration->useTable) {
			$configurations = $this->paginate();
			$this->set(compact('configurations'));
		}

		$uptime = $this->DebugLib->getUptime();
		$serverLoad = $this->DebugLib->serverLoad();
		$mem = $this->DebugLib->getRam();
		$memory = '<i>n/a</i>';
		if ($mem) {
			$memory = '' . $mem['total'] . ' MB total; ' . $mem['free'] . ' MB free';
		}
		$this->set(compact('serverLoad', 'memory', 'uptime'));
		$this->helpers[] = 'Number';
	}

	public function admin_active() {
		if ($this->Configuration->useTable) {
			$configuration = $this->Configuration->getActive();
			$this->set(compact('configuration'));
		}
	}

	public function admin_view($id = null) {
		if (!$id || !($configuration = $this->Configuration->get($id))) {
			$this->Common->flashMessage(__('Invalid Configuration.'), 'error');
			return $this->redirect(array('action' => 'index'));
		}
		$this->set(compact('configuration'));
	}

	public function admin_edit($id = null) {
		if (!$id || !($configuration = $this->Configuration->get($id))) {
			$this->Common->flashMessage(__('Invalid Configuration'), 'error');
			return $this->redirect(array('action' => 'index'));
		}

		if ($this->Common->isPosted()) {
			if ($this->Configuration->save($this->request->data)) {
				$this->Common->flashMessage(__('The Configuration has been saved'), 'success');
				return $this->redirect(array('action' => 'index'), 'success');
			}
			$this->Common->flashMessage(__('The Configuration could not be saved. Please, try again.'), 'error');
		}
		if (empty($this->request->data)) {
			$this->request->data = $this->Configuration->get($id);
		}
	}

	public function admin_add() {
		if ($this->Common->isPosted()) {
			$this->Configuration->create();
			if ($this->Configuration->save($this->request->data)) {
				$this->Common->flashMessage(__('The Configuration has been saved'), 'success');
				return $this->redirect(array('action' => 'index'));
			}
			$this->Common->flashMessage(__('The Configuration could not be saved. Please, try again.'), 'error');

		}
	}

	public function admin_delete($id = null) {
		if (!$id) {
			$this->Common->flashMessage(__('Invalid id for Configuration'), 'error');
			return $this->redirect(array('action' => 'index'));
		}
		if ($this->Configuration->delete($id)) {
			$this->Common->flashMessage(__('Configuration deleted'), 'success');
			return $this->redirect(array('action' => 'index'));
		}
	}

	public function admin_status() {
		$this->set('active_config', $this->Configuration->getActive());
	}

	public function admin_sql() {
		if ($this->Common->isPosted()) {

			if (!empty($this->request->data['Configuration']['sql'])) {
				$queries = array();

				## normal txt sql ##

				// Cleaning and dividing it up
				$tmpQueries = explode(";", $this->request->data['Configuration']['sql']);
				// removing comments? (-- at the beginning)
				// ...

				// insert into DB
				foreach ($tmpQueries as $query) {
					$newstring = '';
					//$realquery=explode("\n", $tmp_query);

					$real = trim($query);
					if (!empty($real) && substr($real, 0, 2) !== '--') {
						$newstring .= $real;
					}

					if (!empty($newstring)) {
						try {
							$res = $this->Configuration->query($newstring);

							$count = (int)$this->Configuration->getAffectedRows(); //die(returns($count));
							if ($count === null) {
							//$db = $this->getDataSource();
								//$count = mysql_affected_rows();
							}
						} catch (Exception $e) {
							$this->Common->flashMessage(h($newstring) . ': ' . $e->getMessage(), 'error');
							$count = null;
						}
						$queries[] = h($newstring) . ($count !== null ? ' (affected: ' . $count . ')' : '');
					}
				}

				$count = count($queries);
				if ($count > 0) {
					$this->set('queries', $queries);
					$this->Common->flashMessage(__('The sql content (<b>' . $count . ' queries total</b>) has been inserted run (each single query is shown below)'), 'success');
				}
			}
		}
		//$this->Common->loadHelper(array('Tools.Jquery'));
	}

	//TODO move to lib!

	protected function _sqlDump($folder, $tables = array()) {
		// TODO
		$config = $this->Configuration->useDbConfig;
		$db = ConnectionManager::getDataSource($config);

		$dbname = $db->config['database'];
		$dbhost = $db->config['host'];
		$dbuser = $db->config['login'];
		$dbpass = $db->config['password'];

		if (!is_dir($folder)) {
			mkdir($folder, 0755, true);
		}
		if (!is_dir($folder)) {
			return false;
		}

		$backupFile = $folder . $dbname . '_' . date("Y-m-d-H-i-s") . '.sql.gz';

		if (WINDOWS) {
			return false;
		}

		$tableList = '';
		if (!empty($tables)) {
			$tableList = ' --tables ' . implode(' ', $tables);
		}

		$command = "mysqldump --opt --host=$dbhost --user=$dbuser --password=$dbpass --databases $dbname" . $tableList . " | gzip > $backupFile";
		/*
		exec($command, $output, $ret);
		echo returns($command);
		echo returns($output);
		die(returns($ret));
		*/
		$lastLine = system($command, $retVal);
		if ($lastLine === false || !file_exists($backupFile)) {
			return false;
		}
		return array('last' => $lastLine, 'ret' => $retVal);
	}

	public function admin_constants() {
	}

	public function admin_superglobals() {
	}

	public function admin_phpinfo() {
		$this->layout = 'ajax';
	}

	/**
	 * //TODO move to lib
	 * check if folders are available AND if rights are set correctly
	 * otherwise automatic folder creation and change of rights
	 * @TODO: with or without \\ at the end?? right now WITH
	 * Note: 0777 write to folder | 0755 read only
	 */
	public function admin_setup() {
		$folders = array(
			WWW_ROOT . 'js' . DS . 'tmp' . DS => 0777,	// 'tmp js/ajax files'
			//WWW_ROOT.'tmp' => 0777,	// 'tmp files'
			//WWW_ROOT.'tmp'.DS.'cache' => 0777,	// 'tmp files'
		);
		$folderinfo = array();
		App::uses('ChmodLib', 'Tools.Utility');

		foreach ($folders as $folder => $rights) {
			$mode = ChmodLib::convertToOctal($rights);
			if (!is_dir($folder)) {
				//$old = umask(0);
				//pr ($old);
				$rs = @mkdir($folder, $mode);
				//@handleError();
				if ($rs) {
					$perms = $this->_filePerms($folder);
					$folderinfo[] = array('folder' => $folder, 'perms' => $perms, 'rights' => ChmodLib::convertFromOctal($mode, true), 'success' => 1, 'message' => 'file did not exist - successfully created');

				} else {
					$folderinfo[] = array('folder' => $folder, 'rights' => ChmodLib::convertFromOctal($mode, true), 'success' => 0, 'message' => 'file did not exist - but could not be created either');
				}

			} else {
				$perms = $this->_filePerms($folder);

				if ($perms != ChmodLib::convertFromOctal($mode, true) && HTTP_HOST !== 'localhost') {
					$folder = new Folder();
					if ($folder->chmod($folder->pwd(), $mode, false)) {
						$text = 'but could be fixed!';
					} else {
						$text = 'and could not be fixed!';
					}
					$folderinfo[] = array('folder' => $folder, 'perms' => $perms, 'rights' => ChmodLib::convertFromOctal($mode, true), 'success' => 2, 'message' => 'file existed, rights were not ok (' . $text . ')');
				} else {

					$folderinfo[] = array('folder' => $folder, 'perms' => $perms, 'rights' => ChmodLib::convertFromOctal($mode, true), 'success' => 3, 'message' => 'file existed, rights are ok');
				}
			}

		}

		$this->set(compact('folderinfo'));
	}

	protected function _filePerms($folder) {
		$perms = fileperms($folder);	// leading zero automatically attached
		return ChmodLib::convertFromOctal($perms);
	}

	public function admin_encoding() {
		$stringInfos = mb_get_info();

		$this->set(compact('stringInfos'));
	}

	/**
	 * All about time settings
	 */
	public function admin_time() {
	}

	public function admin_clearjs() {
		$folder = WWW_ROOT . 'js' . DS . 'cjs' . DS;
		$Handle = new Folder($folder);

		$res = $Handle->read(false, true, true);
		$count = 0;

		foreach ($res[1] as $r) {
			unlink($r);
			$count++;
		}

		$this->Common->flashMessage(__('cjs cleared %s', $count), 'success');
		return $this->Common->autoRedirect(null);
	}

	public function admin_clearcss() {
		$folder = WWW_ROOT . 'css' . DS . 'ccss' . DS;
		$Handle = new Folder($folder);
		$res = $Handle->read(false, true, true);
		$count = 0;

		foreach ($res[1] as $r) {
			unlink($r);
			$count++;
		}

		$this->Common->flashMessage(__('ccss cleared %s', $count), 'success');
		return $this->Common->autoRedirect(null);
	}

	/**
	 * Clear Cache (model, views, persistant)
	 * @param silent | if set, automatically returns to the current page
	 */
	public function admin_clearcache() {
		$empty = array('models', 'views', 'persistent');
		$output = '';
		foreach ($empty as $e) {
			$output .= $this->_clearcache($e);
		}
		$this->Common->flashMessage('Cache is now empty again', 'success');

		if (!empty($this->request->params['pass']) && in_array('silent', $this->request->params['pass'])) {
			return $this->Common->autoRedirect(null);
		}
		$this->set('deleted_caches', $output);
	}

	protected function _clearcache($verzeichnis) {

		$zuLeerendesVerzeichnis = TMP . 'cache' . DS . $verzeichnis . '';

		$return = 'Ordner <b>' . $verzeichnis . '</b>:<br/>';
		$Handle = opendir($zuLeerendesVerzeichnis);
		while ($file = readdir($Handle)) {
			if ($file !== "." && $file !== "..") {
				unlink($zuLeerendesVerzeichnis . DS . $file);
				$return .= $file . '<br/>';
			}
		}
		$return .= '<br/>';
		closedir($Handle);
		// clearCache() -> geht nicht, deswegen workaround
		return $return;
	}

	public function admin_tables() {
		$config = $this->Configuration->useDbConfig;
		$db = ConnectionManager::getDataSource($config);

		$database = $db->config['database'];//'cake_tel';
		$tables = $this->Configuration->query('SHOW TABLES FROM ' . $database . ' LIKE \'' . $this->Configuration->tablePrefix . '%\'');

		//$key = ConnectionManager::getSourceName($this) . '_' . $database . '_list'; // $this->Configuration->config['database']
		//$key = preg_replace('/[^A-Za-z0-9_\-.+]/', '_', $key);
		//$sources = Cache::read($key, '_cake_model_');
		//$sources = Cache::read('cake_model_default_cake_tel_list');

		//pr ($tables);

		foreach ($tables as $table) {

			$tableName = $table['TABLE_NAMES']['Tables_in_' . $database . ' (' . $this->Configuration->tablePrefix . '%)'];
			$c = $this->Configuration->query('Select COUNT(*) as count FROM ' . $tableName);

			$tableName = mb_substr($tableName, mb_strlen($this->Configuration->tablePrefix));
			$tableCount[$tableName] = $c[0][0]['count'];
		}
		// Cache it?

		$this->set(compact('tables', 'tableCount'));
	}

	/**
	 * Read out tables and display them
	 * USEFUL FOR: batch prefix_renaming!?
	 */
	public function admin_db_tables() {
		$dbTables = $this->_getDbServerTableStatus();

		//$resetableTables = array('user_infos', 'news', 'code_keys', 'comments', 'contribution_lists', 'contribution_list_items', 'documents', 'events', 'feature_requests', 'fliers', 'log_ips', 'log_referers', 'map_markers', 'message_options', 'messages', 'notifications', 'online_activities', 'relations');
		$persistentTables = array('users', 'roles', 'news_categories', 'role_users', 'smileys', 'countries', 'country_provinces', 'dropdowns', 'dropdown_cats', 'configuration');

		if ($this->Common->isPosted()) {
			$truncated = 0;
			foreach ($this->request->data['Form'] as $key => $form) {
				if ($form == 1) {
					if ($this->Configuration->truncate($key)) {
						$truncated++;
					}
				}
			}
			if ($truncated > 0) {
				$this->Common->flashMessage(__('%s truncated', $truncated), 'success');
				return $this->redirect(array());
			}

		} else {
			if ($this->request->query('preset')) {
				$preset = true;
			}
		}

		foreach ($dbTables as $key => $t) {
			$tableName = mb_substr($t['TABLES']['Name'], mb_strlen($this->Configuration->tablePrefix));
			$dbTables[$key]['TABLES']['Name'] = $tableName;
			if (!empty($preset) && !in_array($tableName, $persistentTables)) {
				$this->request->data['Form'][$tableName] = 1;
			}
		}

		$tablePrefix = $this->Configuration->tablePrefix;
		$this->set(compact('dbTables', 'tablePrefix'));
	}

	public function admin_backup() {
		App::uses('BackupLib', 'Setup.Lib');
		$backup = new BackupLib($this->Configuration);
		//$files = $backup->listBackupFiles();
		$tables = $backup->listTables();
		$tables = array_combine(array_values($tables), array_values($tables));
		$ownTables = $backup->listTables(true);

		if ($this->Common->isPosted()) {
			set_time_limit(HOUR);
			if ($this->_sqlDump(FILES . 'backup' . DS, $this->request->data['Configuration']['tables'])) {
				$this->Common->flashMessage('Backup-File saved', 'success');
			} else {
				$this->Common->flashMessage('Backup-File not saved', 'error');
			}
		} else {
			foreach ($ownTables as $table) {
				$this->request->data['Configuration']['tables'][] = $table;
			}
		}

		$this->set(compact('tables'));
	}

	/**
	 * All about environment/server and databse settings
	 */
	public function admin_environment() {

	/*
		@list($system, $host, $kernel) = preg_split('/[\s, ]+/', @exec('uname -a'), 5);
		pr ($system);
		pr ($host);
		pr ($kernel);
	*/

		// TODO: TMP FOLDERS: MODE 777
		// !is_dir()
		$ok = (is_writable(TMP) ? 1 : 0);
		// ...
		// + all subpaths!

		// TODO: ADDONS
		// zip, pdf, img,

		// TODO: SMPT STUFF
/*
	if ($vbulletin->options['use_smtp']) {
		print_table_header($vbphrase['pertinent_smtp_settings']);
		print_label_row('SMTP:', (!empty($vbulletin->options['smtp_tls']) ? 'tls://' : '') . $vbulletin->options['smtp_host'] . ':' . (!empty($vbulletin->options['smtp_port']) ? intval($vbulletin->options['smtp_port']) : 25));
		print_label_row($vbphrase['smtp_username'], $vbulletin->options['smtp_user']);
	} else {
		print_table_header($vbphrase['pertinent_php_settings']);
		print_label_row('SMTP:', iif($SMTP = @ini_get('SMTP'), $SMTP, '<i>' . $vbphrase['none'] . '</i>'));
		print_label_row('sendmail_from:', iif($sendmail_from = @ini_get('sendmail_from'), $sendmail_from, '<i>' . $vbphrase['none'] . '</i>'));
		print_label_row('sendmail_path:', iif($sendmail_path = @ini_get('sendmail_path'), $sendmail_path, '<i>' . $vbphrase['none'] . '</i>'));
	}
*/

		// ok: 0 = no rating, 2 = YES, 1 = WARNING, -1 NO
		$serverinfo = array(

			// Infos DB
			'DB_VERSION' => $this->_dbServerVersion(),
			'DB_TIME' => array(
				'ok' => 0,
				'value' => $this->_dbTime(),
				'descr' => 'Current Database Time'
			),
			'DB_UPTIME' => array(
				'ok' => 0,
				'value' => $this->_dbUptime(),
				'descr' => 'Uptime of Database'
			),

			'DB_CLIENT_ENCODING' => array(
				'ok' => 0,
				'value' => $this->_dbClientEncoding(),
				'descr' => 'usually latin1'
			),

			// Infos PHP

			'PHP_VERSION' => $this->_phpVersion(),

			'PHP_TIME' => array(
				'ok' => 0,
				'value' => $this->DebugLib->phpTime(),
				'descr' => 'Current Apache (PHP) Time'
			),
			'PHP_UPTIME' => array(
				'ok' => 0,
				'value' => $this->DebugLib->phpUptime(),
				'descr' => 'Uptime of Apache (PHP)'
			),
			'WEBSERVER' => array(
				'ok' => 0,
				'value' => $this->DebugLib->serverSoftware(),
				'descr' => ''
			),
			'PHP_BUILD_N' => array(
				'ok' => 0,
				'value' => @php_uname(),
				'descr' => ''
			),
			'PHP_INTERFACE' => array(
				'ok' => 0,
				'value' => @php_sapi_name(),
				'descr' => ''
			),
			'ZEND_VERSION' => array(
				'ok' => 0,
				'value' => @zend_version(),
				'descr' => ''
			),
			'OPEN_BASEDIR' => $this->_openBasedir(),
			'TMP_UPLOAD_DIR' => array(
				'ok' => 0,
				'value' => ini_get('upload_tmp_dir'),
				'descr' => 'the dir to store tmp information into'
			),
			//'SERVER_STRUCTURE' => $this->_getServerStructure('/'), // try

			// Settings (important)

			'SHORT_OPEN_TAG' => $this->_shortOpenTag(),

			'FILE_UPLOAD' => $this->_fileUpload(),
			'UPLOAD_MAX_FILESIZE' => $this->_uploadMaxSize(),

			'ALLOW_URL_FOPEN' => $this->_allowUrlFopen(),

			'register_long_arrays' => $this->_registerLongArrays(),
			'register_argc_argv' => $this->_registerArgcArgv(),

			'max_execution_time' => $this->_maxExecTime(),
			'max_input_time' => $this->_maxInputTime(),

			'MAGIC_QUOTES' => $this->_magicQuotesGpc(),

			'output_buffering' => array(	// should be off
				'ok' => 0,
				'value' => ini_get('output_buffering') ? 1 : 0,
				'descr' => 'outpuff buffering'
			),

			'session.auto_start' => array(	// should be off
				'ok' => 0,
				'value' => ini_get('session.auto_start') ? 1 : 0,
				'descr' => 'session.auto_start'
			),

			'DISPLAY_ERRORS' => $this->_displayErrors(),
			'POST_MAX_SIZE' => $this->_postMaxSize(),

			'REGISTER_GLOBALS' => $this->_registerGlobals(),

			'MEMORY_LIMIT' => $this->_memoryLimit(),
			'MEMORY_LIMIT_ADJUSTABLE' => $this->_memoryLimitAdjustable(),

			'XML_SUPPORT' => array(
				'ok' => 0,
				'value' => extension_loaded('xml') ? 1 : 0,
				'descr' => 'XML-Support'
			),
			'ZLIB_SUPPORT' => array(
				'ok' => 0,
				'value' => extension_loaded('zlib') ? 1 : 0,
				'descr' => 'ZLIB-Compression-Support'
			),
			'ICONV_SUPPORT' => array(
				'ok' => 0,
				'value' => extension_loaded('iconv') ? 1 : 0,
				'descr' => 'iconv-Support'
			),
			'GD' => array(
				'ok' => function_exists('imagecreatefromgif') ? 2 : 1,
				'value' => function_exists('imagecreatefromgif') ? 1 : 0,
				'descr' => 'GB Library'
			),
			'ImageMagick' => array(
				'ok' => class_exists('Imagick') ? 2 : 1,
				'value' => class_exists('Imagick') ? 1 : 0,
				'descr' => 'ImageMagick'
			),
			'pcre_SUPPORT' => array(
				'ok' => 0,
				'value' => extension_loaded('pcre') ? 1 : 0,
				'descr' => 'pcre-Support'
			),
			'curl_SUPPORT' => array(
				'ok' => (extension_loaded('curl') && function_exists('curl_init')) ? 2 : 1,
				'value' => (extension_loaded('curl') && function_exists('curl_init')) ? 1 : 0,
				'descr' => 'curl-Support'
			),
			'curl_exec_SUPPORT' => array(
				'ok' => 0,
				'value' => (extension_loaded('curl_exec')) ? 1 : 0,
				'descr' => 'curl_exec-Support'
			),
			'wget_SUPPORT' => array(
				'ok' => 0,
				'value' => ($x = $this->_wget()) === true ? 1 : $x,
				'descr' => 'wget-Support'
			),
			'exec' => array(
				'ok' => 0,
				'value' => $this->DebugLib->execAllowed() ? 1 : 0,
				'descr' => 'exec()-Support (Shell Tasks) - ususally system() works as well then'
			),
			'getmxrr' => array(
				'ok' => 0,
				'value' => DebugLib::getmxrrAvailable() ? 1 : 0,
				'descr' => 'getmxrr for deep email validation'
			),
			'checkdnsrr' => array(
				'ok' => 0,
				'value' => DebugLib::checkdnsrrAvailable() ? 1 : 0,
				'descr' => 'checkdnsrr (fallback if !exits getmxrr) for deep email validation'
			),
			'soap' => array(
				'ok' => 0,
				'value' => $this->DebugLib->soap() ? 1 : 0,
				'descr' => 'soap server and client'
			),
			'modules' => array(
				'ok' => 0,
				'value' => implode(', ', array_keys($this->allModules())),
				'descr' => 'loaded modules'
			),
			'extensions' => array(
				'ok' => 0,
				'value' => implode(', ', get_loaded_extensions()),
				'descr' => 'loaded extensions'
			),
			'opcodeCache' => $this->_opCodeCache(),
		);
		$this->set(compact('serverinfo'));
	}

/* deprecated **/

	/**
	 * Only for apache
	 */
	public function loadedModules() {
		if (WINDOWS) {
			return array();
		}
		$res = exec('apache2ctl -t -D DUMP_MODULES', $output, $ret);
	}

	public function allModules() {
		ob_start(); // Stop output of the code and hold in buffer
		phpinfo(INFO_MODULES); // get loaded modules and their respective settings.
		$data = ob_get_contents(); // Get the buffer contents and store in $data variable
		ob_end_clean(); // Clear buffer

		$data = strip_tags($data, '<h2><th><td>');

		// Use regular expressions to filter out needed data
		// Replace everything in the <th> tags and put in <info> tags
		$data = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $data);

		// Replace everything in <td> tags and put in <info> tags
		$data = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $data);

		// Split the data into an array
		$vTmp = preg_split('/(<h2>[^<]+<\/h2>)/', $data, -1, PREG_SPLIT_DELIM_CAPTURE);
		$vModules = array();
		$count = count($vTmp);
		for ($i = 1; $i < $count; $i += 2) { // Loop through array and add 2 instead of 1
			if (preg_match('/<h2>([^<]+)<\/h2>/', $vTmp[$i], $vMat)) { // Check to make sure value is a module
				$moduleName = trim($vMat[1]); // Get the module name
				$vTmp2 = explode("\n", $vTmp[$i + 1]);
				foreach ($vTmp2 as $vOne) {
					$vPat = '<info>([^<]+)<\/info>'; // Specify the pattern we created above
					$vPat3 = "/$vPat\s*$vPat\s*$vPat/"; // Pattern for 2 settings (Local and Master values)
					$vPat2 = "/$vPat\s*$vPat/"; // Pattern for 1 settings
					if (preg_match($vPat3, $vOne, $vMat)) { // This setting has a Local and Master value
						$vModules[$moduleName][trim($vMat[1])] = array(trim($vMat[2]), trim($vMat[3]));
					} elseif (preg_match($vPat2, $vOne, $vMat)) { // This setting only has a value
						$vModules[$moduleName][trim($vMat[1])] = trim($vMat[2]);
					}
				}
			}
		}
		return $vModules;
	}

	/**
	 * Writes the params['named'] content into the session: Option.{case} = {value}
	 * Ajax Post Only!
	 *
	 * @return void
	 */
	public function admin_set_option_ajax() {
		$this->autoRender = false;

		if ($this->request->isAll(array('post', 'ajax')) && !empty($this->request->query)) {

			$cases = $this->request->query;
			foreach ($cases as $case => $value) {
				switch ($case) {
					case 'global_only':
						$this->_set_session($case, $value);
						break;
					case 'sss':
						$this->_set_session($case, $value);
					default:
						// not valid
				}
			}
		}
	}

	/**
	 * Complete table status
	 */
	protected function _getDbServerTableStatus() {
		$result = $this->Configuration->query("SHOW TABLE STATUS");
		return $result;
	}

	/**
	 * Internal Function to Write Options into the Session
	 */
	protected function _set_session($case, $value) {
		$content = $this->Session->read('Option.' . $case);
		if (empty($value)) {	// unset session (delete its value)
			$this->Session->delete('Option.' . $case);
		} else {
			$this->Session->write('Option.' . $case, $value);
		}
		return true;
	}

	/**
	 * Check if mail works!
	 */
	protected function _checkOwnMail($email, $username) {
		$this->Email = new EmailLib();
		//debug($this->Email);
		//die();
		$this->Email->to($email, $username);
		$this->Email->replyTo(Configure::read('Config.noReplyEmail'), Configure::read('Config.noReplyEmailname'));

		$this->Email->subject('Test');
		$this->Email->template('simple_email');

		$text = 'Ein Test';
		$this->Email->viewVars(compact('text'));

		$res = $this->Email->send();
		return $res;
	}

	protected function _checkCoreMail($email, $username) {
		$this->Email = new EmailLib();
		$this->Email->to($email, $username);
		//$this->Email->bcc = array('backup@kuechenatlas.de');
		$this->Email->subject('Test' . ' (KüchenAtlas)');
		$this->Email->replyTo(Configure::read('Config.noReplyEmail'), Configure::read('Config.noReplyEmailname'));

		$this->Email->template('simple_email');
		$text = 'Ein Test';
		$this->Email->viewVars(compact('text'));

		if (!($res = $this->Email->send())) {
			$this->log('ERROR: ' . $this->Email->error, 'email');
		} elseif (Configure::read('Email.log')) {
			$this->log('TO: ' . $this->Email->to . ', TITLE: ' . $this->Email->subject . '', 'email_ok');
		}
		//$this->set('smtp-errors', $this->Email->smtpError);
		//echo returns($this->Email->smtpError);
		return $res;
	}

	protected function _opCodeCache() {
		App::uses('OpCodeCacheLib', 'Setup.Lib');
		$engines = OpCodeCacheLib::detect();
		$e = array();
		foreach ($engines as $engine => $value) {
			if (!$value) {
				continue;
			}
			$e[] = $engine;
		}
		$ok = !empty($e);
		$ok = ($ok ? 2 : 1);

		$ret = array(
			'ok' => $ok,
			'value' => implode(', ', $e),
			'descr' => 'opcodeCache enabled'
		);
		return $ret;
	}

//TODO: move to debug lib!!

	/** test if exec commands are allowed **/
	protected function _exec() {
		$command = 'echo XYZ';
		//$backupFile = APP.date("Y-m-d_H-i-s").'.txt';
		//$command = "mysqldump --opt -h $dbhost -u $dbuser -p $dbpass $dbname > $backupFile";

		$res = exec($command);
		//$s = system($command);
		return !empty($res) ? true : false;
	}

	/**
	 * Not really necessary (this information)
	 */
	protected function _wget() {
		$transport = 'wget';
		$returnVar = null;
		exec("wget --version", $output, $returnVar);

		if ($returnVar === 0 && !empty($output)) { //0=ok, 127=error; output array[0] => version number
			return true;
		}
		return false;
	}

/** PHP Infos **/

	protected function _openBasedir() {
		$var = ini_get('open_basedir');
		if (strpos($var, ':') !== false) {
			$paths = explode(':', $var);
		} else {
			$paths = array();
		}
		$ret = array(
			'ok' => 0,
			'value' => implode('<br/>', $paths),
			'descr' => 'open basedir restrictions'
		);
		return $ret;
	}

	/**
	 * displayErrors
	 * >= 5: ok
	 * >= 4: warning
	 * <4 : error
	 */
	protected function _phpVersion() {
		$v = $this->DebugLib->phpVersion();
		$ok = (int)$v;
		$ok = ($ok >= 5 ? 2 : ($ok >= 4 ? 1 : -1));
		$ret = array(
				'ok' => $ok,
				'value' => $v,
				'descr' => 'should be 5 or higher'
		);
		return $ret;
	}

/** Database Infos **/

	protected function _dbClientEncoding() {
		return @mysql_client_encoding();
	}

	protected function _dbUptime() {
		$uptime = $this->Configuration->query('show status like "Uptime"');
		$value = $uptime[0]['STATUS']['Value'];
		$dbUptime = intval($value / 3600) . 'h ' . str_pad(intval(($value / 60) % 60), 2, '0', STR_PAD_LEFT) . 'm';
		return $dbUptime;
	}

	protected function _dbTime() {
		$time = $this->Configuration->query('select now() as datetime');
		return $time[0][0]['datetime'];
	}

	/**
	 * Uses DB query, foolprove!
	 * >= 5: OK
	 * < 5: error
	 */
	protected function _dbServerVersion() {
		$v = $this->_getDbServerVersion();
		$ok = (int)$v;
		$ok = ($ok >= 5 ? 2 : -1);
		$ret = array(
				'ok' => $ok,
				'value' => $v,
				'descr' => 'must be 5 or higher'
		);
		return $ret;
	}

	protected function _getDbServerVersion() {
		//return @mysql_get_server_info(); # does not always work...
		$mysqlServerInfo = $this->Configuration->query('select version() as version'); # DateBase Version?
		return $mysqlServerInfo[0][0]['version'];
	}

/** SETTINGS **/

	/**
	 * Whether to allow the treatment of URLs (like http:// or ftp://) as files.
	 * ON: -
	 * OFF: -
	 */
	protected function _allowUrlFopen() {
		$var = $this->DebugLib->allowUrlFopen();
		$res = array(
			'ok' => 0,
			'value' => ($var ? 'yes' : 'no'),
			'descr' => 'whether to allow the treatment of URLs (like http:// or ftp://) as files'
		);
		return $res;
	}

	/**
	 * Experimental (default 16MB)
	 * > 16 : ok
	 * < 16 : warning
	 */
	protected function _uploadMaxSize() {
		$var = (int)$this->DebugLib->uploadMaxSize();
		$res = array(
			'ok' => ($var >= 16 ? 2 : 1),
			'value' => $var . ' MB',
			'descr' => 'default: 16MB (at least)'
		);
		return $res;
	}

	/** Whether or not to register the old-style input arrays, HTTP_GET_VARS and friends.  If you're not using them, it's recommended to turn them off, for performance reasons.
	 */
	protected function _registerLongArrays() {
		$var = $this->DebugLib->registerLongArrays();
		$res = array(
			'ok' => 0,
			'value' => ($var ? 'yes' : 'no'),
			'descr' => 'Whether or not to register the old-style input arrays, HTTP_GET_VARS and friends'
		);
		return $res;
	}

	/**
	 * This directive tells PHP whether to declare the argv&argc variables (that would contain the GET information).  If you don't use these variables, you should turn it off for increased performance.
	 */
	protected function _registerArgcArgv() {
		$var = $this->DebugLib->registerArgcArgv();
		$res = array(
			'ok' => 0,
			'value' => ($var ? 'yes' : 'no'),
			'descr' => 'Whether to declare the argv&argc variables (that would contain the GET information)'
		);
		return $res;
	}

	/**
	 * Maximum amount of time each script may spend executing
	 */
	protected function _maxExecTime() {
		$var = (int)$this->DebugLib->maxExecTime();
		$res = array(
			'ok' => 0,
			'value' => $var . 's',
			'descr' => 'Maximum execution time of each script, in seconds'
		);
		return $res;
	}

	/**
	 * Maximum amount of time each script may spend parsing request data
	 */
	protected function _maxInputTime() {
		$var = (int)$this->DebugLib->maxInputTime();
		$res = array(
			'ok' => 0,
			'value' => $var . 's',
			'descr' => 'Maximum amount of time each script may spend parsing request data'
		);
		return $res;
	}

	protected function _fileUpload() {
		$var = $this->DebugLib->fileUpload();
		$res = array(
			'ok' => ($var ? 2 : -1),
			'value' => ($var ? 'on' : 'off'),
			'descr' => 'if file uploads are permitted'
		);
		return $res;
	}

	protected function _postMaxSize() {
		$var = (int)$this->DebugLib->postMaxSize();
		$res = array(
			'ok' => ($var >= 6 ? 2 : 1),
			'value' => $var . ' MB',
			'descr' => 'max size of data posted via POST'
		);
		return $res;
	}

	/**
	 * Allow the <? tag.  Otherwise, only <?php and <script> tags are recognized. Using short tags should be avoided when developing applications or libraries that are meant for redistribution
	 * ON: warning
	 * OFF: ok
	 */
	protected function _shortOpenTag() {
		$var = $this->DebugLib->shortOpenTag();
		$res = array(
			'ok' => (!$var ? 2 : 1),
			'value' => ($var ? 'on' : 'off'),
			'descr' => 'Allow the &lt;? tag.  Otherwise, only &lt;?php and &lt;script&gt; tags are recognized'
		);
		return $res;
	}

	protected function _safeMode() {	// should be OFF
		$var = $this->DebugLib->safeMode();
		$res = array(
			'ok' => (!$var ? 2 : 1),
			'value' => ($var ? 'on' : 'off'),
			'descr' => 'Safe Mode'
		);
		return $res;
	}

	/**
	 * Magic quotes
	 * ON: error
	 * OFF: ok
	 */
	protected function _magicQuotesGpc() {
		$var = $this->DebugLib->magicQuotesGpc();
		$res = array(
			'ok' => (!$var ? 2 : -1),
			'value' => ($var ? 'on' : 'off'),
			'descr' => 'should always be turned off'
		);
		return $res;
	}

	/**
	 * Register globals
	 * ON: error
	 * OFF: ok
	 */
	protected function _registerGlobals() {
		$var = $this->DebugLib->registerGlobals();
		$res = array(
			'ok' => (!$var ? 2 : -1),
			'value' => ($var ? 'on' : 'off'),
			'descr' => 'should ALWAYS be turned off'
		);
		return $res;
	}

	/**
	 * displayErrors
	 * ON: error
	 * OFF: ok
	 */
	protected function _displayErrors() {
		$var = $this->DebugLib->displayErrors();
		$res = array(
			'ok' => (!$var ? 2 : -1),
			'value' => ($var ? 'on' : 'off'),
			'descr' => 'Should be turned off (on productive servers)'
		);
		return $res;
	}

	/**
	 * MemoryLimit Adjustable
	 * ON: ok
	 * OFF: warning
	 */
	protected function _memoryLimitAdjustable() {
		$var = $this->DebugLib->memoryLimitAdjustable();
		$res = array(
			'ok' => ($var ? 2 : 1),
			'value' => '',
			'descr' => 'some file scripts might need that (for image resizing > 2000x3000 Pixel etc!)'
		);
		return $res;
	}

	/**
	 * >= 32: ok
	 * < 32: warning
	 */
	protected function _memoryLimit() {
		$var = (int)$this->DebugLib->memoryLimit();
		$res = array(
			'ok' => ($var >= 32 ? 2 : 1),
			'value' => $var . ' MB',
			'descr' => 'default: 32 MB (some image resizing might need more)'
		);
		return $res;
	}

}
