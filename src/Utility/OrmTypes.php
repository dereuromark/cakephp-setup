<?php

namespace Setup\Utility;

use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Cake\Utility\Hash;
use RuntimeException;

class OrmTypes {

	/**
	 * @var string
	 */
	public const NAMESPACE = 'Database/Type';

	/**
	 * @var string
	 */
	public const SUFFIX = 'Type';

	/**
	 * @param array $plugins
	 * @param array $map Map of already mapped types to exclude.
	 *
	 * @return array
	 */
	public static function getClasses(array $plugins = [], array $map = []): array {
		$exclude = [];
		if ($map) {
			$exclude = Hash::extract($map, '{s}.class');
		}

		$allClasses = ClassFinder::get('Database/Type', $plugins);
		foreach ($allClasses as $namespace => $classes) {
			$result = [];
			foreach ($classes as $class) {
				$namespacePrefix = str_replace('/', '\\', static::NAMESPACE);
				$fullClass = $namespacePrefix . '\\' . $class;
				if (strtoupper($namespace) !== strtoupper(Configure::readOrFail('App.namespace'))) {
					$fullClass = str_replace('/', '\\', $namespace) . '\\' . $fullClass;
				}
				$name = substr($class, 0, -strlen(static::SUFFIX));

				if ($exclude && in_array($fullClass, $exclude, true)) {
					continue;
				}

				$result[$name] = $fullClass;
			}

			$allClasses[$namespace] = $result;
			if (!$allClasses[$namespace]) {
				unset($allClasses[$namespace]);
			}
		}

		return $allClasses;
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function getMap(): array {
		/** @var array<string, string> $map */
		$map = TypeFactory::getMap();

		$result = [];
		foreach ($map as $type => $class) {
			$name = static::name($class);

			$result[$type] = [
				'name' => $name,
				'class' => $class,
			];
		}

		return $result;
	}

	/**
	 * @param string $class
	 *
	 * @return string
	 */
	protected static function name(string $class): string {
		$namespace = str_replace('/', '\\', static::NAMESPACE);
		preg_match('#^(.+)\\\\' . preg_quote($namespace) . '\\\\(.+)' . preg_quote(static::SUFFIX) . '#', $class, $matches);
		if (!$matches || empty($matches[1]) || empty($matches[2])) {
			throw new RuntimeException('Invalid type class: ' . $class);
		}

		if ($matches[1] === 'Cake' || $matches[1] === Configure::readOrFail('App.namespace')) {
			return $matches[2];
		}

		return $matches[1] . '.' . $matches[2];
	}

}
