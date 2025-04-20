<?php

namespace Setup\Maintenance;

/**
 * Handle maintenance / down-time of the application.
 *
 * @author Mark Scherer
 * @license MIT
 */
class Maintenance {

	/**
	 * @var string
	 */
	public $file;

	/**
	 * @var string
	 */
	public $whitelistFile;

	/**
	 * @var string
	 */
	public $template = 'maintenance.ctp';

	public function __construct() {
		$this->file = TMP . 'maintenance.txt';
		$this->whitelistFile = TMP . 'maintenance_whitelist.txt';
	}

	/**
	 * Check if maintenance mode is on.
	 *
	 * If overwritable, it will set Configure value 'Maintenance.overwrite' with the
	 * corresponding IP so the SetupComponent can trigger a warning message here.
	 *
	 * @param string|null $ipAddress If passed, it allows access when it matches whitelisted IPs.
	 * @return bool Success
	 */
	public function isMaintenanceMode(?string $ipAddress = null): bool {
		if (!file_exists($this->file)) {
			return false;
		}

		if ($ipAddress) {
			return !$this->whitelisted($ipAddress);
		}

		return true;
	}

	/**
	 * @param string $ipAddress
	 * @return bool
	 */
	public function whitelisted(string $ipAddress): bool {
		$whitelistedIps = $this->whitelist();
		foreach ($whitelistedIps as $whitelistedIp) {
			if ($this->ipInRange($ipAddress, $whitelistedIp)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $ip
	 * @param string $range
	 * @return bool
	 */
	protected function ipInRange(string $ip, string $range): bool {
		if (!str_contains($range, '/')) {
			return $ip === $range; // Exact match
		}

		[$subnet, $bits] = explode('/', $range);
		$ip = ip2long($ip);
		$subnet = ip2long($subnet);
		$mask = -1 << (32 - (int)$bits);
		$subnet &= $mask; // Network part

		return ($ip & $mask) === $subnet;
	}

	/**
	 * Set maintenance mode.
	 *
	 * @param bool $value
	 * @return bool Success
	 */
	public function setMaintenanceMode(bool $value): bool {
		if ($value === false) {
			if (!file_exists($this->file)) {
				return true;
			}

			return unlink($this->file);
		}

		if (file_exists($this->file)) {
			return true;
		}

		return (bool)file_put_contents($this->file, '');
	}

	/**
	 * Add new IPs.
	 * Note: Expects IPs to be valid.
	 *
	 * @param array<string> $newIps IP addressed to be added to the whitelist.
	 * @return void
	 */
	public function addToWhitelist(array $newIps = []) {
		if ($newIps) {
			foreach ($newIps as $ip) {
				$this->_addToWhitelist($ip);
			}
		}
	}

	/**
	 * Get the whitelist
	 *
	 * @return array<string>
	 */
	public function whitelist(): array {
		if (!file_exists($this->whitelistFile)) {
			return [];
		}

		$content = file_get_contents($this->whitelistFile);
		if ($content === false) {
			return [];
		}

		if (empty($content)) {
			return [];
		}

		return explode(PHP_EOL, $content);
	}

	/**
	 * Clear whitelist. If IPs are passed, only those will be removed, otherwise all.
	 *
	 * @param array<string> $ips
	 * @return void
	 */
	public function clearWhitelist(array $ips = []): void {
		if (!$ips) {
			if (file_exists($this->whitelistFile)) {
				unlink($this->whitelistFile);
			}

			return;
		}

		$whitelistedIps = $this->whitelist();
		foreach ($whitelistedIps as $k => $ip) {
			if (in_array($ip, $ips, true)) {
				unset($whitelistedIps[$k]);
			}
		}

		$content = implode(PHP_EOL, $whitelistedIps);
		file_put_contents($this->whitelistFile, trim($content));
	}

	/**
	 * MaintenanceLib::_addToWhitelist()
	 *
	 * @param string $ip Valid IP address.
	 * @param bool $debugMode
	 * @return bool Success.
	 */
	protected function _addToWhitelist(string $ip, bool $debugMode = false): bool {
		$content = '';
		if (file_exists($this->whitelistFile)) {
			$content = (string)file_get_contents($this->whitelistFile);
		}

		if (!str_contains($content, $ip)) {
			$content .= PHP_EOL . $ip;
		}
		if (!file_put_contents($this->whitelistFile, trim($content))) {
			return false;
		}

		return true;
	}

}
