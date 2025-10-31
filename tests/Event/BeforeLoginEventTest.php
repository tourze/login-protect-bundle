<?php

namespace Tourze\LoginProtectBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(BeforeLoginEvent::class)]
final class BeforeLoginEventTest extends AbstractEventTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 事件测试不需要特殊设置
    }

    public function testSetUserWithValidUserSetsUser(): void
    {
        $event = new BeforeLoginEvent();
        $user = new InMemoryUser('test@example.com', null);

        $event->setUser($user);

        $this->assertSame($user, $event->getUser());
    }

    public function testGetUserAfterSettingUserReturnsSameUser(): void
    {
        $event = new BeforeLoginEvent();
        $user = new InMemoryUser('test@example.com', null);

        $event->setUser($user);
        $retrievedUser = $event->getUser();

        $this->assertSame($user, $retrievedUser);
        $this->assertEquals('test@example.com', $retrievedUser->getUserIdentifier());
    }

    public function testSetUserWithDifferentUsersUpdatesUser(): void
    {
        $event = new BeforeLoginEvent();
        $user1 = new InMemoryUser('user1@example.com', null);
        $user2 = new InMemoryUser('user2@example.com', null);

        $event->setUser($user1);
        $this->assertSame($user1, $event->getUser());

        $event->setUser($user2);
        $this->assertSame($user2, $event->getUser());
        $this->assertNotSame($user1, $event->getUser());
    }

    public function testEventExtendsSymfonyEvent(): void
    {
        $event = new BeforeLoginEvent();

        $this->assertInstanceOf(Event::class, $event);
    }

    public function testEventIsInstanceOfBeforeLoginEvent(): void
    {
        $event = new BeforeLoginEvent();

        $this->assertInstanceOf(BeforeLoginEvent::class, $event);
    }

    public function testSetUserWithUserInterfaceStoresCorrectly(): void
    {
        $event = new BeforeLoginEvent();
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('mock@example.com');
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $event->setUser($user);

        $this->assertSame($user, $event->getUser());
        $this->assertEquals('mock@example.com', $event->getUser()->getUserIdentifier());
        $this->assertEquals(['ROLE_USER'], $event->getUser()->getRoles());
    }

    public function testSetUserMultipleTimesReturnsLatestUser(): void
    {
        $event = new BeforeLoginEvent();
        $users = [
            new InMemoryUser('user1@example.com', null),
            new InMemoryUser('user2@example.com', null),
            new InMemoryUser('user3@example.com', null),
        ];

        foreach ($users as $user) {
            $event->setUser($user);
        }

        $this->assertSame($users[2], $event->getUser());
        $this->assertEquals('user3@example.com', $event->getUser()->getUserIdentifier());
    }

    public function testSetUserWithComplexUserPreservesAllProperties(): void
    {
        $event = new BeforeLoginEvent();
        $user = $this->createMock(UserInterface::class);

        $user->method('getUserIdentifier')->willReturn('complex@example.com');
        $user->method('getRoles')->willReturn(['ROLE_USER', 'ROLE_ADMIN']);

        $event->setUser($user);
        $retrievedUser = $event->getUser();

        $this->assertEquals('complex@example.com', $retrievedUser->getUserIdentifier());
        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $retrievedUser->getRoles());
    }
}
