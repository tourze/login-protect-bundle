<?php

namespace Tourze\LoginProtectBundle\Tests\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Tourze\LoginProtectBundle\EventSubscriber\LoginLogSubscriber;
use Tourze\LoginProtectBundle\Service\LoginService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(LoginLogSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class LoginLogSubscriberTest extends AbstractEventSubscriberTestCase
{
    private LoginService $loginService;

    private LoggerInterface $logger;

    private LoginLogSubscriber $subscriber;

    protected function onSetUp(): void
    {
        $this->loginService = $this->createMock(LoginService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass - 需要使用Mock依赖验证行为
        $this->subscriber = new LoginLogSubscriber($this->logger, $this->loginService);
    }

    /**
     * 创建测试用户替身
     */
    private function createUserStub(string $identifier = 'test@example.com'): UserInterface
    {
        /** @phpstan-ignore-next-line staticMethod.dynamicCall */
        $user = $this->createStub(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($identifier);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        return $user;
    }

    protected function createPassport(?string $userIdentifier = null): Passport
    {
        $userBadge = new UserBadge($userIdentifier ?? 'test@example.com');
        $user = $this->createUserStub($userIdentifier ?? 'test@example.com');

        $credentials = $this->createMock(CredentialsInterface::class);
        $credentials->method('isResolved')->willReturn(true);

        $passport = new Passport($userBadge, $credentials);
        $userBadge->setUserLoader(fn () => $user);

        return $passport;
    }

    protected function createToken(?UserInterface $user = null): TokenInterface
    {
        $user ??= $this->createUserStub();

        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    public function testOnLoginFailureWithRegularException(): void
    {
        $userIdentifier = 'test@example.com';
        $passport = $this->createPassport($userIdentifier);
        $exception = new BadCredentialsException('Invalid credentials');

        $event = $this->createMock(LoginFailureEvent::class);
        $event->method('getPassport')->willReturn($passport);
        $event->method('getException')->willReturn($exception);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('登录失败，记录登录日志', self::anything())
        ;

        $this->loginService->expects($this->once())
            ->method('saveLoginLogWithUnlockTime')
            ->with($userIdentifier, 'failure', null)
        ;

        $this->subscriber->onLoginFailure($event);
    }

    public function testOnLoginFailureWithTooManyAttemptsException(): void
    {
        $_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE'] = '45';

        $userIdentifier = 'test@example.com';
        $passport = $this->createPassport($userIdentifier);
        $exception = new TooManyLoginAttemptsAuthenticationException();

        $event = $this->createMock(LoginFailureEvent::class);
        $event->method('getPassport')->willReturn($passport);
        $event->method('getException')->willReturn($exception);

        $this->loginService->expects($this->once())
            ->method('saveLoginLogWithUnlockTime')
            ->with(
                $userIdentifier,
                'failure',
                self::isInstanceOf(\DateTimeInterface::class)
            )
        ;

        $this->subscriber->onLoginFailure($event);
    }

    public function testOnLoginFailureWithNullPassport(): void
    {
        $exception = new BadCredentialsException('Invalid credentials');

        $event = $this->createMock(LoginFailureEvent::class);
        $event->method('getPassport')->willReturn(null);
        $event->method('getException')->willReturn($exception);

        $this->loginService->expects($this->once())
            ->method('saveLoginLogWithUnlockTime')
            ->with('', 'failure', null)
        ;

        $this->subscriber->onLoginFailure($event);
    }

    public function testOnLoginFailureWithServiceException(): void
    {
        $userIdentifier = 'test@example.com';
        $passport = $this->createPassport($userIdentifier);
        $exception = new BadCredentialsException('Invalid credentials');
        $serviceException = new \RuntimeException('Database error');

        $event = $this->createMock(LoginFailureEvent::class);
        $event->method('getPassport')->willReturn($passport);
        $event->method('getException')->willReturn($exception);

        $this->loginService->expects($this->once())
            ->method('saveLoginLogWithUnlockTime')
            ->willThrowException($serviceException)
        ;

        $this->logger->expects($this->once())
            ->method('error')
            ->with('记录登录日志失败', ['exception' => $serviceException])
        ;

        $this->subscriber->onLoginFailure($event);
    }

    public function testOnLoginSuccessWithRegularToken(): void
    {
        $user = $this->createUserStub('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = $this->createToken($user);
        $firewallName = 'main';

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getAuthenticatedToken')->willReturn($token);
        $event->method('getPassport')->willReturn($passport);
        $event->method('getFirewallName')->willReturn($firewallName);

        $this->loginService->expects($this->once())
            ->method('saveLoginLog')
            ->with($passport->getUser(), 'success')
        ;

        $this->subscriber->onLoginSuccess($event);
    }

    public function testOnLoginSuccessWithPostAuthenticationToken(): void
    {
        $user = $this->createUserStub('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());
        $firewallName = 'main';

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getAuthenticatedToken')->willReturn($token);
        $event->method('getPassport')->willReturn($passport);
        $event->method('getFirewallName')->willReturn($firewallName);

        $this->loginService->expects($this->never())
            ->method('saveLoginLog')
        ;

        $this->subscriber->onLoginSuccess($event);
    }

    public function testOnLoginSuccessWithDevFirewall(): void
    {
        $user = $this->createUserStub('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = $this->createToken($user);
        $firewallName = 'dev';

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getAuthenticatedToken')->willReturn($token);
        $event->method('getPassport')->willReturn($passport);
        $event->method('getFirewallName')->willReturn($firewallName);

        $this->loginService->expects($this->never())
            ->method('saveLoginLog')
        ;

        $this->subscriber->onLoginSuccess($event);
    }

    public function testOnLoginSuccessWithSafeDevFirewall(): void
    {
        $user = $this->createUserStub('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = $this->createToken($user);
        $firewallName = 'safe_dev';

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getAuthenticatedToken')->willReturn($token);
        $event->method('getPassport')->willReturn($passport);
        $event->method('getFirewallName')->willReturn($firewallName);

        $this->loginService->expects($this->never())
            ->method('saveLoginLog')
        ;

        $this->subscriber->onLoginSuccess($event);
    }

    public function testOnLogoutWithUser(): void
    {
        $user = $this->createUserStub('test@example.com');
        $token = $this->createToken($user);

        $event = $this->createMock(LogoutEvent::class);
        $event->method('getToken')->willReturn($token);

        $this->loginService->expects($this->once())
            ->method('saveLoginLog')
            ->with($user, 'logout')
        ;

        $this->subscriber->onLogout($event);
    }

    public function testOnLogoutWithNullToken(): void
    {
        $event = $this->createMock(LogoutEvent::class);
        $event->method('getToken')->willReturn(null);

        $this->loginService->expects($this->never())
            ->method('saveLoginLog')
        ;

        $this->subscriber->onLogout($event);
    }

    public function testOnLogoutWithNullUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $event = $this->createMock(LogoutEvent::class);
        $event->method('getToken')->willReturn($token);

        $this->loginService->expects($this->never())
            ->method('saveLoginLog')
        ;

        $this->subscriber->onLogout($event);
    }
}
