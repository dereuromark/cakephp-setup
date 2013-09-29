<?php

/**
 * A wrapper to allow the EmailConfig to be smarter and adjust itself to the environment
 * - no real emails sent in debug mode (content logged to email_trace log file)
 * - dynamic setting of sensitive information (password, ...)
 * - allow setting of extensive logging feature
 *
 * @author Mark Scherer
 * @license MIT
 */
class BaseEmailConfig {

	public function __construct() {
		$pwds = (array)Configure::read('Email.Pwd');
		foreach ($pwds as $key => $val) {
			if (isset($this->{$key})) {
				$this->{$key}['password'] = $val;
			}
		}

		if (!empty($this->default['log'])) {
			$this->default['report'] = true;
		}
		if (isset($this->default['log'])) {
			unset($this->default['log']);
		}
		if (isset($this->default['trace'])) {
			$this->default['log'] = 'email_trace';
		}

		if (Configure::read('debug') && !Configure::read('Email.live')) {
			$this->default['transport'] = 'Debug';
			if (!isset($this->default['trace'])) {
				$this->default['log'] = 'email_trace';
			}
		}
		if ($config = Configure::read('Mail')) {
			if (!empty($config['smtp_host'])) {
				$this->default['host'] = $config['smtp_host'];
			}
			if (!empty($config['smtp_username'])) {
				$this->default['username'] = $config['smtp_username'];
			}
			if (!empty($config['smtp_password'])) {
				$this->default['password'] = $config['smtp_password'];
			}
		}
	}

}
