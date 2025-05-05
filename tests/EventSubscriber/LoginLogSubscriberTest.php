<?php

namespace Tourze\LoginProtectBundle\Tests\EventSubscriber;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\EventSubscriber\LoginLogSubscriber;
use Tourze\LoginProtectBundle\Service\LoginService;

class LoginLogSubscriberTest extends TestCase
{
    private LoginLogSubscriber $subscriber;
    private DoctrineService|MockObject $doctrineService;
    private LoggerInterface|MockObject $logger;
    private LoginService|MockObject $loginService;

    protected function setUp(): void
    {
        $this->doctrineService = $this->createMock(DoctrineService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->loginService = $this->createMock(LoginService::class);

        $this->subscriber = new LoginLogSubscriber(
            $this->logger,
            $this->doctrineService,
            $this->loginService
        );
    }

    /**
     * 测试登录成功事件处理
     */
    public function testOnLoginSuccess_normalCase(): void
    {
        // 创建模拟对象
        $event = $this->createMock(LoginSuccessEvent::class);
        $token = $this->createMock(TokenInterface::class);
        $passport = $this->createMock(Passport::class);
        $user = $this->createMock(UserInterface::class);

        // 设置期望
        $event->expects($this->once())
            ->method('getAuthenticatedToken')
            ->willReturn($token);

        $event->expects($this->once())
            ->method('getFirewallName')
            ->willReturn('main');

        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $passport->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->loginService->expects($this->once())
            ->method('saveLoginLog')
            ->with($user, 'success');

        // 执行测试
        $this->subscriber->onLoginSuccess($event);
    }

    /**
     * 测试使用 PostAuthenticationToken 的登录成功事件 (应该提前返回)
     */
    public function testOnLoginSuccess_withPostAuthToken(): void
    {
        // 创建模拟对象
        $event = $this->createMock(LoginSuccessEvent::class);
        $token = $this->createMock(PostAuthenticationToken::class);

        // 设置期望
        $event->expects($this->once())
            ->method('getAuthenticatedToken')
            ->willReturn($token);

        $event->expects($this->never())
            ->method('getPassport');

        $this->loginService->expects($this->never())
            ->method('saveLoginLog');

        // 执行测试
        $this->subscriber->onLoginSuccess($event);
    }

    /**
     * 测试使用开发防火墙的登录成功事件 (应该提前返回)
     */
    public function testOnLoginSuccess_withDevFirewall(): void
    {
        // 创建模拟对象
        $event = $this->createMock(LoginSuccessEvent::class);
        $token = $this->createMock(TokenInterface::class);

        // 设置期望
        $event->expects($this->once())
            ->method('getAuthenticatedToken')
            ->willReturn($token);

        $event->expects($this->once())
            ->method('getFirewallName')
            ->willReturn('dev');

        $event->expects($this->never())
            ->method('getPassport');

        $this->loginService->expects($this->never())
            ->method('saveLoginLog');

        // 执行测试
        $this->subscriber->onLoginSuccess($event);
    }

    /**
     * 测试登录失败事件处理
     */
    public function testOnLoginFailure_normalCase(): void
    {
        // 创建模拟对象
        $event = $this->createMock(LoginFailureEvent::class);
        $passport = $this->createMock(Passport::class);
        $userBadge = $this->createMock(UserBadge::class);
        $exception = $this->createMock(AuthenticationException::class);

        // 设置期望
        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $passport->expects($this->once())
            ->method('getBadge')
            ->with(UserBadge::class)
            ->willReturn($userBadge);

        $userBadge->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $event->expects($this->once())
            ->method('getException')
            ->willReturn($exception);

        $this->doctrineService->expects($this->once())
            ->method('directInsert')
            ->with($this->callback(function (LoginLog $log) {
                return $log->getIdentifier() === 'user@example.com'
                    && $log->getAction() === 'failure'
                    && $log->getUnlockTime() === null;
            }));

        // 执行测试
        $this->subscriber->onLoginFailure($event);
    }

    /**
     * 测试登录尝试次数过多的失败事件处理
     */
    public function testOnLoginFailure_withTooManyAttempts(): void
    {
        // 创建模拟对象
        $event = $this->createMock(LoginFailureEvent::class);
        $passport = $this->createMock(Passport::class);
        $userBadge = $this->createMock(UserBadge::class);
        $exception = $this->createMock(TooManyLoginAttemptsAuthenticationException::class);

        // 备份环境变量
        $originalEnv = $_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE'] ?? null;
        $_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE'] = 30;

        // 设置期望
        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $passport->expects($this->once())
            ->method('getBadge')
            ->with(UserBadge::class)
            ->willReturn($userBadge);

        $userBadge->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $event->expects($this->once())
            ->method('getException')
            ->willReturn($exception);

        $this->doctrineService->expects($this->once())
            ->method('directInsert')
            ->with($this->callback(function (LoginLog $log) {
                return $log->getIdentifier() === 'user@example.com'
                    && $log->getAction() === 'failure'
                    && $log->getUnlockTime() instanceof \DateTimeInterface;
            }));

        // 执行测试
        $this->subscriber->onLoginFailure($event);

        // 恢复环境变量
        if ($originalEnv === null) {
            unset($_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE']);
        } else {
            $_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE'] = $originalEnv;
        }
    }

    /**
     * 测试 directInsert 抛出异常时的处理
     */
    public function testOnLoginFailure_withException(): void
    {
        // 创建模拟对象
        $event = $this->createMock(LoginFailureEvent::class);
        $passport = $this->createMock(Passport::class);
        $userBadge = $this->createMock(UserBadge::class);
        $exception = $this->createMock(AuthenticationException::class);

        // 设置期望
        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $passport->expects($this->once())
            ->method('getBadge')
            ->with(UserBadge::class)
            ->willReturn($userBadge);

        $userBadge->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $event->expects($this->once())
            ->method('getException')
            ->willReturn($exception);

        $dbException = new \Exception('Database error');
        $this->doctrineService->expects($this->once())
            ->method('directInsert')
            ->willThrowException($dbException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('记录登录日志失败', ['exception' => $dbException]);

        // 执行测试
        $this->subscriber->onLoginFailure($event);
    }

    /**
     * 测试注销事件处理
     */
    public function testOnLogout_normalCase(): void
    {
        // 直接测试内部逻辑，跳过上层判断
        $this->loginService->expects($this->once())
            ->method('saveLoginLog')
            ->with($this->isInstanceOf(UserInterface::class), 'logout');

        // 创建用户和令牌
        $user = $this->createMock(UserInterface::class);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // 创建事件
        $event = $this->createStub(LogoutEvent::class);
        $event->method('getToken')->willReturn($token);

        // 执行测试
        $this->subscriber->onLogout($event);
    }

    /**
     * 测试没有用户的注销事件 (应该提前返回)
     */
    public function testOnLogout_withNullUser(): void
    {
        // 创建模拟对象
        $event = $this->createStub(LogoutEvent::class);
        $token = $this->createStub(TokenInterface::class);

        // 设置期望
        $token->method('getUser')->willReturn(null);
        $event->method('getToken')->willReturn($token);

        $this->loginService->expects($this->never())
            ->method('saveLoginLog');

        // 执行测试
        $this->subscriber->onLogout($event);
    }
}
