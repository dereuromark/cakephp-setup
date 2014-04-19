<?php

if (!defined('WINDOWS')) {
	if (substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

App::uses('Component', 'Controller');
App::uses('Folder', 'Utility');
App::uses('ChmodLib', 'Tools.Utility');
App::uses('SetupLib', 'Setup.Lib');

/**
 * Attach this to your AppController to power up debugging:
 * - Auto create missing tmp folders etc in debug mode (already in core since 2.4)
 * - Catch redirect loops with meaningful exception (will also be logged then)
 * - Quick-Switch: layout, maintenance, debug, clearcache (password protected in productive mode)
 * - Notify Admin via Email about self-inflicted 404s or loops (configurable)
 *
 * Note that debug, clearcache, maintenance etc for productive mode, since they require a password,
 * are emergency commands only (in case you cannot power up ssh shell access that quickly).
 * Change your password immediately afterwards for security reasons as pwds should not be passed
 * plain via url.
 * Tip: Use the CLI and the Setup plugin shells for normal execution.
 *
 * @author Mark Scherer
 * @license MIT
 */
class SetupComponent extends Component {

	public $components = array('Session');

	public $Controller;

	public $dirs = array();

	public $notifications = array(
		'404' => true,
		'loops' => false, //TODO,
		'memory' => false,
		'execTime' => false,
	);

	/**
	 * Main interaction.
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function initialize(Controller $Controller) {
		$this->Controller = $Controller;

		// create missing tmp folders
		if (Configure::read('debug') > 0) {
			$this->setupDirs();
		}

		// maintenance mode?
		if ($overwrite = Configure::read('Maintenance.overwrite')) {
			// if this is reachable, the whitelisting is enabled and active
			$message = __d('setup', 'Maintenance mode active - your IP %s is in the whitelist.', $overwrite);
			CommonComponent::transientFlashMessage($message, 'warning');
		}

		// the following is only allowed with proper clearance
		if (!$this->isAuthorized()) {
			//$Controller->Common->flashMessage(__('not possible in productive mode'), 'warning');
			return;
		}

		// layout
		if ($Controller->request->query('layout') !== null) {
			if (($x = $this->setLayout($Controller->request->query('layout'))) !== false) {
				$Controller->Common->flashMessage(__('layout %s activated', $Controller->request->query('layout')), 'success');
			} else {
				$Controller->Common->flashMessage(__('layout not activated'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('layout'));
		}

		// maintenance mode
		if ($Controller->request->query('maintenance') !== null) {
			if (($x = $this->setMaintenance($Controller->request->query('maintenance'))) !== false) {
				$Controller->Common->flashMessage(__('maintenance activated'), 'success');
			} else {
				$Controller->Common->flashMessage(__('maintenance not activated'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('maintenance'));
		}

		// debug mode
		if ($Controller->request->query('debug') !== null) {
			if (($x = $this->setDebug($Controller->request->query('debug'))) !== false) {
				$Controller->Common->flashMessage(__('debug set to %s', $x), 'success');
			} else {
				$Controller->Common->flashMessage(__('debug not set'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('debug'));
		}

		// clear cache
		if ($Controller->request->query('clearcache') !== null) {
			if (($x = $this->clearCache($Controller->request->query('clearcache'))) !== false) {
				$Controller->Common->flashMessage(__('cache cleared'), 'success');
			} else {
				$Controller->Common->flashMessage(__('cache not cleared'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('clearcache'));
		}

		// clear tmp - more powerful as clearcache
		if ($Controller->request->query('cleartmp') !== null) {
			if (($x = $this->clearTmp($Controller->request->query('cleartmp'))) !== false) {
				$Controller->Common->flashMessage(__('tmp cleared'), 'success');
			} else {
				$Controller->Common->flashMessage(__('tmp not cleared'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('cleartmp'));
		}

		// clear session
		if ($Controller->request->query('clearsession') !== null) {
			if ($this->clearSession()) {
				$Controller->Common->flashMessage(__('session cleared'), 'success');
			} else {
				$Controller->Common->flashMessage(__('session not cleared'), 'error');
			}
			//$clearCacheUrl = array('action' => $Controller->request->params['action']);
			$Controller->redirect($this->_cleanedUrl('clearsession'));
			//$Controller->redirect(array_merge($clearCacheUrl, $Controller->request->params['pass']));
		}

		// deprecated named params:

		// set maintenance mode
		if (isset($Controller->request->params['named']['maintenance'])) {
			if (($x = $this->setMaintenance($Controller->request->params['named']['maintenance'])) !== false) {
				$Controller->Common->flashMessage(__('maintenance activated'), 'success');
			} else {
				$Controller->Common->flashMessage(__('maintenance deactivated'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('maintenance'));
		}

		// set debug
		if (isset($Controller->request->params['named']['debug'])) {
			if (($x = $this->setDebug($Controller->request->params['named']['debug'])) !== false) {
				$Controller->Common->flashMessage(__('debug set to %s', $x), 'success');
			} else {
				$Controller->Common->flashMessage(__('debug not set'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('debug'));
		}

		// clear cache
		if (!empty($Controller->request->params['named']['clearcache'])) {
			if (($x = $this->clearCache($Controller->request->params['named']['clearcache'])) !== false) {
				$Controller->Common->flashMessage(__('cache cleared'), 'success');
			} else {
				$Controller->Common->flashMessage(__('cache not cleared'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('clearcache'));
		}

		// clear tmp - more powerful as clearcache
		if (!empty($Controller->request->params['named']['cleartmp'])) {
			if (($x = $this->clearTmp($Controller->request->params['named']['cleartmp'])) !== false) {
				$Controller->Common->flashMessage(__('tmp cleared'), 'success');
			} else {
				$Controller->Common->flashMessage(__('tmp not cleared'), 'error');
			}
			$Controller->redirect($this->_cleanedUrl('cleartmp'));
		}

		// clear session
		if (!empty($Controller->request->params['named']['clearsession'])) {
			if ($this->clearSession()) {
				$Controller->Common->flashMessage(__('session cleared'), 'success');
			} else {
				$Controller->Common->flashMessage(__('session not cleared'), 'error');
			}
			//$clearCacheUrl = array('action' => $Controller->request->params['action']);
			$Controller->redirect($this->_cleanedUrl('clearsession'));
			//$Controller->redirect(array_merge($clearCacheUrl, $Controller->request->params['pass']));
		}

		$this->issueMailing($Controller);
	}

	/**
	 * SetupComponent::startup()
	 *
	 * - Sets the layout based on session value 'Setup.layout'.
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function startup(Controller $controller) {
		if ($layout = $this->Session->read('Setup.layout')) {
			$controller->layout = $layout;
		}
	}

	/**
	 * Notify admin about 404s and other self inflected issues right away (via email right now)
	 *
	 * @return void
	 */
	public function issueMailing(Controller $Controller) {
		if ($Controller->name !== 'CakeError' || empty($this->notifications['404'])) {
			return;
		}
  	if (env('REMOTE_ADDR') === '127.0.0.1' || Configure::read('debug') > 0) {
			return;
		}
		$referer = $Controller->referer();
		if (strlen($referer) > 2 && (int)$this->Session->read('Report.404') < time() - 5 * MINUTE) {
			$text = '404:' . TB . TB . '/' . $Controller->request->url .
			NL . 'Referer:' . TB . '' . $referer .
			NL . NL . 'Browser: ' . env('HTTP_USER_AGENT') .
			NL . 'IP: ' . env('REMOTE_ADDR');
			if ($uid = $this->Session->read('Auth.User.id')) {
				$text .= NL . NL . 'UID: ' . $uid;
			}

			if (!$this->_notification('404!', $text)) {
				throw new InternalErrorException('Cannot send admin notification email');
			}
			$this->Session->write('Report.404', time());
		}
	}

	/**
	 * Catch redirect loops with meaningful exception
	 * Use Configure::write('Debug.loopTimeout', ...) to activate
	 *
	 * @return void
	 */
	public function beforeRedirect(Controller $Controller, $url, $status = null, $exit = true) {
		$url = Router::url($url);
		if (Configure::read('Debug.logRedirect')) {
			$this->log($url, 'redirect');
		}

		$timeFrame = Configure::read('Debug.loopTimeout');
		if (!$timeFrame) {
			return;
		}
		$time = $this->Session->read('Debug.Redirect.time');

		if ($time && $time >= time() - $timeFrame) {
			$stack = (array)$this->Session->read('Debug.Redirect.stack');

		} else {
			$stack = array();
		}

		$inStack = false;
		foreach ($stack as $element) {
			if ($element['url'] === $url) {
				$inStack = true;
				break;
			}
		}

		$stack[] = array('url' => $url, 'time' => time(), 'status' => $status, 'referer' => $Controller->referer(), 'current' => $Controller->request->here);
		if (count($stack) > 5) {
			array_shift($stack);
		}
		$stack = array('time' => time(), 'stack' => $stack);
		$this->Session->write('Debug.Redirect', $stack);

		if ($inStack) {
			$urls = Set::extract('/url', $stack['stack']);

			if (!empty($this->notifications['loops'])) {
				$text = implode(PHP_EOL, $urls);

				$this->_notification('loop!', $text);
			}
			throw new InternalErrorException('Redirect Loop: ' . implode(' - ', $urls));
		}
	}

	/**
	 * SetupComponent::shutdown()
	 *
	 * @param Conroller $Controller
	 * @return void
	 */
	public function shutdown(Controller $Controller) {
		$this->Session->delete('Debug.Redirect.time');
	}

	/**
	 * SetupComponent::log404()
	 *
	 * @param bool $notifyAdminOnInteralErrors
	 * @return void
	 */
	public function log404($notifyAdminOnInteralErrors = false) {
		if ($this->Controller->name === 'CakeError') {
			$referer = $this->Controller->referer();
			$this->Controller->log('REF: ' . $referer . ' - URL: ' . $this->Controller->request->url, '404');
		}
	}

	/**
	 * SetupComponent::isAuthorized()
	 *
	 * @return bool Success
	 */
	public function isAuthorized() {
		if (!Configure::read('Config.productive')) {
			return true;
		}
		if (!empty($this->Controller->request->params['named']['pwd']) && $this->Controller->request->params['named']['pwd'] == Configure::read('Config.pwd')) {
			return true;
		}
		return false;
	}

	/**
	 * Set layout for this session.
	 *
	 * @param string $layout
	 * @return bool Success
	 */
	public function setLayout($layout) {
		if (!$layout) {
			return $this->Session->delete('Setup.layout');
		}
		return $this->Session->write('Setup.layout', $layout);
	}

	/**
	 * Set maintance mode for everybody except for the own IP which will
	 * be whitelisted.
	 *
	 * -´duration query string can be used to set a timeout maintenance window
	 *
	 * @param mixed $maintenance
	 * @return bool Success
	 */
	public function setMaintenance($maintenance) {
		$ip = env('REMOTE_ADDR');
		// optional length in minutes
		$length = (int)$this->Controller->request->query('duration');

		App::uses('MaintenanceLib', 'Setup.Lib');
		$Maintenance = new MaintenanceLib();
		if (!$Maintenance->setMaintenanceMode($length)) {
			return false;
		}
		if (!$Maintenance->whitelist(array($ip))) {
			return false;
		}

		return true;
	}

	/**
	 * Override debug level
	 *
	 * @param level: 0, 1, 2
	 * @param type: session/ip [optional] (defaults to session)
	 * @return bool Success
	 */
	public function setDebug($level, $type = 'session') {
		$level = (int)$level;
		if ($level < 0 || $level > 2) {
			return false;
		}
		$cookieName = Configure::read('Session.cookie');
		if (empty($cookieName)) {
			$cookieName = 'CAKEPHP';
		}
		if ($type === 'session') {
			$id = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : '';

		} elseif ($type = 'ip') {
			$ip = env('REMOTE_ADDR');
			$host = 'unknown';
			if (!empty($ip)) {
				$host = gethostbyaddr($ip);
			}
			$id = $ip . '-' . $host;
		}
		if (!empty($id)) {
			$file = TMP . 'debugOverride-' . $id . '.txt';
			if (file_put_contents($file, $level)) {
				return $level;
			}
		}

		return false;
	}

	/**
	 * Clear tmp folders
	 * if not specified, clear all tmp folders!?
	 *
	 * @param type
	 * - c (cache)
	 * - p (packed files)
	 * - t (tmp folder)
	 * - ...
	 */
	public function clearTmp($type) {
		$folders = $this->_defaults();
		//TODO

		return false;
	}

	/**
	 * Clear cache of tmp folders
	 *
	 * @return bool Success
	 */
	public function clearCache($type) {
		$this->Setup = new SetupLib();
		// filesystem
		$this->Setup->clearCache($type);

		// memcache:
		$config = 'default';
		$this->Setup->clearCache2(false, $config);

		return true;
	}

	/**
	 * Destroy the current user's session.
	 *
	 * @return bool Success
	 */
	public function clearSession() {
		$this->Session->destroy();
		return true;
	}

	/**
	 * Auto-setup folders for TMP etc.
	 *
	 * @return void
	 */
	public function setupDirs() {
		Configure::write('Setup.folderRights', $this->_tmpFolders());
		$errors = array();
		$messages = array();
		foreach (Configure::read('Setup.folderRights') as $dir => $mode) {
			if ($dir === TMP && !WINDOWS) {
				continue; # the main tmp folder must be adjusted manually (0775)
			}
			$Handle = new Folder($dir, true, $mode);
			$messages = array_merge($messages, $Handle->messages());

			if (!$Handle->errors()) {
				if (ChmodLib::convertToOctal(ChmodLib::convertFromOctal(fileperms($dir))) !== $mode) {
					//buggy!!!
					//$Handle->chmod($dir, $mode, true);
				}
				if (!is_readable($dir) || !is_writable($dir)) {
					$errors[] = __('%s not read and writable', $dir);
				}
			}
			if (!WINDOWS) {
				// on windows there is only 0777 (it will always return messages here - no matter what)
				$messages = array_merge($messages, $Handle->messages());
			}
			if ($Handle->errors()) {
				$errors = array_merge($errors, $Handle->errors());
			}
			//TODO: ChmodLib::convertFromOctal() on the error messages!!!
			foreach ($errors as $key => $val) {
				//mb_ereg_replace();
				$value = preg_replace_callback('/to\s[0-9][0-9][0-9]\s/s', array($this, '_processErrorMessage'), $val);
				$errors[$key] = $value;
			}
		}
		foreach ($errors as $error) {
			if (Configure::read('debug') > 1) {
				trigger_error($error, E_USER_WARNING);
			}
		}
		if (count($errors) === 0 && count($messages) > 0) {
			$this->Controller->Common->flashMessage('Tmp Folders created', 'info');
			$this->Controller->redirect('/' . $this->Controller->request->url);
		}
	}

	/**
	 * Convert octal values (509) to human readable number (755)!
	 *
	 * @return string
	 */
	protected function _processErrorMessage($matches, $isCallback = false) {
		$value = substr($matches[0], 3);
		$value = ChmodLib::convertToOctal($value);
		return 'to ' . $value . '! ';
	}

	/**
	 * SetupComponent::_tmpFolders()
	 *
	 * @return array
	 */
	protected function _tmpFolders() {
		if (file_exists(APP . 'Config' . DS . 'setup.php')) {
			Configure::load('setup');
			$customs = (array)Configure::read('Setup.folderRights');
		} else {
			$customs = array();
		}
		return array_merge($customs, $this->_defaults());
	}

	/**
	 * Get defaults for setupDirs and clearCache
	 *
	 * @return array
	 */
	protected function _defaults() {
		$defaults = array(
			TMP => 0775,
			TMP . 'logs' . DS => 0775,
			TMP . 'sessions' . DS => 0775,
			CACHE => 0775,
			CACHE . 'models' . DS => 0775,
			CACHE . 'persistent' . DS => 0775,
			CACHE . 'views' . DS => 0775
		);

		// add js/css cache
		if (Configure::read('Asset.combine')) {
			$defaults[JS . 'cjs' . DS] = 0775;
			$defaults[CSS . 'ccss' . DS] = 0775;
		}
		//TODO

		return $defaults;
	}

	/**
	 * Remove specific named param from parsed url array
	 *
	 * @return array url
	 */
	protected function _cleanedUrl($type) {
		$type = (array)$type;
		if (Configure::read('Config.productive')) {
			$type[] = 'pwd';
		}
		return SetupLib::cleanedUrl($type, $this->Controller->request->params + array('?' => $this->Controller->request->query));
	}

	/**
	 * Quick way of notifying admin
	 * Note: right now only via email
	 *
	 * @param string $title
	 * @param string $message
	 * @return bool Success
	 */
	protected function _notification($title, $text) {
		if (!isset($this->Email)) {
			App::uses('EmailLib', 'Tools.Lib');
			$this->Email = new EmailLib();
		} else {
			$this->Email->reset();
		}

		$this->Email->to(Configure::read('Config.adminEmail'), Configure::read('Config.adminName'));
		$this->Email->subject($title);
		$this->Email->template('simple_email');
		$this->Email->viewVars(compact('text'));
		if ($this->Email->send()) {
			return true;
		}
		return false;
	}

}
