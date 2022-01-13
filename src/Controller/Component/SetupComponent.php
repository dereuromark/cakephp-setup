<?php

namespace Setup\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\InternalErrorException;
use Exception;
use Setup\Maintenance\Maintenance;
use Setup\Utility\Setup;
use Tools\Mailer\Mailer;

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

	/**
	 * @var \Cake\Controller\Controller
	 */
	public $Controller;

	/**
	 * @var \Cake\Mailer\Mailer|null
	 */
	protected $Mailer;

	/**
	 * @var array
	 */
	public $notifications = [
		'404' => true,
		'loops' => false, //TODO,
		'memory' => false, //TODO,
		'execTime' => false, //TODO,
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @throws \Exception
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeFilter(EventInterface $event) {
		/** @var \Cake\Controller\Controller $Controller */
		$Controller = $event->getSubject();
		$this->Controller = $Controller;

		// For debug overwrite
		$debug = $this->getController()->getRequest()->getSession()->read('Setup.debug');
		if ($debug !== null) {
			Configure::write('debug', $debug);
		}

		if (!isset($this->Controller->Flash)) {
			throw new Exception('Flash component missing in AppController setup.');
		}

		// maintenance mode?
		$overwrite = Configure::read('Maintenance.overwrite');
		if ($overwrite) {
			// if this is reachable, the whitelisting is enabled and active
			$message = __d('setup', 'Maintenance mode active - your IP {0} is in the whitelist.', $overwrite);
			$this->Controller->Flash->warning($message);
		}

		// The following is only allowed with proper clearance
		if (!$this->isAuthorized()) {
			return null;
		}

		// maintenance mode
		if ($this->Controller->getRequest()->getQuery('maintenance') !== null) {
			$mode = $this->Controller->getRequest()->getQuery('maintenance') ? __d('setup', 'activated') : __d('setup', 'deactivated');
			$result = $this->setMaintenance($this->Controller->getRequest()->getQuery('maintenance'));
			if ($result !== false) {
				$this->Controller->Flash->success(__d('setup', 'Maintenance mode {0}', $mode));
			} else {
				$this->Controller->Flash->error(__d('setup', 'Maintenance mode not {0}', $mode));
			}

			return $this->Controller->redirect($this->_cleanedUrl('maintenance'));
		}

		// debug mode
		if ($this->Controller->getRequest()->getQuery('debug') !== null) {
			$result = $this->setDebug((int)$this->Controller->getRequest()->getQuery('debug'));
			if ($result !== false) {
				$this->Controller->Flash->success(__d('setup', 'debug set to {0}', $this->Controller->getRequest()->getQuery('debug')));
			} else {
				$this->Controller->Flash->error(__d('setup', 'debug not set'));
			}

			return $this->Controller->redirect($this->_cleanedUrl('debug'));
		}

		// clear cache
		if ($this->Controller->getRequest()->getQuery('clearcache') !== null) {
			$result = $this->clearCache((string)$this->Controller->getRequest()->getQuery('clearcache'));
			if ($result !== false) {
				$this->Controller->Flash->success(__d('setup', 'cache cleared'));
			} else {
				$this->Controller->Flash->error(__d('setup', 'cache not cleared'));
			}

			return $this->Controller->redirect($this->_cleanedUrl('clearcache'));
		}

		// clear session
		if ($this->Controller->getRequest()->getQuery('clearsession') !== null) {
			if ($this->clearSession()) {
				$this->Controller->Flash->success(__d('setup', 'session cleared'));
			} else {
				$this->Controller->Flash->error(__d('setup', 'session not cleared'));
			}

			return $this->Controller->redirect($this->_cleanedUrl('clearsession'));
		}

		// layout switch
		if ($this->Controller->getRequest()->getQuery('layout') !== null) {
			$this->setLayout((string)$this->Controller->getRequest()->getQuery('layout'));
			$this->Controller->Flash->success(__d('setup', 'layout {0} activated', $this->Controller->getRequest()->getQuery('layout')));

			return $this->Controller->redirect($this->_cleanedUrl('layout'));
		}

		$this->issueMailing();
	}

	/**
	 * SetupComponent::startup()
	 *
	 * - Sets the layout based on session value 'Setup.layout'.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function startup(EventInterface $event) {
		$layout = $this->getController()->getRequest()->getSession()->read('Setup.layout');
		if ($layout) {
			$this->Controller->viewBuilder()->setLayout($layout);
		}
	}

	/**
	 * Notify admin about 404s and other self inflected issues right away (via email right now)
	 *
	 * @return void
	 */
	public function issueMailing() {
		if ($this->Controller->getName() !== 'CakeError' || empty($this->notifications['404'])) {
			return;
		}
		if (env('REMOTE_ADDR') === '127.0.0.1' || Configure::read('debug') > 0) {
			return;
		}
		$referer = $this->Controller->referer();
		if (strlen($referer) > 2 && (int)$this->getController()->getRequest()->getSession()->read('Report.404') < time() - 5 * MINUTE) {
			$text = '404:' . TB . TB . $this->Controller->getRequest()->getRequestTarget() .
			NL . 'Referer:' . TB . '' . $referer .
			NL . NL . 'Browser: ' . env('HTTP_USER_AGENT') .
			NL . 'IP: ' . env('REMOTE_ADDR');
			$uid = $this->getController()->getRequest()->getSession()->read('Auth.User.id');
			if ($uid) {
				$text .= NL . NL . 'UID: ' . $uid;
			}

			if (!$this->_notification('404!', $text)) {
				throw new InternalErrorException('Cannot send admin notification email');
			}
			$this->getController()->getRequest()->getSession()->write('Report.404', time());
		}
	}

	/**
	 * SetupComponent::log404()
	 *
	 * @param bool $notifyAdminOnInternalErrors
	 * @return void
	 */
	public function log404($notifyAdminOnInternalErrors = false) {
		if ($this->Controller->getName() === 'CakeError') {
			$referer = $this->Controller->referer();
			$this->Controller->log('REF: ' . $referer . ' - URL: ' . $this->Controller->getRequest()->getRequestTarget(), '404');
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
		$pwd = $this->Controller->getRequest()->getQuery('pwd');
		if ($pwd && $pwd === Configure::read('Config.pwd')) {
			return true;
		}

		return false;
	}

	/**
	 * Set layout for this session.
	 *
	 * @param string $layout
	 * @return void
	 */
	public function setLayout($layout) {
		if (!$layout) {
			$this->getController()->getRequest()->getSession()->delete('Setup.layout');

			return;
		}
		$this->getController()->getRequest()->getSession()->write('Setup.layout', $layout);
	}

	/**
	 * Set maintance mode for everybody except for the own IP which will
	 * be whitelisted.
	 *
	 * Alternatively, this can and should be done using the CLI shell.
	 *
	 * URL Options:
	 * - `duration` query string can be used to set a timeout maintenance window
	 *
	 * @param mixed $maintenance
	 * @return bool Success
	 */
	public function setMaintenance($maintenance) {
		$ip = env('REMOTE_ADDR');
		// optional length in minutes
		$length = (int)$this->Controller->getRequest()->getQuery('duration');

		$Maintenance = new Maintenance();
		if (!$Maintenance->setMaintenanceMode($maintenance ? $length : false)) {
			return false;
		}
		if (!$Maintenance->whitelist([$ip])) {
			return false;
		}

		return true;
	}

	/**
	 * Override debug level
	 *
	 * 0/1 to set, or -1 to unset.
	 *
	 * @param int $level Debug level
	 * @param string $type Type - session/ip [optional] (defaults to session)
	 * @return bool Success
	 */
	public function setDebug($level, $type = 'session') {
		$level = (int)$level;

		if ($type === 'session') {
			if ($level < 0) {
				$this->getController()->getRequest()->getSession()->delete('Setup.debug');

				return false;
			}
			$this->getController()->getRequest()->getSession()->write('Setup.debug', $level);

			return true;
		}

		$cookieName = Configure::read('Session.cookie');
		if (empty($cookieName)) {
			$cookieName = 'CAKEPHP';
		}

		$id = null;
		if ($type === 'session') {
			$id = $_COOKIE[$cookieName] ?? '';

		} elseif ($type === 'ip') {
			$ip = env('REMOTE_ADDR');
			$host = 'unknown';
			if (!empty($ip)) {
				$host = gethostbyaddr($ip);
			}
			$id = $ip . '-' . $host;
		}
		if ($id === null) {
			throw new Exception('Invalid type');
		}

		$file = TMP . 'debugOverride-' . $id . '.txt';
		if ($level < 0) {
			if (file_exists($file)) {
				unlink($file);
			}

			return false;
		}

		if (!empty($id)) {
			if (file_put_contents($file, $level)) {
				return (bool)$level;
			}
		}

		return false;
	}

	/**
	 * Clear cache of tmp folders
	 *
	 * @param string|null $type
	 * @return bool Success
	 */
	public function clearCache($type = null) {
		$config = $type ?: 'default';
		Cache::clear($config);

		return true;
	}

	/**
	 * Destroy the current user's session.
	 *
	 * @return bool Success
	 */
	public function clearSession() {
		$this->getController()->getRequest()->getSession()->destroy();

		return true;
	}

	/**
	 * Remove specific named param from parsed url array
	 *
	 * @param array|string $type
	 * @return array URL
	 */
	protected function _cleanedUrl($type) {
		$type = (array)$type;
		if (Configure::read('Config.productive')) {
			$type[] = 'pwd';
		}

		return Setup::cleanedUrl($type, $this->Controller->getRequest()->getAttribute('params') + ['?' => $this->Controller->getRequest()->getQuery()]);
	}

	/**
	 * Quick way of notifying admin
	 * Note: right now only via email
	 *
	 * @param string $title
	 * @param string $text
	 * @return bool Success
	 */
	protected function _notification($title, $text) {
		if (!isset($this->Mailer)) {
			$this->Mailer = new Mailer();
		} else {
			$this->Mailer->reset();
		}

		$this->Mailer->setTo(Configure::read('Config.adminEmail'), Configure::read('Config.adminName'));
		$this->Mailer->setSubject($title);
		//FIXME
		//$this->Mailer->setTemplate('simple_email');
		$this->Mailer->setViewVars(compact('text'));
		$this->Mailer->send();

		return true;
	}

}
