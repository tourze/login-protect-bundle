<?php

namespace Tourze\LoginProtectBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;
use Tourze\LoginProtectBundle\Service\ExceptionFactory;

/**
 * @internal
 */
#[CoversClass(ExceptionFactory::class)]
final class ExceptionFactoryTest extends TestCase
{
    private ExceptionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ExceptionFactory();
    }

    public function testCreateLockedAuthenticationExceptionWithoutParameters(): void
    {
        $exception = $this->factory->createLockedAuthenticationException();

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCreateLockedAuthenticationExceptionWithMessage(): void
    {
        $message = '测试消息';
        $exception = $this->factory->createLockedAuthenticationException($message);

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCreateLockedAuthenticationExceptionWithMessageAndCode(): void
    {
        $message = '测试消息';
        $code = 429;
        $exception = $this->factory->createLockedAuthenticationException($message, $code);

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCreateLockedAuthenticationExceptionWithAllParameters(): void
    {
        $message = '测试消息';
        $code = 429;
        $previous = new \Exception('上一个异常');
        $exception = $this->factory->createLockedAuthenticationException($message, $code, $previous);

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCreateLockedAuthenticationExceptionWithEmptyMessage(): void
    {
        $exception = $this->factory->createLockedAuthenticationException('');

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
    }

    public function testCreateLockedAuthenticationExceptionWithUnicodeMessage(): void
    {
        $message = '用户登录失败次数过多，账户已被锁定';
        $exception = $this->factory->createLockedAuthenticationException($message);

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
}
