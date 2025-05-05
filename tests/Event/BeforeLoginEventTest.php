<?php

namespace Tourze\LoginProtectBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;

class BeforeLoginEventTest extends TestCase
{
    /**
     * 测试事件的 getter 和 setter 方法
     */
    public function testGetterAndSetter(): void
    {
        $event = new BeforeLoginEvent();
        $user = $this->createMock(UserInterface::class);

        $event->setUser($user);
        $this->assertSame($user, $event->getUser());
    }

    /**
     * 测试事件继承自 Symfony\Contracts\EventDispatcher\Event
     */
    public function testEventInheritance(): void
    {
        $event = new BeforeLoginEvent();
        $this->assertInstanceOf(\Symfony\Contracts\EventDispatcher\Event::class, $event);
    }
}
