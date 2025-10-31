<?php

namespace Tourze\LoginProtectBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;
use Tourze\LoginProtectBundle\Service\ExceptionFactory;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(LockedAuthenticationException::class)]
final class LockedAuthenticationExceptionTest extends AbstractExceptionTestCase
{
    private ExceptionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ExceptionFactory();
    }

    public function testConstructorWithMessageSetsMessage(): void
    {
        $message = '登录次数过多，请稍后重试';
        $exception = $this->factory->createLockedAuthenticationException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testConstructorWithEmptyMessageSetsEmptyMessage(): void
    {
        $exception = $this->factory->createLockedAuthenticationException('');

        $this->assertEquals('', $exception->getMessage());
    }

    public function testConstructorWithoutMessageSetsEmptyMessage(): void
    {
        $exception = $this->factory->createLockedAuthenticationException();

        $this->assertEquals('', $exception->getMessage());
    }

    public function testConstructorWithCodeSetsCode(): void
    {
        $code = 429;
        $exception = $this->factory->createLockedAuthenticationException('Test message', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testConstructorWithoutCodeSetsZeroCode(): void
    {
        $exception = $this->factory->createLockedAuthenticationException('Test message');

        $this->assertEquals(0, $exception->getCode());
    }

    public function testConstructorWithPreviousSetsPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = $this->factory->createLockedAuthenticationException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorWithoutPreviousSetsNullPrevious(): void
    {
        $exception = $this->factory->createLockedAuthenticationException('Test message');

        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionExtendsException(): void
    {
        $exception = $this->factory->createLockedAuthenticationException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionIsInstanceOfCorrectClass(): void
    {
        $exception = $this->factory->createLockedAuthenticationException();

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
    }

    public function testConstructorWithAllParametersSetsAllProperties(): void
    {
        $message = '用户已被锁定';
        $code = 423; // Locked
        $previous = new \Exception('原始异常');

        $exception = $this->factory->createLockedAuthenticationException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testToStringContainsExceptionDetails(): void
    {
        $message = 'Account locked due to too many failed attempts';
        $exception = $this->factory->createLockedAuthenticationException($message);

        $string = (string) $exception;

        $this->assertStringContainsString('LockedAuthenticationException', $string);
        $this->assertStringContainsString($message, $string);
    }

    public function testGetMessageWithUnicodeMessageHandlesCorrectly(): void
    {
        $message = '用户登录失败次数过多，账户已被锁定，请稍后重试';
        $exception = $this->factory->createLockedAuthenticationException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testGetCodeWithNegativeCodeHandlesCorrectly(): void
    {
        $code = -1;
        $exception = $this->factory->createLockedAuthenticationException('Test', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testGetFileReturnsCorrectFile(): void
    {
        $exception = $this->factory->createLockedAuthenticationException('Test');

        // 通过工厂创建的异常，文件路径应该是工厂文件
        $this->assertStringContainsString('ExceptionFactory.php', $exception->getFile());
    }

    public function testGetLineReturnsCorrectLine(): void
    {
        $exception = $this->factory->createLockedAuthenticationException('Test');

        // 通过工厂创建的异常，行号应该是工厂文件中的行号
        // 我们只需要验证行号是一个正整数
        $this->assertIsInt($exception->getLine());
        $this->assertGreaterThan(0, $exception->getLine());
    }

    public function testGetTraceReturnsArray(): void
    {
        $exception = $this->factory->createLockedAuthenticationException('Test');

        $trace = $exception->getTrace();
        $this->assertNotEmpty($trace);
    }

    public function testConstructorWithLongMessageHandlesCorrectly(): void
    {
        $longMessage = str_repeat('用户账户已被锁定，请联系管理员。', 100);
        $exception = $this->factory->createLockedAuthenticationException($longMessage);

        $this->assertEquals($longMessage, $exception->getMessage());
    }

    public function testExceptionThrowable(): void
    {
        $this->expectException(LockedAuthenticationException::class);
        $this->expectExceptionMessage('Test exception');

        throw $this->factory->createLockedAuthenticationException('Test exception');
    }

    public function testExceptionCatchable(): void
    {
        $thrown = false;
        $caught = false;

        try {
            $thrown = true;
            throw $this->factory->createLockedAuthenticationException('Catchable exception');
        } catch (LockedAuthenticationException $e) {
            $caught = true;
            $this->assertEquals('Catchable exception', $e->getMessage());
        }

        $this->assertTrue($thrown);
        $this->assertTrue($caught);
    }
}
