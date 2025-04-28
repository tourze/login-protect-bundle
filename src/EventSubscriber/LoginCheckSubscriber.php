<?php

namespace Tourze\LoginProtectBundle\EventSubscriber;

use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
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
        /** @var LoginLog $lastLog */
        $lastLog = $this->loginLogRepository->createQueryBuilder('a')
            ->where('a.identifier = :identifier')
            ->setParameter('identifier', $event->getUser()->getUserIdentifier())
            ->orderBy('a.id', Criteria::DESC)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($lastLog && $lastLog->getUnlockTime() && Carbon::now()->lessThan($lastLog->getUnlockTime())) {
            throw new LockedAuthenticationException('登录次数过多，请稍后重试');
        }
    }
}
