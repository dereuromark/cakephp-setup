<?php
/*
 * SQL logger that writes to a file
 * Copyright (c) 2008 Matt Curry
 * www.PseudoCoder.com
 *
 * 90% of this code is taken from the DebugKit
 * http://thechaw.com/debug_kit
 *
 * @author      Matt Curry <matt@pseudocoder.com>
 * @license     MIT
 */
App::uses('Xml', 'Utility');
App::uses('Component', 'Controller');

/**
 * Logs now to sql.log
 */
class SqlLogComponent extends Component {

	const LOG_FILE = 'sql';

	public $hideElements = array(
		'SHOW',
		'SELECT CHARACTER_SET_NAME',
		'DESCRIBE'
	);

	public function beforeRender(Controller $Controller) {
		parent::beforeRender($Controller);

		$queryLogs = array();
		if (!class_exists('ConnectionManager')) {
			return;
		}
		if (!Configure::read('System.sqlLog')) {
			return;
		}

		$dbConfigs = ConnectionManager::sourceList();
		foreach ($dbConfigs as $configName) {
			$db = ConnectionManager::getDataSource($configName);

			if (!method_exists($db, 'getLog')) {
				return array();
			}
			$log = $db->getLog();

			$sql = array($log['count'] . ' queries took ' . $log['time'] . 'ms');

			foreach ($log['log'] as $query) {
				if ($this->isHidden($query)) {
					continue;
				}
				$sql[] = '# ' . $query['query'] . '(A' . $query['affected'] . ' - N' . $query['numRows'] . ' - ' . $query['took'] . 'ms)';
			}
			$this->log(implode(PHP_EOL, $sql), 'sql');
		}
	}

	/**
	 */
	public function isHidden($query) {
		foreach ($this->hideElements as $e) {
			if (startsWith($query['query'], $e . ' ')) {
				return true;
			}
		}
		return false;
	}

}
