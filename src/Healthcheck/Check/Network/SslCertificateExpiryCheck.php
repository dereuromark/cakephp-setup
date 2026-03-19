<?php
declare(strict_types=1);

namespace Setup\Healthcheck\Check\Network;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Setup\Healthcheck\Check\Check;

class SslCertificateExpiryCheck extends Check {

	/**
	 * @var string
	 */
	public const INFO = 'Checks if the SSL certificate is not expiring soon.';

	protected string $level = self::LEVEL_WARNING;

	/**
	 * @var array<string|callable>
	 */
	protected array $scope = [
		self::SCOPE_WEB,
	];

	protected int $warningDays;

	protected int $errorDays;

	protected ?string $host;

	/**
	 * @param int $warningDays Warn when certificate expires within this many days (default: 30)
	 * @param int $errorDays Error when certificate expires within this many days (default: 7)
	 * @param string|null $host Host to check. Defaults to current application host.
	 */
	public function __construct(
		int $warningDays = 30,
		int $errorDays = 7,
		?string $host = null,
	) {
		$this->warningDays = $warningDays;
		$this->errorDays = $errorDays;
		$this->host = $host;
	}

	/**
	 * @return void
	 */
	public function check(): void {
		$host = $this->resolveHost();

		if (!$host) {
			$this->passed = true;
			$this->infoMessage[] = 'No host configured for SSL check. Configure via `Healthcheck.sslHost` or constructor.';

			return;
		}

		if (!extension_loaded('openssl')) {
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
			$this->failureMessage[] = 'OpenSSL extension is not loaded. Cannot perform SSL certificate check.';

			return;
		}

		$cert = $this->fetchCertificate($host);
		if ($cert === null) {
			return;
		}

		$this->evaluateCertificate($cert, $host);
	}

	/**
	 * Resolve the host to check.
	 *
	 * @return string|null
	 */
	protected function resolveHost(): ?string {
		if ($this->host !== null) {
			return $this->host;
		}

		$configuredHost = Configure::read('Healthcheck.sslHost');
		if ($configuredHost) {
			return $configuredHost;
		}

		$fullBaseUrl = Router::url('/', true);
		$parsed = parse_url($fullBaseUrl);

		return $parsed['host'] ?? null;
	}

	/**
	 * Fetch SSL certificate from host.
	 *
	 * @param string $host The host to connect to
	 * @return array<string, mixed>|null The parsed certificate or null on failure
	 */
	protected function fetchCertificate(string $host): ?array {
		$context = stream_context_create([
			'ssl' => [
				'capture_peer_cert' => true,
				'verify_peer' => false,
				'verify_peer_name' => false,
			],
		]);

		$socket = @stream_socket_client(
			"ssl://{$host}:443",
			$errno,
			$errstr,
			10,
			STREAM_CLIENT_CONNECT,
			$context,
		);

		if (!$socket) {
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
			$this->failureMessage[] = "Cannot connect to `{$host}:443`: {$errstr} (errno: {$errno})";

			return null;
		}

		$params = stream_context_get_params($socket);
		fclose($socket);

		if (!isset($params['options']['ssl']['peer_certificate'])) {
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
			$this->failureMessage[] = "No SSL certificate found for `{$host}`.";

			return null;
		}

		$cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
		if (!$cert || !isset($cert['validTo_time_t'])) {
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
			$this->failureMessage[] = 'Unable to parse SSL certificate.';

			return null;
		}

		return $cert;
	}

	/**
	 * Evaluate the certificate expiry.
	 *
	 * @param array<string, mixed> $cert The parsed certificate
	 * @param string $host The host being checked
	 * @return void
	 */
	protected function evaluateCertificate(array $cert, string $host): void {
		$expiryTime = (int)$cert['validTo_time_t'];
		$daysUntilExpiry = (int)(($expiryTime - time()) / 86400);
		$expiryDate = date('Y-m-d H:i:s', $expiryTime);

		$subject = $cert['subject']['CN'] ?? 'Unknown';
		$issuer = $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown';

		$this->infoMessage[] = "Certificate for `{$host}`: Subject=`{$subject}`, Issuer=`{$issuer}`";
		$this->infoMessage[] = "Expires: {$expiryDate} ({$daysUntilExpiry} days from now)";

		if ($daysUntilExpiry <= 0) {
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
			$this->failureMessage[] = "SSL certificate for `{$host}` has EXPIRED!";

			return;
		}

		if ($daysUntilExpiry <= $this->errorDays) {
			$this->passed = false;
			$this->level = static::LEVEL_ERROR;
			$this->failureMessage[] = "SSL certificate for `{$host}` expires in {$daysUntilExpiry} days!";

			return;
		}

		if ($daysUntilExpiry <= $this->warningDays) {
			$this->passed = false;
			$this->level = static::LEVEL_WARNING;
			$this->warningMessage[] = "SSL certificate for `{$host}` expires in {$daysUntilExpiry} days.";

			return;
		}

		$this->passed = true;
	}

}
