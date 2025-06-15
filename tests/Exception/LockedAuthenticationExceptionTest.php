<?php

namespace Tourze\LoginProtectBundle\Tests\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;

class LockedAuthenticationExceptionTest extends TestCase
{
    public function test_constructor_withMessage_setsMessage(): void
    {
        $message = '登录次数过多，请稍后重试';
        $exception = new LockedAuthenticationException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function test_constructor_withEmptyMessage_setsEmptyMessage(): void
    {
        $exception = new LockedAuthenticationException('');

        $this->assertEquals('', $exception->getMessage());
    }

    public function test_constructor_withoutMessage_setsEmptyMessage(): void
    {
        $exception = new LockedAuthenticationException();

        $this->assertEquals('', $exception->getMessage());
    }

    public function test_constructor_withCode_setsCode(): void
    {
        $code = 429;
        $exception = new LockedAuthenticationException('Test message', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function test_constructor_withoutCode_setsZeroCode(): void
    {
        $exception = new LockedAuthenticationException('Test message');

        $this->assertEquals(0, $exception->getCode());
    }

    public function test_constructor_withPrevious_setsPrevious(): void
    {
        $previous = new Exception('Previous exception');
        $exception = new LockedAuthenticationException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_constructor_withoutPrevious_setsNullPrevious(): void
    {
        $exception = new LockedAuthenticationException('Test message');

        $this->assertNull($exception->getPrevious());
    }

    public function test_exception_extendsException(): void
    {
        $exception = new LockedAuthenticationException();

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function test_exception_isInstanceOfCorrectClass(): void
    {
        $exception = new LockedAuthenticationException();

        $this->assertInstanceOf(LockedAuthenticationException::class, $exception);
    }

    public function test_constructor_withAllParameters_setsAllProperties(): void
    {
        $message = '用户已被锁定';
        $code = 423; // Locked
        $previous = new Exception('原始异常');

        $exception = new LockedAuthenticationException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_toString_containsExceptionDetails(): void
    {
        $message = 'Account locked due to too many failed attempts';
        $exception = new LockedAuthenticationException($message);

        $string = (string) $exception;

        $this->assertStringContainsString('LockedAuthenticationException', $string);
        $this->assertStringContainsString($message, $string);
    }

    public function test_getMessage_withUnicodeMessage_handlesCorrectly(): void
    {
        $message = '用户登录失败次数过多，账户已被锁定，请稍后重试';
        $exception = new LockedAuthenticationException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function test_getCode_withNegativeCode_handlesCorrectly(): void
    {
        $code = -1;
        $exception = new LockedAuthenticationException('Test', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function test_getFile_returnsCorrectFile(): void
    {
        $exception = new LockedAuthenticationException('Test');

        $this->assertStringContainsString('LockedAuthenticationExceptionTest.php', $exception->getFile());
    }

    public function test_getLine_returnsCorrectLine(): void
    {
        $line = __LINE__ + 1;
        $exception = new LockedAuthenticationException('Test');

        $this->assertEquals($line, $exception->getLine());
    }

    public function test_getTrace_returnsArray(): void
    {
        $exception = new LockedAuthenticationException('Test');

        $trace = $exception->getTrace();

        $this->assertIsArray($trace);
        $this->assertNotEmpty($trace);
    }

    public function test_constructor_withLongMessage_handlesCorrectly(): void
    {
        $longMessage = str_repeat('用户账户已被锁定，请联系管理员。', 100);
        $exception = new LockedAuthenticationException($longMessage);

        $this->assertEquals($longMessage, $exception->getMessage());
    }

    public function test_exception_throwable(): void
    {
        $this->expectException(LockedAuthenticationException::class);
        $this->expectExceptionMessage('Test exception');

        throw new LockedAuthenticationException('Test exception');
    }

    public function test_exception_catchable(): void
    {
        $thrown = false;
        $caught = false;

        try {
            $thrown = true;
            throw new LockedAuthenticationException('Catchable exception');
        } catch (LockedAuthenticationException $e) {
            $caught = true;
            $this->assertEquals('Catchable exception', $e->getMessage());
        }

        $this->assertTrue($thrown);
        $this->assertTrue($caught);
    }
}
