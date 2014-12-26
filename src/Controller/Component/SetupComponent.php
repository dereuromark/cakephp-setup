<?php
namespace Setup\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Tools\Controller\Component\FlashComponent;
use Setup\Maintenance\Maintenance;
use Cake\Event\Event;
use Tools\Network\Email\Email;
use Setup\Utility\Setup;
use Cake\Cache\Cache;

if (!defined('WINDOWS')) {
	if (substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

/**
 * Attach this to your AppController to power up debugging:
 * - Quick-Switch: layout, maintenance, debug, clearcache (password protected in productive mode)
 * - Notify Admin via Email about self-inflicted 404s or loops (configurable)

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

	public $components = array('Tools.Session');

	public $Controller;

	public $notifications = array(
		'404' => true,
		'loops' => false, //TODO,
		'memory' => false, //TODO,
		'execTime' => false, //TODO,
	);

	/**
	 * ::beforeFilter()
	 *
	 * @param Event $event
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		$this->Controller = $event->subject();

		// For debug overwrite
		if (($debug = $this->Session->read('Setup.debug')) !== null) {
			Configure::write('debug', $debug);
		}

		// maintenance mode?
		if ($overwrite = Configure::read('Maintenance.overwrite')) {
			// if this is reachable, the whitelisting is enabled and active
			$message = __d('setup', 'Maintenance mode active - your IP %s is in the whitelist.', $overwrite);
			FlashComponent::transientMessage($message, 'warning');
		}

		// The following is only allowed with proper clearance
		if (!$this->isAuthorized()) {
			return;
		}

		if (!isset($this->Controller->Flash)) {
			throw new \Exception('Flash component missing in AppController setup.');
		}

		// maintenance mode
		if ($this->Controller->request->query('maintenance') !== null) {
			if (($x = $this->setMaintenance($this->Controller->request->query('maintenance'))) !== false) {
				$mode = $this->Controller->request->query('maintenance') ? __d('setup', 'activated') : __d('setup', 'deactivated');
				$this->Controller->Flash->message(__d('setup', 'Maintenance mode {0}', $mode), 'success');
			} else {
				$this->Controller->Flash->message(__d('setup', 'Maintenance mode not {0}', $mode), 'error');
			}
			return $this->Controller->redirect($this->_cleanedUrl('maintenance'));
		}

		// debug mode
		if ($this->Controller->request->query('debug') !== null) {
			if (($x = $this->setDebug($this->Controller->request->query('debug'))) !== false) {
				$this->Controller->Flash->message(__('debug set to %s', $this->Controller->request->query('debug')), 'success');
			} else {
				$this->Controller->Flash->message(__('debug not set'), 'error');
			}
			return $this->Controller->redirect($this->_cleanedUrl('debug'));
		}

		// clear cache
		if ($this->Controller->request->query('clearcache') !== null) {
			if (($x = $this->clearCache($this->Controller->request->query('clearcache'))) !== false) {
				$this->Controller->Flash->message(__('cache cleared'), 'success');
			} else {
				$this->Controller->Flash->message(__('cache not cleared'), 'error');
			}
			return $this->Controller->redirect($this->_cleanedUrl('clearcache'));
		}

		// clear session
		if ($this->Controller->request->query('clearsession') !== null) {
			if ($this->clearSession()) {
				$this->Controller->Flash->message(__('session cleared'), 'success');
			} else {
				$this->Controller->Flash->message(__('session not cleared'), 'error');
			}
			return $this->Controller->redirect($this->_cleanedUrl('clearsession'));
		}

		// layout switch
		if ($this->Controller->request->query('layout') !== null) {
			if (($x = $this->setLayout($this->Controller->request->query('layout'))) !== false) {
				$this->Controller->Flash->message(__('layout %s activated', $this->Controller->request->query('layout')), 'success');
			} else {
				$this->Controller->Flash->message(__('layout not activated'), 'error');
			}
			return $this->Controller->redirect($this->_cleanedUrl('layout'));
		}

		$this->issueMailing();
	}

	/**
	 * SetupComponent::startup()
	 *
	 * - Sets the layout based on session value 'Setup.layout'.
	 *
	 * @param Controller $this->Controller
	 * @return void
	 */
	public function startup(Event $event) {
		if ($layout = $this->Session->read('Setup.layout')) {
			$this->Controller->layout = $layout;
		}
	}

	/**
	 * Notify admin about 404s and other self inflected issues right away (via email right now)
	 *
	 * @return void
	 */
	public function issueMailing() {
		if ($this->Controller->name !== 'CakeError' || empty($this->notifications['404'])) {
			return;
		}
		if (env('REMOTE_ADDR') === '127.0.0.1' || Configure::read('debug') > 0) {
			return;
		}
		$referer = $this->Controller->referer();
		if (strlen($referer) > 2 && (int)$this->Session->read('Report.404') < time() - 5 * MINUTE) {
			$text = '404:' . TB . TB . '/' . $this->Controller->request->url .
			NL . 'Referer:' . TB . '' . $referer .
			NL . NL . 'Browser: ' . env('HTTP_USER_AGENT') .
			NL . 'IP: ' . env('REMOTE_ADDR');
			if ($uid = $this->Session->read('Auth.User.id')) {
				$text .= NL . NL . 'UID: ' . $uid;
			}

			if (!$this->_notification('404!', $text)) {
				throw new \InternalErrorException('Cannot send admin notification email');
			}
			$this->Session->write('Report.404', time());
		}
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
		if (($pwd = $this->Controller->request->query('pwd')) && $pwd === Configure::read('Config.pwd')) {
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
	 * Alternatively, this can and should be done using the CLI shell.
	 *
	 * URL Options:
	 * - ´duration` query string can be used to set a timeout maintenance window
	 *
	 * @param mixed $maintenance
	 * @return bool Success
	 */
	public function setMaintenance($maintenance) {
		$ip = env('REMOTE_ADDR');
		// optional length in minutes
		$length = (int)$this->Controller->request->query('duration');

		$Maintenance = new Maintenance();
		if (!$Maintenance->setMaintenanceMode($maintenance ? $length : false)) {
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
	 * 0/1 to set, or -1 to unset.
	 *
	 * @param bool|int $level Debug level
	 * @param string $type Type - session/ip [optional] (defaults to session)
	 * @return bool Success
	 */
	public function setDebug($level, $type = 'session') {
		$level = (int)$level;

		if ($type === 'session') {
			if ($level < 0) {
				$this->Session->delete('Setup.debug');
				return false;
			}
			return $this->Session->write('Setup.debug', $level);
		}

		$file = TMP . 'debugOverride-' . $id . '.txt';
		if ($level < 0) {
			if (file_exists($file)) {
				unlink($file);
			}
			return false;
		}

		$cookieName = Configure::read('Session.cookie');
		if (empty($cookieName)) {
			$cookieName = 'CAKEPHP';
		}
		if ($type === 'session') {
			$id = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : '';

		} elseif ($type === 'ip') {
			$ip = env('REMOTE_ADDR');
			$host = 'unknown';
			if (!empty($ip)) {
				$host = gethostbyaddr($ip);
			}
			$id = $ip . '-' . $host;
		}
		if (!empty($id)) {
			if (file_put_contents($file, $level)) {
				return $level;
			}
		}

		return false;
	}

	/**
	 * Clear cache of tmp folders
	 *
	 * @return bool Success
	 */
	public function clearCache($type) {
		$config = 'default';
		Cache::clear(false, $config);

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
	 * Remove specific named param from parsed url array
	 *
	 * @return array url
	 */
	protected function _cleanedUrl($type) {
		$type = (array)$type;
		if (Configure::read('Config.productive')) {
			$type[] = 'pwd';
		}

		return Setup::cleanedUrl($type, $this->Controller->request->params + array('?' => $this->Controller->request->query));
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
			$this->Email = new Email();
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
