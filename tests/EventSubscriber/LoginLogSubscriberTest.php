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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Tourze\LoginProtectBundle\EventSubscriber\LoginLogSubscriber;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
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
    private LoginLogRepository $loginLogRepository;

    protected function onSetUp(): void
    {
        // 使用集成测试方法，从容器获取真实服务
        $this->subscriber = self::getService(LoginLogSubscriber::class);
        $this->loginService = self::getService(LoginService::class);
        $this->logger = self::getService(LoggerInterface::class);
        $this->loginLogRepository = self::getService(LoginLogRepository::class);
    }

    protected function createPassport(?string $userIdentifier = null): Passport
    {
        $userIdentifier = $userIdentifier ?? 'test@example.com';
        $user = $this->createNormalUser($userIdentifier);
        $userBadge = new UserBadge($userIdentifier, fn() => $user);

        $credentials = $this->createMock(CredentialsInterface::class);
        $credentials->method('isResolved')->willReturn(true);

        $passport = new Passport($userBadge, $credentials);

        return $passport;
    }

    protected function createToken(?UserInterface $user = null): TokenInterface
    {
        if (null === $user) {
            $user = $this->createNormalUser('test@example.com');
        }

        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    protected function createAuthenticator(): AuthenticatorInterface
    {
        return $this->createMock(AuthenticatorInterface::class);
    }

    protected function createRequest(): Request
    {
        return Request::create('/login', 'POST');
    }

    public function testOnLoginFailureWithRegularException(): void
    {
        $userIdentifier = 'test@example.com';
        $passport = $this->createPassport($userIdentifier);
        $exception = new BadCredentialsException('Invalid credentials');
        $authenticator = $this->createAuthenticator();
        $request = $this->createRequest();
        $response = null;
        $firewallName = 'main';

        $event = new LoginFailureEvent($exception, $authenticator, $request, $response, $firewallName, $passport);

        // 执行测试
        $this->subscriber->onLoginFailure($event);

        // 验证登录日志已记录
        $logs = $this->loginLogRepository->findBy(['identifier' => $userIdentifier, 'action' => 'failure']);
        $this->assertCount(1, $logs);
        $this->assertEquals($userIdentifier, $logs[0]->getIdentifier());
        $this->assertEquals('failure', $logs[0]->getAction());
        $this->assertNull($logs[0]->getUnlockTime());
    }

    public function testOnLoginFailureWithTooManyAttemptsException(): void
    {
        $_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE'] = '45';

        $userIdentifier = 'test@example.com';
        $passport = $this->createPassport($userIdentifier);
        $exception = new TooManyLoginAttemptsAuthenticationException();
        $authenticator = $this->createAuthenticator();
        $request = $this->createRequest();
        $response = null;
        $firewallName = 'main';

        $event = new LoginFailureEvent($exception, $authenticator, $request, $response, $firewallName, $passport);

        // 执行测试
        $this->subscriber->onLoginFailure($event);

        // 验证登录日志已记录且包含解锁时间
        $logs = $this->loginLogRepository->findBy(['identifier' => $userIdentifier, 'action' => 'failure']);
        $this->assertCount(1, $logs);
        $this->assertEquals($userIdentifier, $logs[0]->getIdentifier());
        $this->assertEquals('failure', $logs[0]->getAction());
        $this->assertNotNull($logs[0]->getUnlockTime());
        $this->assertInstanceOf(\DateTimeInterface::class, $logs[0]->getUnlockTime());
    }

    public function testOnLoginFailureWithNullPassport(): void
    {
        $exception = new BadCredentialsException('Invalid credentials');
        $authenticator = $this->createAuthenticator();
        $request = $this->createRequest();
        $response = null;
        $firewallName = 'main';
        $passport = null;

        $event = new LoginFailureEvent($exception, $authenticator, $request, $response, $firewallName, $passport);

        // 执行测试
        $this->subscriber->onLoginFailure($event);

        // 验证登录日志已记录（使用空字符串作为标识符）
        $logs = $this->loginLogRepository->findBy(['identifier' => '', 'action' => 'failure']);
        $this->assertCount(1, $logs);
        $this->assertEquals('', $logs[0]->getIdentifier());
        $this->assertEquals('failure', $logs[0]->getAction());
    }

    public function testOnLoginSuccessWithRegularToken(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = $this->createToken($user);
        $firewallName = 'main';
        $authenticator = $this->createAuthenticator();
        $request = $this->createRequest();
        $response = null;
        $previousToken = null;

        $event = new LoginSuccessEvent($authenticator, $passport, $token, $request, $response, $firewallName, $previousToken);

        // 执行测试
        $this->subscriber->onLoginSuccess($event);

        // 验证登录日志已记录
        $logs = $this->loginLogRepository->findBy(['action' => 'success']);
        $this->assertNotEmpty($logs);
        $foundLog = null;
        foreach ($logs as $log) {
            if ($log->getIdentifier() === 'test@example.com') {
                $foundLog = $log;
                break;
            }
        }
        $this->assertNotNull($foundLog);
        $this->assertEquals('success', $foundLog->getAction());
    }

    public function testOnLoginSuccessWithPostAuthenticationToken(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());
        $firewallName = 'main';
        $authenticator = $this->createAuthenticator();
        $request = $this->createRequest();
        $response = null;
        $previousToken = null;

        $event = new LoginSuccessEvent($authenticator, $passport, $token, $request, $response, $firewallName, $previousToken);

        // 执行测试
        $this->subscriber->onLoginSuccess($event);

        // PostAuthenticationToken 不应该记录日志
        $logs = $this->loginLogRepository->findBy(['action' => 'success']);
        $foundLog = null;
        foreach ($logs as $log) {
            if ($log->getIdentifier() === 'test@example.com') {
                $foundLog = $log;
                break;
            }
        }
        $this->assertNull($foundLog, 'PostAuthenticationToken should not log success');
    }

    public function testOnLoginSuccessWithDevFirewall(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = $this->createToken($user);
        $firewallName = 'dev';
        $authenticator = $this->createAuthenticator();
        $request = $this->createRequest();
        $response = null;
        $previousToken = null;

        $event = new LoginSuccessEvent($authenticator, $passport, $token, $request, $response, $firewallName, $previousToken);

        // 执行测试
        $this->subscriber->onLoginSuccess($event);

        // dev 防火墙不应该记录日志
        $logs = $this->loginLogRepository->findBy(['action' => 'success']);
        $foundLog = null;
        foreach ($logs as $log) {
            if ($log->getIdentifier() === 'test@example.com') {
                $foundLog = $log;
                break;
            }
        }
        $this->assertNull($foundLog, 'Dev firewall should not log success');
    }

    public function testOnLoginSuccessWithSafeDevFirewall(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $passport = $this->createPassport('test@example.com');
        $token = $this->createToken($user);
        $firewallName = 'safe_dev';
        $authenticator = $this->createAuthenticator();
        $request = $this->createRequest();
        $response = null;
        $previousToken = null;

        $event = new LoginSuccessEvent($authenticator, $passport, $token, $request, $response, $firewallName, $previousToken);

        // 执行测试
        $this->subscriber->onLoginSuccess($event);

        // safe_dev 防火墙不应该记录日志
        $logs = $this->loginLogRepository->findBy(['action' => 'success']);
        $foundLog = null;
        foreach ($logs as $log) {
            if ($log->getIdentifier() === 'test@example.com') {
                $foundLog = $log;
                break;
            }
        }
        $this->assertNull($foundLog, 'Safe dev firewall should not log success');
    }

    public function testOnLogoutWithUser(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $token = $this->createToken($user);
        $request = $this->createRequest();

        $event = new LogoutEvent($request, $token);

        // 执行测试
        $this->subscriber->onLogout($event);

        // 验证登出日志已记录
        $logs = $this->loginLogRepository->findBy(['action' => 'logout']);
        $this->assertNotEmpty($logs);
        $foundLog = null;
        foreach ($logs as $log) {
            if ($log->getIdentifier() === 'test@example.com') {
                $foundLog = $log;
                break;
            }
        }
        $this->assertNotNull($foundLog);
        $this->assertEquals('logout', $foundLog->getAction());
    }

    public function testOnLogoutWithNullToken(): void
    {
        $request = $this->createRequest();
        $event = new LogoutEvent($request, null);

        // 执行测试
        $this->subscriber->onLogout($event);

        // 空token不应该记录日志
        $logs = $this->loginLogRepository->findBy(['action' => 'logout']);
        // 只应该有之前测试产生的日志
        $filteredLogs = array_filter($logs, function($log) {
            return $log->getIdentifier() === null;
        });
        $this->assertEmpty($filteredLogs, 'Null token should not log logout');
    }

    public function testOnLogoutWithNullUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $request = $this->createRequest();

        $event = new LogoutEvent($request, $token);

        // 执行测试
        $this->subscriber->onLogout($event);

        // 空用户不应该记录日志
        $logs = $this->loginLogRepository->findBy(['action' => 'logout']);
        $filteredLogs = array_filter($logs, function($log) {
            return $log->getIdentifier() === null;
        });
        $this->assertEmpty($filteredLogs, 'Null user should not log logout');
    }
}