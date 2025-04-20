<?php

namespace Setup\Utility;

use Cake\Validation\Validation as CakeValidation;

class Validation extends CakeValidation {

	/**
	 * Validation of an IP address or subnet.
	 *
	 * @param mixed $check The string to test.
	 * @param string $type The IP Protocol version to validate against
	 * @return bool Success
	 */
	public static function ipOrSubnet(mixed $check, string $type = 'both'): bool {
		if (!is_string($check)) {
			return false;
		}

		if (!str_contains($check, '/')) {
			return static::ip($check, $type);
		}

		[$ip, $mask] = explode('/', $check, 2);

		// Validate mask part (IPv4: 0-32, IPv6: 0-128)
		if (in_array($type, ['both', 'ipv4', true]) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return is_numeric($mask) && $mask >= 0 && $mask <= 32;
		}
		if (in_array($type, ['both', 'ipv6', true]) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return is_numeric($mask) && $mask >= 0 && $mask <= 128;
		}

		return false;
	}

}
