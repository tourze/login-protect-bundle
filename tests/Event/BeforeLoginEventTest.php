<?php

namespace Tourze\LoginProtectBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;

class BeforeLoginEventTest extends TestCase
{
    private function createMockUser(string $identifier): UserInterface
    {
        return new class($identifier) implements UserInterface {
            public function __construct(private string $identifier) {}
            
            public function getUserIdentifier(): string
            {
                return $this->identifier;
            }

            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void {}
        };
    }

    public function test_setUser_withValidUser_setsUser(): void
    {
        $event = new BeforeLoginEvent();
        $user = $this->createMockUser('test@example.com');

        $event->setUser($user);

        $this->assertSame($user, $event->getUser());
    }

    public function test_getUser_afterSettingUser_returnsSameUser(): void
    {
        $event = new BeforeLoginEvent();
        $user = $this->createMockUser('test@example.com');

        $event->setUser($user);
        $retrievedUser = $event->getUser();

        $this->assertSame($user, $retrievedUser);
        $this->assertEquals('test@example.com', $retrievedUser->getUserIdentifier());
    }

    public function test_setUser_withDifferentUsers_updatesUser(): void
    {
        $event = new BeforeLoginEvent();
        $user1 = $this->createMockUser('user1@example.com');
        $user2 = $this->createMockUser('user2@example.com');

        $event->setUser($user1);
        $this->assertSame($user1, $event->getUser());

        $event->setUser($user2);
        $this->assertSame($user2, $event->getUser());
        $this->assertNotSame($user1, $event->getUser());
    }

    public function test_event_extendsSymfonyEvent(): void
    {
        $event = new BeforeLoginEvent();

        $this->assertInstanceOf(Event::class, $event);
    }

    public function test_event_isInstanceOfBeforeLoginEvent(): void
    {
        $event = new BeforeLoginEvent();

        $this->assertInstanceOf(BeforeLoginEvent::class, $event);
    }

    public function test_setUser_withUserInterface_storesCorrectly(): void
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

    public function test_setUser_multipleTimes_returnsLatestUser(): void
    {
        $event = new BeforeLoginEvent();
        $users = [
            $this->createMockUser('user1@example.com'),
            $this->createMockUser('user2@example.com'),
            $this->createMockUser('user3@example.com'),
        ];

        foreach ($users as $user) {
            $event->setUser($user);
        }

        $this->assertSame($users[2], $event->getUser());
        $this->assertEquals('user3@example.com', $event->getUser()->getUserIdentifier());
    }

    public function test_user_hasCorrectInterface(): void
    {
        $event = new BeforeLoginEvent();
        $user = $this->createMockUser('interface@example.com');

        $event->setUser($user);
        $retrievedUser = $event->getUser();

        $this->assertInstanceOf(UserInterface::class, $retrievedUser);
        $this->assertNotEmpty($retrievedUser->getUserIdentifier());
        $this->assertContains('ROLE_USER', $retrievedUser->getRoles());
    }

    public function test_setUser_withComplexUser_preservesAllProperties(): void
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
