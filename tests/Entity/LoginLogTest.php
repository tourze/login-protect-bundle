<?php

namespace Tourze\LoginProtectBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\LoginProtectBundle\Entity\LoginLog;

class LoginLogTest extends TestCase
{
    /**
     * 测试实体的 getter 和 setter 方法
     */
    public function testGetterAndSetters(): void
    {
        $loginLog = new LoginLog();

        // 测试 CreateTime
        $createTime = new \DateTime();
        $loginLog->setCreateTime($createTime);
        $this->assertSame($createTime, $loginLog->getCreateTime());

        // 测试 Identifier
        $identifier = 'user@example.com';
        $loginLog->setIdentifier($identifier);
        $this->assertSame($identifier, $loginLog->getIdentifier());

        // 测试 Action
        $action = 'success';
        $loginLog->setAction($action);
        $this->assertSame($action, $loginLog->getAction());

        // 测试 UnlockTime
        $unlockTime = new \DateTime('+30 minutes');
        $loginLog->setUnlockTime($unlockTime);
        $this->assertSame($unlockTime, $loginLog->getUnlockTime());

        // 测试 SessionId
        $sessionId = 'abc123';
        $loginLog->setSessionId($sessionId);
        $this->assertSame($sessionId, $loginLog->getSessionId());

        // 测试 CreatedFromIp
        $createdFromIp = '127.0.0.1';
        $loginLog->setCreatedFromIp($createdFromIp);
        $this->assertSame($createdFromIp, $loginLog->getCreatedFromIp());
    }

    /**
     * 测试实体的默认值
     */
    public function testDefaultValues(): void
    {
        $loginLog = new LoginLog();

        $this->assertNull($loginLog->getId());
        $this->assertNull($loginLog->getCreateTime());
        $this->assertNull($loginLog->getIdentifier());
        $this->assertNull($loginLog->getAction());
        $this->assertNull($loginLog->getUnlockTime());
        $this->assertSame('', $loginLog->getSessionId());
        $this->assertNull($loginLog->getCreatedFromIp());
    }

    /**
     * 测试链式方法调用
     */
    public function testFluentInterfaces(): void
    {
        $loginLog = new LoginLog();

        $this->assertInstanceOf(LoginLog::class, $loginLog->setIdentifier('test'));
        $this->assertInstanceOf(LoginLog::class, $loginLog->setAction('login'));
        $this->assertInstanceOf(LoginLog::class, $loginLog->setSessionId('session'));
        $this->assertInstanceOf(LoginLog::class, $loginLog->setCreateTime(new \DateTime()));
        $this->assertInstanceOf(LoginLog::class, $loginLog->setUnlockTime(new \DateTime()));
    }
}
