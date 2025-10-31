# login-protect-bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](../../LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [配置](#配置)
- [使用方法](#使用方法)
- [高级用法](#高级用法)
- [安全](#安全)
- [事件监听](#事件监听)
- [异常处理](#异常处理)
- [数据清理](#数据清理)
- [测试](#测试)
- [高级配置](#高级配置)
- [架构说明](#架构说明)
- [依赖](#依赖)
- [许可证](#许可证)

提供全面登录保护功能的 Symfony Bundle，包括登录失败记录、自动账户锁定和登录活动日志。

## 功能特性

- **登录尝试追踪**: 记录所有登录尝试（成功/失败/登出）及 IP 地址
- **账户锁定**: 多次登录失败后自动锁定账户
- **可配置锁定时长**: 通过环境变量控制锁定超时时间
- **IP 追踪**: 追踪 IP 地址用于安全审计
- **异步日志**: 使用异步数据库插入提升性能
- **数据清理**: 通过计划任务自动清理旧的登录日志

## 安装

```bash
composer require tourze/login-protect-bundle
```

## 配置

### 1. Bundle 注册

在 `config/bundles.php` 中添加 bundle：

```php
return [
    // ... 其他 bundles
    Tourze\LoginProtectBundle\LoginProtectBundle::class => ['all' => true],
];
```

### 2. 环境变量

在 `.env` 文件中配置锁定时长：

```env
# 多次失败后的锁定时长（分钟，默认: 30）
LOGIN_ATTEMPT_FAIL_LOCK_MINUTE=30

# 登录日志保留天数（默认: 120）
LOGIN_LOG_PERSIST_DAY_NUM=120
```

### 3. 数据库迁移

创建登录日志数据表：

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

## 使用方法

### 基本使用

Bundle 通过 Symfony 的安全事件自动追踪登录事件。基本功能无需额外代码。

### 手动登录日志记录

如果需要手动记录登录事件：

```php
use Tourze\LoginProtectBundle\Service\LoginService;

class YourController
{
    public function __construct(
        private LoginService $loginService
    ) {}
    
    public function someAction()
    {
        // 记录成功登录
        $this->loginService->saveLoginLog($user, 'success', $sessionId);
        
        // 记录失败登录
        $this->loginService->saveLoginLog($userIdentifier, 'failure');
        
        // 记录登出
        $this->loginService->saveLoginLog($user, 'logout');
    }
}
```

## 高级用法

### 自定义登录检查

您可以分发 `BeforeLoginEvent` 来触发登录保护检查：

```php
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class YourAuthenticator
{
    public function authenticate(Request $request): ?Passport
    {
        // ... 获取用户 ...
        
        // 检查用户是否被锁定
        $event = new BeforeLoginEvent($user);
        $this->eventDispatcher->dispatch($event);
        
        // 如果用户被锁定，将抛出 LockedAuthenticationException
        
        return $passport;
    }
}
```

## 安全

此 Bundle 提供多种安全功能：

- **暴力破解保护**: 多次失败尝试后自动锁定账户
- **IP 追踪**: 记录 IP 地址用于安全审计和取证分析
- **登录活动监控**: 全面记录所有身份验证事件
- **可配置锁定策略**: 根据安全需求自定义锁定时长

### 安全注意事项

- 确保正确配置锁定时长，平衡安全性和可用性
- 定期监控登录日志以发现可疑活动
- 考虑在网络层面实施额外的速率限制
- 使用 HTTPS 保护传输中的登录凭据

## 事件监听

Bundle 监听以下 Symfony 安全事件：

- `LoginSuccessEvent`: 记录成功登录
- `LoginFailureEvent`: 记录失败尝试并可能设置解锁时间
- `LogoutEvent`: 记录登出事件

## 异常处理

当账户被锁定时，Bundle 会抛出 `LockedAuthenticationException`。在认证流程中处理它：

```php
try {
    // 认证逻辑
} catch (LockedAuthenticationException $e) {
    // 账户被锁定，显示相应消息
    return new Response('由于多次失败尝试，账户已被锁定');
}
```

## 数据清理

登录日志基于计划任务配置自动清理。默认保留期为 120 天，可通过 `LOGIN_LOG_PERSIST_DAY_NUM` 环境变量配置。

## 测试

运行测试：

```bash
vendor/bin/phpunit packages/login-protect-bundle/tests
```

## 依赖

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- tourze/doctrine-async-insert-bundle
- tourze/doctrine-indexed-bundle
- tourze/doctrine-ip-bundle
- tourze/doctrine-snowflake-bundle
- tourze/doctrine-timestamp-bundle

## 许可证

MIT 许可证。详情请查看 [LICENSE](../../LICENSE)。

## 高级配置

### 自定义锁定策略

Bundle 使用环境变量 `LOGIN_ATTEMPT_FAIL_LOCK_MINUTE` 来控制锁定时长。您可以在不同环境中设置不同的值：

```env
# 开发环境：较短的锁定时间
LOGIN_ATTEMPT_FAIL_LOCK_MINUTE=5

# 生产环境：较长的锁定时间
LOGIN_ATTEMPT_FAIL_LOCK_MINUTE=60
```

### 数据保留策略

通过 `LOGIN_LOG_PERSIST_DAY_NUM` 控制日志保留时间：

```env
# 保留 30 天的登录日志
LOGIN_LOG_PERSIST_DAY_NUM=30

# 保留 1 年的登录日志
LOGIN_LOG_PERSIST_DAY_NUM=365
```

### 防火墙排除

Bundle 会自动排除开发环境的防火墙（`dev` 和 `safe_dev`），不记录这些环境的登录日志。

## 架构说明

### 核心组件

1. **LoginService**: 负责保存登录日志的核心服务
2. **LoginLogSubscriber**: 监听 Symfony 安全事件的事件订阅者
3. **LoginCheckSubscriber**: 检查账户锁定状态的事件订阅者
4. **LoginLog**: 登录日志实体，包含自动 IP 追踪和时间戳
5. **LoginLogRepository**: 登录日志的数据访问层

### 性能优化

- 使用异步插入减少对登录流程的性能影响
- 使用索引优化查询性能
- 支持定期清理历史数据减少存储空间

### 安全特性

- IP 地址自动记录用于安全审计
- 支持可配置的锁定策略
- 防止暴力破解攻击
- 详细的登录活动记录