<?php

if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0770);
}
if (!defined('BACKUPS')) {
	define('BACKUPS', APP . 'files' . DS . 'backups' . DS);
}

App::uses('AppShell', 'Console/Command');
App::uses('File', 'Utility');
App::uses('Inflector', 'Utility');

class WriteSqlTask extends AppShell {

	/**
	 * WriteSqlTask::execute()
	 *
	 * @param array $tables
	 * @return void
	 */
	public function execute($tables) {
		$today = date(FORMAT_DB_DATE) . '_' . date('H-i-s');

		$File = new File(BACKUPS . 'dump_' . $today . '.sql', true, CHMOD_PUBLIC);

		foreach ($tables as $key => $value) {
			$tableName = Inflector::tableize($key);

			$File->write('#' . "\n" . '# Table ' . $tableName . "\n" . '#' . "\n");
			$File->write($value['table'] . "\n\n");

			foreach ($value['contents'] as $content) {
				$File->write($content . "\n");
			}
			$File->write("\n\n\n\n");
		}
		$File->close();
	}

}
