<?php

namespace Tourze\LoginProtectBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 登录前触发事件
 */
class BeforeLoginEvent extends Event
{
    private UserInterface $user;

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }
}
