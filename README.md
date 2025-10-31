# login-protect-bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](../../LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

A Symfony bundle that provides comprehensive login protection features including 
failed login attempt tracking, automatic account locking, and login activity logging.

## Features

- **Login Attempt Tracking**: Records all login attempts (success/failure/logout) with IP addresses
- **Account Locking**: Automatically locks accounts after multiple failed login attempts
- **Configurable Lock Duration**: Environment variable control for lock timeout
- **IP Tracking**: Tracks IP addresses for security auditing
- **Async Logging**: Uses async database insertion for better performance
- **Data Cleanup**: Automatic cleanup of old login logs via scheduled tasks

## Installation

```bash
composer require tourze/login-protect-bundle
```

## Configuration

### 1. Bundle Registration

Add the bundle to your `config/bundles.php`:

```php
return [
    // ... other bundles
    Tourze\LoginProtectBundle\LoginProtectBundle::class => ['all' => true],
];
```

### 2. Environment Variables

Configure lock duration in your `.env` file:

```env
# Lock duration in minutes after too many failed attempts (default: 30)
LOGIN_ATTEMPT_FAIL_LOCK_MINUTE=30

# How many days to keep login logs (default: 120)
LOGIN_LOG_PERSIST_DAY_NUM=120
```

### 3. Database Migration

Create the database table for login logs:

```sql
CREATE TABLE login_attempt (
    id BIGINT NOT NULL PRIMARY KEY,
    identifier VARCHAR(120) NOT NULL COMMENT '唯一标志',
    action VARCHAR(20) NOT NULL COMMENT '登录结果',
    unlock_time DATETIME DEFAULT NULL COMMENT '解锁时间',
    session_id VARCHAR(100) DEFAULT '' COMMENT '会话ID',
    created_from_ip VARCHAR(45) DEFAULT NULL COMMENT '创建时IP',
    create_time DATETIME NOT NULL COMMENT '创建时间',
    INDEX idx_identifier (identifier),
    INDEX idx_action (action),
    INDEX idx_session_id (session_id)
);
```

## Usage

### Basic Usage

The bundle automatically tracks login events through Symfony's security events. 
No additional code is required for basic functionality.

### Manual Login Logging

If you need to manually log login events:

```php
use Tourze\LoginProtectBundle\Service\LoginService;

class YourController
{
    public function __construct(
        private LoginService $loginService
    ) {}
    
    public function someAction()
    {
        // Log successful login
        $this->loginService->saveLoginLog($user, 'success', $sessionId);
        
        // Log failed login
        $this->loginService->saveLoginLog($userIdentifier, 'failure');
        
        // Log logout
        $this->loginService->saveLoginLog($user, 'logout');
    }
}
```

## Advanced Usage

### Custom Login Check

You can dispatch `BeforeLoginEvent` to trigger login protection checks:

```php
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class YourAuthenticator
{
    public function authenticate(Request $request): ?Passport
    {
        // ... get user ...
        
        // Check if user is locked
        $event = new BeforeLoginEvent($user);
        $this->eventDispatcher->dispatch($event);
        
        // If user is locked, LockedAuthenticationException will be thrown
        
        return $passport;
    }
}
```

## Security

This bundle provides several security features:

- **Brute Force Protection**: Automatically locks accounts after multiple failed attempts
- **IP Tracking**: Records IP addresses for security auditing and forensic analysis
- **Login Activity Monitoring**: Comprehensive logging of all authentication events
- **Configurable Lock Policies**: Customizable lock duration based on security requirements

### Security Considerations

- Ensure proper configuration of lock duration to balance security and usability
- Monitor login logs regularly for suspicious activity
- Consider implementing additional rate limiting at the network level
- Use HTTPS to protect login credentials in transit

## Events

The bundle listens to these Symfony security events:

- `LoginSuccessEvent`: Records successful logins
- `LoginFailureEvent`: Records failed attempts and may set unlock time
- `LogoutEvent`: Records logout events

## Exception Handling

The bundle throws `LockedAuthenticationException` when an account is locked. Handle it in your authentication flow:

```php
try {
    // authentication logic
} catch (LockedAuthenticationException $e) {
    // Account is locked, show appropriate message
    return new Response('Account locked due to too many failed attempts');
}
```

## Data Cleanup

Login logs are automatically cleaned up based on the scheduled task configuration. 
The default retention period is 120 days, configurable via `LOGIN_LOG_PERSIST_DAY_NUM` 
environment variable.

## Testing

Run tests:

```bash
vendor/bin/phpunit packages/login-protect-bundle/tests
```

## Dependencies

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- tourze/doctrine-async-insert-bundle
- tourze/doctrine-indexed-bundle
- tourze/doctrine-ip-bundle
- tourze/doctrine-snowflake-bundle
- tourze/doctrine-timestamp-bundle

## License

MIT License. See [LICENSE](../../LICENSE) for details.