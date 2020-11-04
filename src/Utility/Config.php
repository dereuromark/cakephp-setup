<?php

namespace Setup\Utility;

use RuntimeException;

class Config {

	/**
	 * @return array
	 */
	public static function getEnvVars(): array {
		$path = CONFIG;

		$envVars = $result = [];

		$files = glob($path . 'app*\.php') ?: [];
		foreach ($files as $file) {
			$content = file_get_contents($file);
			if ($content === false) {
				throw new RuntimeException('Cannot read file: ' . $file);
			}
			preg_match_all('#env\(\'([A-Z_)]+)\'.*\)#', $content, $matches);

			$envs = $matches ? $matches[1] : [];
			if (!$envs) {
				continue;
			}

			$envVars = array_merge($envVars, $envs);
		}

		foreach ($envVars as $envVar) {
			$result[$envVar] = getenv($envVar);
		}

		return $result;
	}

	/**
	 * @return array|null
	 */
	public static function getLocal(): ?array {
		$file = CONFIG . 'app_local.php';
		if (!file_exists($file)) {
			return null;
		}

		$config = include $file;

		array_walk_recursive($config, static function(&$value) {
			if (is_string($value) && $value !== '') {
				$value = 'value';
			}

			return $value;
		});

		return static::configTree($config);
	}

	/**
	 * @param array $array
	 *
	 * @return array
	 */
	public static function configTree(array $array): array {
		$result = [];

		foreach ($array as $k => $v) {
			if (!is_array($v)) {
				$result[$k] = ['name' => $k, 'value' => $v, 'children' => []];

				continue;
			}

			$result[$k] = $v;
			$result[$k]['name'] = $k;

			if (is_string($k) && !empty($v)) {
				$result[$k]['children'] = static::configTree($v);
				$result[$k]['value'] = $v;
			} else {
				$result[$k]['children'] = [];
				$result[$k]['value'] = $v;
			}
		}

		return $result;
	}

	/**
	 * Replaces sensitive strings with dummy text for security reasons.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected static function value($value) {
		if (!is_string($value) || $value === '') {
			return $value;
		}

		if (in_array(strtoupper($value), ['0', '1', 'TRUE', 'FALSE'], true)) {
			return $value;
		}

		return $value;
	}

}
