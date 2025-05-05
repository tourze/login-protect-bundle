<?php

namespace Tourze\LoginProtectBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;

class LockedAuthenticationExceptionTest extends TestCase
{
    /**
     * 测试异常消息
     */
    public function testExceptionMessage(): void
    {
        $message = '登录次数过多，请稍后重试';
        $exception = new LockedAuthenticationException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    /**
     * 测试异常继承关系
     */
    public function testExceptionInheritance(): void
    {
        $exception = new LockedAuthenticationException();
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * 测试异常代码
     */
    public function testExceptionCode(): void
    {
        $code = 429; // Too Many Requests
        $exception = new LockedAuthenticationException('错误消息', $code);

        $this->assertSame($code, $exception->getCode());
    }
}
