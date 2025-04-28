<?php

namespace Tourze\LoginProtectBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\LoginProtectBundle\Entity\LoginLog;

class LoginService
{
    public function __construct(
        private readonly DoctrineService $doctrineService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function saveLoginLog(UserInterface|string|null $user, string $action, string $sessionId = ''): void
    {
        $this->logger->debug('saveLoginLog', [
            'user' => $user,
            'action' => $action,
        ]);
        if (null === $user) {
            return;
        }

        if ($user instanceof UserInterface) {
            $user = $user->getUserIdentifier();
        }

        $log = new LoginLog();
        $log->setIdentifier($user);
        $log->setAction($action);
        $log->setSessionId($sessionId);
        $this->doctrineService->asyncInsert($log);
    }
}
