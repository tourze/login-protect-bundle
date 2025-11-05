<?php

namespace Tourze\LoginProtectBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\LoginProtectBundle\Entity\LoginLog;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'login_protect')]
readonly class LoginService
{
    public function __construct(
        private DoctrineService $doctrineService,
        private LoggerInterface $logger,
    ) {
    }

    public function saveLoginLog(UserInterface|string|null $user, string $action, string $sessionId = ''): void
    {
        $log = $this->createLoginLog($user, $action, $sessionId);
        if (null === $log) {
            return;
        }
        $this->doctrineService->asyncInsert($log);
    }

    public function saveLoginLogWithUnlockTime(UserInterface|string|null $user, string $action, ?\DateTimeInterface $unlockTime = null, string $sessionId = ''): void
    {
        $log = $this->createLoginLog($user, $action, $sessionId);
        if (null === $log) {
            return;
        }

        if (null !== $unlockTime) {
            $log->setUnlockTime($unlockTime);
        }

        $this->doctrineService->asyncInsert($log);
    }

    private function createLoginLog(UserInterface|string|null $user, string $action, string $sessionId = ''): ?LoginLog
    {
        $this->logger->debug('saveLoginLog', [
            'user' => $user,
            'action' => $action,
        ]);
        if (null === $user) {
            return null;
        }

        if ($user instanceof UserInterface) {
            $user = $user->getUserIdentifier();
        }

        $log = new LoginLog();
        $log->setIdentifier($user);
        $log->setAction($action);
        $log->setSessionId($sessionId);

        return $log;
    }
}
