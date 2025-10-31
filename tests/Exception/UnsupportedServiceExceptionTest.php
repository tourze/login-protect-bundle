<?php

namespace Tourze\LoginProtectBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\LoginProtectBundle\Exception\UnsupportedServiceException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(UnsupportedServiceException::class)]
final class UnsupportedServiceExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructorWithMessageSetsMessage(): void
    {
        $message = 'Service not supported';
        $exception = new UnsupportedServiceException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testConstructorWithEmptyMessageSetsEmptyMessage(): void
    {
        $exception = new UnsupportedServiceException('');

        $this->assertEquals('', $exception->getMessage());
    }

    public function testConstructorWithoutMessageSetsEmptyMessage(): void
    {
        $exception = new UnsupportedServiceException();

        $this->assertEquals('', $exception->getMessage());
    }

    public function testConstructorWithCodeSetsCode(): void
    {
        $code = 500;
        $exception = new UnsupportedServiceException('Test message', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testConstructorWithoutCodeSetsZeroCode(): void
    {
        $exception = new UnsupportedServiceException('Test message');

        $this->assertEquals(0, $exception->getCode());
    }

    public function testConstructorWithPreviousSetsPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new UnsupportedServiceException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorWithoutPreviousSetsNullPrevious(): void
    {
        $exception = new UnsupportedServiceException('Test message');

        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionExtendsException(): void
    {
        $exception = new UnsupportedServiceException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionIsInstanceOfCorrectClass(): void
    {
        $exception = new UnsupportedServiceException();

        $this->assertInstanceOf(UnsupportedServiceException::class, $exception);
    }

    public function testConstructorWithAllParametersSetsAllProperties(): void
    {
        $message = 'Unsupported service';
        $code = 501;
        $previous = new \Exception('Original exception');

        $exception = new UnsupportedServiceException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testToStringContainsExceptionDetails(): void
    {
        $message = 'Service not supported in this context';
        $exception = new UnsupportedServiceException($message);

        $string = (string) $exception;

        $this->assertStringContainsString('UnsupportedServiceException', $string);
        $this->assertStringContainsString($message, $string);
    }

    public function testGetMessageWithUnicodeMessageHandlesCorrectly(): void
    {
        $message = '不支持的服务类型';
        $exception = new UnsupportedServiceException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testGetCodeWithNegativeCodeHandlesCorrectly(): void
    {
        $code = -1;
        $exception = new UnsupportedServiceException('Test', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testGetFileReturnsCorrectFile(): void
    {
        $exception = new UnsupportedServiceException('Test');

        $this->assertStringContainsString('UnsupportedServiceExceptionTest.php', $exception->getFile());
    }

    public function testGetLineReturnsCorrectLine(): void
    {
        $line = __LINE__ + 1;
        $exception = new UnsupportedServiceException('Test');

        $this->assertEquals($line, $exception->getLine());
    }

    public function testGetTraceReturnsArray(): void
    {
        $exception = new UnsupportedServiceException('Test');

        $trace = $exception->getTrace();
        $this->assertNotEmpty($trace);
    }

    public function testConstructorWithLongMessageHandlesCorrectly(): void
    {
        $longMessage = str_repeat('This is a long message about unsupported service. ', 50);
        $exception = new UnsupportedServiceException($longMessage);

        $this->assertEquals($longMessage, $exception->getMessage());
    }

    public function testExceptionThrowable(): void
    {
        $this->expectException(UnsupportedServiceException::class);
        $this->expectExceptionMessage('Test exception');

        throw new UnsupportedServiceException('Test exception');
    }

    public function testExceptionCatchable(): void
    {
        $thrown = false;
        $caught = false;

        try {
            $thrown = true;
            throw new UnsupportedServiceException('Catchable exception');
        } catch (UnsupportedServiceException $e) {
            $caught = true;
            $this->assertEquals('Catchable exception', $e->getMessage());
        }

        $this->assertTrue($thrown);
        $this->assertTrue($caught);
    }
}
