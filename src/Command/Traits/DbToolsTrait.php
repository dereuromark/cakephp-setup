<?php

namespace Setup\Command\Traits;

use Cake\Core\App;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Pdo;
use RuntimeException;
use Shim\Filesystem\Folder;

/**
 * @mixin \Cake\Command\Command
 */
trait DbToolsTrait {

	/**
	 * @param string $name
	 *
	 * @return \Cake\Database\Connection
	 */
	protected function _getConnection(string $name = 'default') {
		if (!empty($this->params['connection'])) {
			$name = $this->params['connection'];
		}

		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get($name);

		return $connection;
	}

	/**
	 * @param string $prefix
	 *
	 * @return array<string>
	 */
	protected function _getTables(string $prefix = ''): array {
		$db = $this->_getConnection();
		$config = $db->config();
		$database = $config['database'];

		$script = "
SELECT table_name
FROM information_schema.tables AS tb
WHERE   table_schema = '$database'
AND table_name LIKE '$prefix%' OR table_name LIKE '\_%';";

		$res = $db->execute($script)->fetchAll(Pdo::FETCH_ASSOC);
		if (!$res) {
			throw new RuntimeException('No tables found for DB `' . $database . '`...');
		}

		/** @var array $whitelist */
		$whitelist = []; //Text::tokenize((string)$this->args->getOption('table'));

		$tables = [];
		foreach ($res as $key => $table) {
			if (str_starts_with($table['table_name'], '_')) {
				continue;
			}

			if ($whitelist && !in_array($table['table_name'], $whitelist)) {
				continue;
			}

			$tables[] = $table['table_name'];
		}

		sort($tables);

		return $tables;
	}

	/**
		* @param string|null $model
		* @param string|null $plugin
		*
		* @throws \RuntimeException
		*
		* @return array<\Cake\ORM\Table>
		*/
	protected function _getModels(?string $model, ?string $plugin): array {
		if ($model) {
			$className = App::className($plugin ? $plugin . '.' : $model, 'Model/Table', 'Table');
			if (!$className) {
				throw new RuntimeException('Model not found: ' . $model);
			}

			return [
				TableRegistry::getTableLocator()->get($plugin ? $plugin . '.' : $model),
			];
		}

		$folders = App::classPath('Model/Table', $plugin);

		$models = [];
		foreach ($folders as $folder) {
			$folderContent = (new Folder($folder))->read(Folder::SORT_NAME, true);

			foreach ($folderContent[1] as $file) {
				$name = pathinfo($file, PATHINFO_FILENAME);

				preg_match('#^(.+)Table$#', $name, $matches);
				if (!$matches) {
					continue;
				}

				$model = $matches[1];

				$className = App::className($plugin ? $plugin . '.' . $model : $model, 'Model/Table', 'Table');
				if (!$className) {
					continue;
				}

				$models[] = TableRegistry::getTableLocator()->get($plugin ? $plugin . '.' . $model : $model);
			}
		}

		return $models;
	}

}
