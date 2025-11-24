# FullBaseUrlCheck - Host Header Injection Protection

## Security Vulnerability

**Host Header Injection** is a critical security vulnerability that affects password reset functionality and any feature that generates absolute URLs in emails or CLI contexts.

### How the Attack Works

1. Attacker sends a password reset request with a malicious `Host` header
2. Application generates reset link using the attacker's malicious host
3. Victim receives email with link to attacker's domain: `http://attacker.com/reset_password/valid-token-123`
4. Victim clicks the link, attacker captures the valid reset token from the URL
5. Attacker uses the stolen token on the legitimate site to reset the victim's password

## The Fix

Hardcode `App.fullBaseUrl` in your configuration to prevent CakePHP from trusting the HTTP Host header:

```php
// config/app.php
'App' => [
    'fullBaseUrl' => 'https://yourdomain.com',
]
```

Or use environment variables:

```bash
# .env
APP_FULL_BASE_URL=https://yourdomain.com
```

## What the Healthcheck Does

The `FullBaseUrlCheck` validates three critical security requirements:

### 1. Checks if `App.fullBaseUrl` is Configured
**Severity: ERROR**

If not set, the application is vulnerable to Host Header Injection attacks.

### 2. Runtime Detection of Host Header Injection (Web Mode Only)
**Severity: CRITICAL ERROR**

When running in web mode (not CLI), the healthcheck performs a **runtime test** to detect if `App.fullBaseUrl` is being dynamically set from the `HTTP_HOST` header.

**How it works:**
- Compares the configured `fullBaseUrl` with the current `HTTP_HOST` request header
- If they match exactly, the application is likely vulnerable
- This catches the vulnerability regardless of where/how the configuration is set

**Vulnerable code pattern:**
```php
// ❌ VULNERABLE CODE - DO NOT USE!
if (!Configure::read('App.fullBaseUrl')) {
    $s = null;
    if (env('HTTPS')) {
        $s = 's';
    }

    $httpHost = env('HTTP_HOST');  // ⚠️ Trusts attacker-controlled header!
    if (isset($httpHost)) {
        Configure::write('App.fullBaseUrl', 'http' . $s . '://' . $httpHost);
    }
    unset($httpHost, $s);
}
```

This creates a **false sense of security** because:
- `App.fullBaseUrl` IS set (so basic checks pass)
- But it's set to whatever the attacker sends in the Host header!
- The application is still vulnerable

The runtime check will **FAIL** if it detects that the configured URL matches the current HTTP_HOST header.

**Why web mode only?**
- CLI scripts don't have HTTP_HOST headers
- The vulnerability only affects web requests
- This avoids false positives in command-line environments

### 3. Production Environment Checks
**Severity: WARNING**

In non-debug mode, validates:
- URL uses HTTPS (not HTTP)
- URL is not localhost/127.0.0.1

## Running the Healthcheck

### CLI
```bash
bin/cake healthcheck Core
```

For verbose output with details:
```bash
bin/cake healthcheck Core -v
```

### Web Interface
Access `/setup/healthcheck` in your application (details visible in debug mode)

### Automated Monitoring
Use with [QueueScheduler](https://github.com/dereuromark/cakephp-queue-scheduler) to run periodic checks:
```php
// Run healthcheck every hour and alert on failures
```

## Real-World Impact

This vulnerability has been discovered in production systems and can lead to:
- Complete account takeover
- Unauthorized password resets
- Phishing attacks using legitimate reset tokens
- Data breaches

## References

- [OWASP: Host Header Injection](https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/07-Input_Validation_Testing/17-Testing_for_Host_Header_Injection)
- [PortSwigger: Host Header Attacks](https://portswigger.net/web-security/host-header)
- [CakePHP Security Configuration](https://book.cakephp.org/4/en/development/configuration.html#general-configuration)
