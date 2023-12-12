<?php

namespace Setup\Command\Traits;

use Cake\Collection\Collection;
use Cake\Core\App;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use RuntimeException;
use Shim\Filesystem\Folder;

/**
 * @mixin \Cake\Command\Command
 */
trait DbToolsTrait {

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
