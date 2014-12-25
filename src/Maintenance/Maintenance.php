<?php
namespace Setup\Maintenance;

use Cake\Core\Configure;
use Cake\Network\Response;

/**
 * Handle maintenance / down-time of the application.
 *
 * @author Mark Scherer
 * @license MIT
 */
class Maintenance {

	public $file;

	public $template = 'maintenance.ctp';

	public function __construct() {
		$this->file = TMP . 'maintenance.txt';
	}

	/**
	 * Main functionality to trigger maintenance mode.
	 * Will automatically set the appropriate headers.
	 *
	 * Tip: Check for non CLI first
	 *
	 *  if (php_sapi_name() !== 'cli') {
	 *    App::uses('MaintenanceLib', 'Setup.Lib');
	 *    $Maintenance = new MaintenanceLib();
	 *    $Maintenance->checkMaintenance();
	 *  }
	 *
	 * @param bool $exit If Response should be sent and exited.
	 * @return void
	 * @deprecated Use Maintenance DispatcherFilter
	 */
	public function checkMaintenance($ipAddress = null, $exit = true) {
		if ($ipAddress === null) {
			$ipAddress = env('REMOTE_ADDRESS');
		}
		if (!$this->isMaintenanceMode($ipAddress)) {
			return;
		}
		$Response = new Response();
		$Response->statusCode(503);
		$Response->header('Retry-After', DAY);
		$body = __d('setup', 'Maintenance work');
		$template = APP . 'Template' . DS . 'Error' . DS . $this->template;
		if (file_exists($template)) {
			$body = file_get_contents($template);
		}

		$Response->body($body);
		if ($exit) {
			$Response->send();
			exit;
		}
	}

	/**
	 * Check if maintenance mode is on.
	 *
	 * If overwritable, it will set Configure value 'Maintenance.overwrite' with the
	 * corresponding IP so the SetupComponent can trigger a warning message here.
	 *
	 * @param string $ipAdddres If passed it allows access when it matches whitelisted IPs.
	 * @return bool Success
	 */
	public function isMaintenanceMode($ipAddress = null) {
		if (!file_exists($this->file)) {
			return false;
		}

		$content = file_get_contents($this->file);
		if ($content === false) {
			return false;
		}

		if ($content > 0 && $content < time()) {
			$this->setMaintenanceMode(false);
			return false;
		}

		if ($ipAddress) {
			if (file_exists(TMP . 'maintenanceOverride-' . $this->_slugIp($ipAddress) . '.txt')) {
				Configure::write('Maintenance.overwrite', $ipAddress);
				return false;
			}
		}

		return true;
	}

	/**
	 * Set maintenance mode.
	 *
	 * Integer (in minutes) to activate with timeout.
	 * Using 0 it will have no timeout.
	 *
	 * @param int|false $value False to deactivate, or Integer to activate.
	 * @return bool Success
	 */
	public function setMaintenanceMode($value) {
		if ($value === false) {
			if (!file_exists($this->file)) {
				return true;
			}
			return unlink($this->file);
		}

		if ($value) {
			$value = time() + $value * MINUTE;
		}

		return (bool)file_put_contents($this->file, $value);
	}

	/**
	 * Get the whitelist or add new IPs.
	 * Note: Expects IPs to be valid.
	 *
	 * @param array $newIps IP addressed to be added to the whitelist.
	 * @return mixed boolean succes for adding, an array of all whitelisted IPs otherwise.
	 */
	public function whitelist($newIps = array()) {
		if ($newIps) {
			foreach ($newIps as $ip) {
				$this->_addToWhitelist($ip);
			}
			return true;
		}

		$files = glob(TMP . "maintenanceOverride-*.txt");
		$ips = array();
		foreach ($files as $file) {
			$ip = pathinfo($file, PATHINFO_FILENAME);
			$ip = substr($ip, strpos($ip, '-') + 1);
			$ips[] = $this->_unslugIp($ip);
		}
		return $ips;
	}

	/**
	 * Clear whitelist. If IPs are passed, only those will be removed, otherwise all.
	 *
	 * @param array $ips
	 * @return bool Success
	 */
	public function clearWhitelist($ips = array()) {
		$files = glob(TMP . "maintenanceOverride-*.txt");
		foreach ($files as $file) {
			$ip = pathinfo($file, PATHINFO_FILENAME);
			$ip = substr($ip, strpos($ip, '-') + 1);
			if (!$ips || in_array($this->_unslugIp($ip), $ips)) {
				if (!unlink($file)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * MaintenanceLib::_addToWhitelist()
	 *
	 * @param string $ip Valid IP address.
	 * @return bool Success.
	 */
	protected function _addToWhitelist($ip) {
		$file = TMP . 'maintenanceOverride-' . $this->_slugIp($ip) . '.txt';
		if (!file_put_contents($file, 1)) {
			return false;
		}
		return true;
	}

	/**
	 * Handle special chars in IPv6.
	 *
	 * @return void
	 */
	protected function _slugIp($ip) {
		return str_replace(':', '#', $ip);
	}

	/**
	 * Handle special chars in IPv6.
	 *
	 * @return void
	 */
	protected function _unslugIp($ip) {
		return str_replace('#', ':', $ip);
	}

}
