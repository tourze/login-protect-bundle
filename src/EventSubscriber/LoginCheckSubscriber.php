<?php

namespace Tourze\LoginProtectBundle\EventSubscriber;

use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\Order;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\Event\BeforeLoginEvent;
use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;

class LoginCheckSubscriber
{
    public function __construct(private readonly LoginLogRepository $loginLogRepository)
    {
    }

    #[AsEventListener]
    public function checkLoginTime(BeforeLoginEvent $event): void
    {
        $lastLog = $this->loginLogRepository->createQueryBuilder('a')
            ->where('a.identifier = :identifier')
            ->setParameter('identifier', $event->getUser()->getUserIdentifier())
            ->orderBy('a.id', Order::Descending->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        if ($lastLog instanceof LoginLog && null !== $lastLog->getUnlockTime() && CarbonImmutable::now()->lessThan($lastLog->getUnlockTime())) {
            throw new LockedAuthenticationException('登录次数过多，请稍后重试');
        }
    }
}
