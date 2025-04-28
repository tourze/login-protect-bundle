<?php

namespace Tourze\LoginProtectBundle\EventSubscriber;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\Service\LoginService;

/**
 * 登录安全相关的处理
 */
class LoginLogSubscriber
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DoctrineService $doctrineService,
        private readonly LoginService $loginService,
    ) {
    }

    #[AsEventListener]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if ($event->getAuthenticatedToken() instanceof PostAuthenticationToken) {
            return;
        }
        if (in_array($event->getFirewallName(), ['safe_dev', 'dev'])) {
            return;
        }
        $this->loginService->saveLoginLog($event->getPassport()->getUser(), 'success');
    }

    /**
     * 登录失败时，我们做一些日志记录
     */
    #[AsEventListener]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->logger->debug('登录失败，记录登录日志', [
            'event' => $event,
        ]);

        $log = new LoginLog();

        $identifier = '';
        $passport = $event->getPassport();
        if ($passport) {
            $identifier = $passport->getBadge(UserBadge::class)?->getUserIdentifier();
        }
        $log->setIdentifier($identifier);
        $log->setAction('failure');

        // 在 config/packages/security.yaml 中进行配置
        // 如果登录失败超出一定次数，我们就阻止他继续登录
        // 普通用户登录失败 5 次后，须自动锁定此账户，锁定时长建议至少为 30 分钟。
        $e = $event->getException();
        if ($e instanceof TooManyLoginAttemptsAuthenticationException) {
            $log->setUnlockTime(Carbon::now()->addMinutes($_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE'] ?? 30));
        }

        try {
            $this->doctrineService->directInsert($log);
        } catch (\Throwable $exception) {
            $this->logger->error('记录登录日志失败', [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * 退出时也记录一次
     */
    #[AsEventListener]
    public function onLogout(LogoutEvent $event): void
    {
        if (!$event->getToken()?->getUser()) {
            return;
        }
        $this->loginService->saveLoginLog($event->getToken()->getUser(), 'logout');
    }
}
