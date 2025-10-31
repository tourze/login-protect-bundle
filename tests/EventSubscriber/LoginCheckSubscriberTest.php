<?php

namespace Tourze\LoginProtectBundle\Tests\EventSubscriber;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
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

    private LoginLogRepository|MockObject $loginLogRepository;

    protected static function getEventSubscriberClass(): string
    {
        return LoginCheckSubscriber::class;
    }

    protected function onSetUp(): void
    {
        // 通过容器设置 mock 的 LoginLogRepository
        $this->loginLogRepository = $this->createMock(LoginLogRepository::class);
        self::getContainer()->set(LoginLogRepository::class, $this->loginLogRepository);
    }

    private function getSubscriber(): LoginCheckSubscriber
    {
        if (!isset($this->subscriber)) {
            $this->subscriber = self::getService(LoginCheckSubscriber::class);
        }

        return $this->subscriber;
    }

    /**
     * 测试非锁定用户的登录检查
     */
    public function testCheckLoginTimeWithNonLockedUser(): void
    {
        // 初始化 subscriber 和 mock repository
        $this->getSubscriber();

        // 创建模拟对象
        $event = $this->createMock(BeforeLoginEvent::class);
        $user = $this->createMock(UserInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // 创建一个未锁定的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('success');

        // 设置期望
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com')
        ;

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', 'DESC')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($lastLog)
        ;

        // 执行测试 - 应该没有异常抛出
        $this->getSubscriber()->checkLoginTime($event);
        // 测试通过，因为没有异常
    }

    /**
     * 测试锁定用户的登录检查
     */
    public function testCheckLoginTimeWithLockedUser(): void
    {
        // 初始化 subscriber 和 mock repository
        $this->getSubscriber();

        // 创建模拟对象
        $event = $this->createMock(BeforeLoginEvent::class);
        $user = $this->createMock(UserInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // 创建一个锁定的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('failure');
        $lastLog->setUnlockTime(CarbonImmutable::now()->addMinutes(30));

        // 设置期望
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com')
        ;

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', 'DESC')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($lastLog)
        ;

        // 执行测试 - 应该抛出 LockedAuthenticationException
        $this->expectException(LockedAuthenticationException::class);
        $this->expectExceptionMessage('登录次数过多，请稍后重试');
        $this->getSubscriber()->checkLoginTime($event);
    }

    /**
     * 测试锁定已过期的用户登录检查
     */
    public function testCheckLoginTimeWithExpiredLock(): void
    {
        // 初始化 subscriber 和 mock repository
        $this->getSubscriber();

        // 创建模拟对象
        $event = $this->createMock(BeforeLoginEvent::class);
        $user = $this->createMock(UserInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // 创建一个锁定已过期的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('failure');
        $lastLog->setUnlockTime(CarbonImmutable::now()->subMinutes(5)); // 5分钟前解锁

        // 设置期望
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com')
        ;

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', 'DESC')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($lastLog)
        ;

        // 执行测试 - 不应抛出异常，因为锁定已过期
        $this->getSubscriber()->checkLoginTime($event);
        // 测试通过，因为没有异常
    }

    /**
     * 测试没有登录记录的用户登录检查
     */
    public function testCheckLoginTimeWithNoLoginRecord(): void
    {
        // 初始化 subscriber 和 mock repository
        $this->getSubscriber();

        // 创建模拟对象
        $event = $this->createMock(BeforeLoginEvent::class);
        $user = $this->createMock(UserInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // 设置期望
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com')
        ;

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', 'DESC')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null)
        ;

        // 执行测试 - 没有登录记录，不应抛出异常
        $this->getSubscriber()->checkLoginTime($event);
        // 测试通过，因为没有异常
    }
}
