<?php

namespace Tourze\LoginProtectBundle\Tests\EventSubscriber;

use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;
use Tourze\LoginProtectBundle\EventSubscriber\LoginCheckSubscriber;
use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;

class LoginCheckSubscriberTest extends TestCase
{
    private LoginCheckSubscriber $subscriber;
    private LoginLogRepository|MockObject $loginLogRepository;

    protected function setUp(): void
    {
        $this->loginLogRepository = $this->createMock(LoginLogRepository::class);
        $this->subscriber = new LoginCheckSubscriber($this->loginLogRepository);
    }

    /**
     * 测试非锁定用户的登录检查
     */
    public function testCheckLoginTime_withNonLockedUser(): void
    {
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
            ->willReturn($user);

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', Criteria::DESC)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($lastLog);

        // 执行测试 - 应该没有异常抛出
        $this->subscriber->checkLoginTime($event);
        // 测试通过，因为没有异常
    }

    /**
     * 测试锁定用户的登录检查
     */
    public function testCheckLoginTime_withLockedUser(): void
    {
        // 创建模拟对象
        $event = $this->createMock(BeforeLoginEvent::class);
        $user = $this->createMock(UserInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // 创建一个锁定的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('failure');
        $lastLog->setUnlockTime(Carbon::now()->addMinutes(30));

        // 设置期望
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', Criteria::DESC)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($lastLog);

        // 执行测试 - 应该抛出 LockedAuthenticationException
        $this->expectException(LockedAuthenticationException::class);
        $this->expectExceptionMessage('登录次数过多，请稍后重试');
        $this->subscriber->checkLoginTime($event);
    }

    /**
     * 测试锁定已过期的用户登录检查
     */
    public function testCheckLoginTime_withExpiredLock(): void
    {
        // 创建模拟对象
        $event = $this->createMock(BeforeLoginEvent::class);
        $user = $this->createMock(UserInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // 创建一个锁定已过期的 LoginLog
        $lastLog = new LoginLog();
        $lastLog->setIdentifier('user@example.com');
        $lastLog->setAction('failure');
        $lastLog->setUnlockTime(Carbon::now()->subMinutes(5)); // 5分钟前解锁

        // 设置期望
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', Criteria::DESC)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($lastLog);

        // 执行测试 - 不应抛出异常，因为锁定已过期
        $this->subscriber->checkLoginTime($event);
        // 测试通过，因为没有异常
    }

    /**
     * 测试没有登录记录的用户登录检查
     */
    public function testCheckLoginTime_withNoLoginRecord(): void
    {
        // 创建模拟对象
        $event = $this->createMock(BeforeLoginEvent::class);
        $user = $this->createMock(UserInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // 设置期望
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $this->loginLogRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.identifier = :identifier')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('identifier', 'user@example.com')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', Criteria::DESC)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        // 执行测试 - 没有登录记录，不应抛出异常
        $this->subscriber->checkLoginTime($event);
        // 测试通过，因为没有异常
    }
}
