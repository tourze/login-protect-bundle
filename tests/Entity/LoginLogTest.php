<?php

namespace Tourze\LoginProtectBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(LoginLog::class)]
final class LoginLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LoginLog();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'identifier' => ['identifier', 'test@example.com'];
        yield 'action' => ['action', 'login'];
        yield 'unlockTime' => ['unlockTime', new \DateTimeImmutable('+1 hour')];
        yield 'sessionId' => ['sessionId', 'abc123'];
        yield 'createdFromIp' => ['createdFromIp', '127.0.0.1'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
    }
}
