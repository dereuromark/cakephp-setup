<?php
if (!Configure::read('Mail.smtpHost')) {
	Configure::write('Mail.smtpHost', Configure::read('Mail.smtp_host'));
}
if (!Configure::read('Mail.smtpUsername')) {
	Configure::write('Mail.smtpUsername', Configure::read('Mail.smtp_username'));
}
if (!Configure::read('Mail.smtpPassword')) {
	Configure::write('Mail.smtpPassword', Configure::read('Mail.smtp_password'));
}

/**
 * A wrapper to allow the EmailConfig to be smarter and adjust itself to the environment
 * - no real emails sent in debug mode (content logged to email_trace log file)
 * - dynamic setting of sensitive information (password, username, host)
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
				$this->default['host'] = $config['smtpHost'];
			}
			if (!empty($config['smtp_username'])) {
				$this->default['username'] = $config['smtpUsername'];
			}
			if (!empty($config['smtp_password'])) {
				$this->default['password'] = $config['smtpPassword'];
			}
		}
	}

}
