<?php

namespace Setup\Middleware;

use InvalidArgumentException;

/**
 * Value object describing the contents of an RFC 9116 `security.txt`.
 *
 * `Expires` is not a field here: it is computed by the middleware from
 * `expiresInterval` so it is always in the future.
 *
 * Each field value may be a single string or a list of strings (repeated lines,
 * e.g. multiple `Contact`). `Contact` is required by RFC 9116 — constructing a
 * document without one throws.
 */
class SecurityTxt {

	/**
	 * @param array<string>|string $contact One or more contact URIs (`https:`, `mailto:`, `tel:`). Required.
	 * @param array<string>|string|null $canonical Canonical URI(s) of this file.
	 * @param array<string>|string|null $encryption Encryption key URI(s).
	 * @param array<string>|string|null $acknowledgments Hall-of-fame URI(s).
	 * @param array<string>|string|null $preferredLanguages RFC 5646 language tags, e.g. `en, de`.
	 * @param array<string>|string|null $policy Security policy URI(s).
	 * @param array<string>|string|null $hiring Security-related job URI(s).
	 * @param array<string>|string|null $csaf `provider-metadata.json` URI(s).
	 * @param string $expiresInterval `strtotime`-relative interval for the always-future `Expires`.
	 *
	 * @throws \InvalidArgumentException If no non-empty contact is provided.
	 */
	public function __construct(
		public readonly array|string $contact,
		public readonly array|string|null $canonical = null,
		public readonly array|string|null $encryption = null,
		public readonly array|string|null $acknowledgments = null,
		public readonly array|string|null $preferredLanguages = null,
		public readonly array|string|null $policy = null,
		public readonly array|string|null $hiring = null,
		public readonly array|string|null $csaf = null,
		public readonly string $expiresInterval = '+1 year',
	) {
		if (static::normalize($contact) === []) {
			throw new InvalidArgumentException(
				'A security.txt document requires at least one non-empty `Contact` value (RFC 9116).',
			);
		}
	}

	/**
	 * Ordered RFC 9116 field map, excluding the computed `Expires`.
	 *
	 * Field order is not significant per RFC 9116; this emits actionable fields
	 * first (how to report) and file metadata last (`Canonical`,
	 * `Preferred-Languages`) for readability. `Expires` is appended last by the
	 * middleware.
	 *
	 * @return array<string, string|array<string>>
	 */
	public function toFields(): array {
		$fields = [
			'Contact' => $this->contact,
			'Encryption' => $this->encryption,
			'Policy' => $this->policy,
			'Acknowledgments' => $this->acknowledgments,
			'CSAF' => $this->csaf,
			'Hiring' => $this->hiring,
			'Canonical' => $this->canonical,
			'Preferred-Languages' => $this->preferredLanguages,
		];

		return array_filter($fields, fn ($value): bool => $value !== null);
	}

	/**
	 * Normalize a field value (string or list) into a list of non-empty trimmed strings.
	 *
	 * @param mixed $value
	 * @return array<string>
	 */
	public static function normalize(mixed $value): array {
		if ($value === null || $value === '' || $value === []) {
			return [];
		}

		$values = is_array($value) ? $value : [$value];
		$result = [];
		foreach ($values as $item) {
			$item = trim((string)$item);
			if ($item !== '') {
				$result[] = $item;
			}
		}

		return $result;
	}

}
