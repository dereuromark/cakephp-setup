<?php

namespace Setup\Utility;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Shim\Filesystem\Folder;

class ClassFinder {

	/**
	 * @param string $string
	 * @param array<string>|null $plugins
	 *
	 * @return array<string, array<string>>
	 */
	public static function get(string $string, ?array $plugins): array {
		$appPaths = App::classPath($string);
		$result = [];
		$classes = static::getClasses($appPaths);
		if ($classes) {
			$appNamespace = (string)Configure::readOrFail('App.namespace');
			$result[strtoupper($appNamespace)] = $classes;
		}

		if ($plugins === null) {
			$plugins = Plugin::loaded();
		}
		foreach ($plugins as $plugin) {
			$pluginPaths = App::classPath($string, $plugin);
			$classes = static::getClasses($pluginPaths);
			if ($classes) {
				$result[$plugin] = $classes;
			}
		}

		return $result;
	}

	/**
	 * @param array<string> $folders
	 *
	 * @return array<string>
	 */
	public static function getClasses(array $folders): array {
		$names = [];
		foreach ($folders as $folder) {
			$folderContent = (new Folder($folder))->read(Folder::SORT_NAME, true);

			foreach ($folderContent[1] as $file) {
				$name = pathinfo($file, PATHINFO_FILENAME);
				$names[] = $name;
			}

			foreach ($folderContent[0] as $subFolder) {
				$folderContent = (new Folder($folder . $subFolder))->read(Folder::SORT_NAME, true);

				foreach ($folderContent[1] as $file) {
					$name = pathinfo($file, PATHINFO_FILENAME);
					$names[] = $subFolder . '/' . $name;
				}
			}
		}

		return $names;
	}

}
