<?php

namespace Tourze\LoginProtectBundle\Tests\EventSubscriber;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;
use Tourze\LoginProtectBundle\EventSubscriber\LoginCheckSubscriber;
use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(LoginCheckSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class LoginCheckSubscriberTest extends AbstractEventSubscriberTestCase
{
    private LoginCheckSubscriber $subscriber;

    private LoginLogRepository $loginLogRepository;

    protected static function getEventSubscriberClass(): string
    {
        return LoginCheckSubscriber::class;
    }

    protected function onSetUp(): void
    {
        // 获取真实的订阅器，Repository 使用真实实现
        $this->subscriber = self::getService(LoginCheckSubscriber::class);
        $this->loginLogRepository = self::getService(LoginLogRepository::class);
    }

    
    /**
     * 测试非锁定用户的登录检查
     */
    public function testCheckLoginTimeWithNonLockedUser(): void
    {
        // 创建一个未锁定的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('success');
        $lastLog->setUnlockTime(null);
        $this->persistAndFlush($lastLog);

        // 创建真实用户和事件
        $user = $this->createNormalUser('user@example.com');
        $event = new BeforeLoginEvent();
        $event->setUser($user);

        // 执行测试 - 应该没有异常抛出
        $this->subscriber->checkLoginTime($event);
        $this->assertTrue(true); // 测试通过，因为没有异常
    }

    /**
     * 测试锁定用户的登录检查
     */
    public function testCheckLoginTimeWithLockedUser(): void
    {
        // 创建一个锁定的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('failure');
        $lastLog->setUnlockTime(CarbonImmutable::now()->addMinutes(30));
        $this->persistAndFlush($lastLog);

        // 创建真实用户和事件
        $user = $this->createNormalUser('user@example.com');
        $event = new BeforeLoginEvent();
        $event->setUser($user);

        // 执行测试 - 应该抛出 LockedAuthenticationException
        $this->expectException(LockedAuthenticationException::class);
        $this->expectExceptionMessage('登录次数过多，请稍后重试');
        $this->subscriber->checkLoginTime($event);
    }

    /**
     * 测试锁定已过期的用户登录检查
     */
    public function testCheckLoginTimeWithExpiredLock(): void
    {
        // 创建一个锁定已过期的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('failure');
        $lastLog->setUnlockTime(CarbonImmutable::now()->subMinutes(5)); // 5分钟前解锁
        $this->persistAndFlush($lastLog);

        // 创建真实用户和事件
        $user = $this->createNormalUser('user@example.com');
        $event = new BeforeLoginEvent();
        $event->setUser($user);

        // 执行测试 - 不应抛出异常，因为锁定已过期
        $this->subscriber->checkLoginTime($event);
        $this->assertTrue(true); // 测试通过，因为没有异常
    }

    /**
     * 测试没有登录记录的用户登录检查
     */
    public function testCheckLoginTimeWithNoLoginRecord(): void
    {
        // 创建真实用户和事件，但不创建任何登录记录
        $user = $this->createNormalUser('new-user@example.com');
        $event = new BeforeLoginEvent();
        $event->setUser($user);

        // 执行测试 - 没有登录记录，不应抛出异常
        $this->subscriber->checkLoginTime($event);
        $this->assertTrue(true); // 测试通过，因为没有异常
    }
}
