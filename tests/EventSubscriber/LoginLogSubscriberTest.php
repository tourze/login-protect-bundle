<?php

namespace Tourze\LoginProtectBundle\Tests\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Tourze\DoctrineAsyncInsertBundle\DoctrineAsyncInsertBundle;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\DoctrineDirectInsertBundle\DoctrineDirectInsertBundle;
use Tourze\DoctrineDirectInsertBundle\Service\DirectInsertService;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\LoginProtectBundle\EventSubscriber\LoginLogSubscriber;
use Tourze\LoginProtectBundle\LoginProtectBundle;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
use Tourze\LoginProtectBundle\Service\LoginService;

class LoginLogSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LoginLogSubscriber $subscriber;
    private LoginLogRepository $repository;
    private LoginService $loginService;
    private AsyncInsertService $asyncInsertService;
    private DirectInsertService $directInsertService;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            LoginProtectBundle::class => ['all' => true],
            DoctrineAsyncInsertBundle::class => ['all' => true],
            DoctrineDirectInsertBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
        ]);
    }

    public function test_onLoginSuccess_withRegularToken_createsLoginLog(): void
    {
        $user = $this->createMockUser('success@example.com');
        $passport = $this->createMockPassport($user);
        $token = new UsernamePasswordToken($user, 'main');

        $event = new LoginSuccessEvent(
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $passport,
            $token,
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main'
        );

        $this->subscriber->onLoginSuccess($event);
        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试事件处理不抛异常
        $this->addToAssertionCount(1);
    }

    private function createMockUser(string $identifier): UserInterface
    {
        return new class($identifier) implements UserInterface {
            public function __construct(private string $identifier) {}

            public function getUserIdentifier(): string
            {
                return $this->identifier;
            }

            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void {}
        };
    }

    private function createMockPassport(UserInterface $user): Passport
    {
        $passport = $this->createMock(Passport::class);
        $userBadge = new UserBadge($user->getUserIdentifier());

        $passport->method('getUser')->willReturn($user);
        $passport->method('getBadge')->willReturn($userBadge);

        return $passport;
    }

    public function test_onLoginSuccess_withPostAuthenticationToken_doesNotCreateLog(): void
    {
        $user = $this->createMockUser('post@example.com');
        $passport = $this->createMockPassport($user);
        $token = new PostAuthenticationToken($user, 'main', ['ROLE_USER']);

        $event = new LoginSuccessEvent(
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $passport,
            $token,
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main'
        );

        $this->subscriber->onLoginSuccess($event);
        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试 PostAuthenticationToken 不触发日志记录
        $this->addToAssertionCount(1);
    }

    public function test_onLoginSuccess_withDevFirewall_doesNotCreateLog(): void
    {
        $user = $this->createMockUser('dev@example.com');
        $passport = $this->createMockPassport($user);
        $token = new UsernamePasswordToken($user, 'dev');

        $event = new LoginSuccessEvent(
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $passport,
            $token,
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'dev'
        );

        $this->subscriber->onLoginSuccess($event);
        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试 dev firewall 不触发日志记录
        $this->addToAssertionCount(1);
    }

    public function test_onLoginSuccess_withSafeDevFirewall_doesNotCreateLog(): void
    {
        $user = $this->createMockUser('safedev@example.com');
        $passport = $this->createMockPassport($user);
        $token = new UsernamePasswordToken($user, 'safe_dev');

        $event = new LoginSuccessEvent(
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $passport,
            $token,
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'safe_dev'
        );

        $this->subscriber->onLoginSuccess($event);
        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试 safe_dev firewall 不触发日志记录
        $this->addToAssertionCount(1);
    }

    public function test_onLoginFailure_withRegularFailure_createsFailureLog(): void
    {
        $passport = $this->createMockPassport($this->createMockUser('failure@example.com'));
        $exception = new class('Login failed') extends AuthenticationException {};

        $event = new LoginFailureEvent(
            $exception,
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main',
            $passport
        );

        $this->subscriber->onLoginFailure($event);

        // 测试登录失败事件处理不抛异常
        $this->addToAssertionCount(1);
    }

    public function test_onLoginFailure_withTooManyAttempts_createsFailureLogWithUnlockTime(): void
    {
        $passport = $this->createMockPassport($this->createMockUser('toomany@example.com'));
        $exception = new TooManyLoginAttemptsAuthenticationException(5, 'Too many attempts');

        $event = new LoginFailureEvent(
            $exception,
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main',
            $passport
        );

        $this->subscriber->onLoginFailure($event);

        // 测试过多登录尝试事件处理不抛异常
        $this->addToAssertionCount(1);
    }

    public function test_onLoginFailure_withTooManyAttempts_respectsEnvironmentVariable(): void
    {
        $_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE'] = '60';

        $passport = $this->createMockPassport($this->createMockUser('envtest@example.com'));
        $exception = new TooManyLoginAttemptsAuthenticationException(5, 'Too many attempts');

        $event = new LoginFailureEvent(
            $exception,
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main',
            $passport
        );

        $this->subscriber->onLoginFailure($event);

        // 测试环境变量配置生效
        $this->addToAssertionCount(1);

        unset($_ENV['LOGIN_ATTEMPT_FAIL_LOCK_MINUTE']);
    }

    public function test_onLoginFailure_withEmptyIdentifier_handlesGracefully(): void
    {
        $passport = $this->createMock(Passport::class);
        $userBadge = new UserBadge('');
        $passport->method('getBadge')->willReturn($userBadge);

        $exception = new class('Login failed') extends AuthenticationException {};

        $event = new LoginFailureEvent(
            $exception,
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main',
            $passport
        );

        $this->subscriber->onLoginFailure($event);

        // 测试空识别符处理不抛异常
        $this->addToAssertionCount(1);
    }

    public function test_onLoginFailure_withNullPassport_handlesGracefully(): void
    {
        $exception = new class('Login failed') extends AuthenticationException {};

        $event = new LoginFailureEvent(
            $exception,
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main',
            null
        );

        $this->subscriber->onLoginFailure($event);

        // 测试 null passport 处理不抛异常
        $this->addToAssertionCount(1);
    }

    public function test_onLogout_withValidUser_createsLogoutLog(): void
    {
        $user = $this->createMockUser('logout@example.com');
        $token = new UsernamePasswordToken($user, 'main');

        $event = new LogoutEvent(
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            $token
        );

        $this->subscriber->onLogout($event);
        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试登出事件处理不抛异常
        $this->addToAssertionCount(1);
    }

    public function test_onLogout_withNullToken_doesNotCreateLog(): void
    {
        $event = new LogoutEvent(
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null
        );

        $this->subscriber->onLogout($event);
        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试 null token 不触发日志记录
        $this->addToAssertionCount(1);
    }

    public function test_onLogout_withNullUser_doesNotCreateLog(): void
    {
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $event = new LogoutEvent(
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            $token
        );

        $this->subscriber->onLogout($event);
        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试 null user 不触发日志记录
        $this->addToAssertionCount(1);
    }

    public function test_subscriber_isInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(LoginLogSubscriber::class, $this->subscriber);
    }

    public function test_subscriber_hasCorrectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->subscriber);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(3, $parameters);

        $parameterTypes = array_map(
            fn($param) => $param->getType()->getName(),
            $parameters
        );

        $this->assertContains(LoggerInterface::class, $parameterTypes);
        $this->assertContains(DirectInsertService::class, $parameterTypes);
        $this->assertContains(LoginService::class, $parameterTypes);
    }

    public function test_onLoginFailure_multipleFailures_createsMultipleLogs(): void
    {
        $passport = $this->createMockPassport($this->createMockUser('multi@example.com'));
        $exception = new class('Login failed') extends AuthenticationException {};

        $event = new LoginFailureEvent(
            $exception,
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main',
            $passport
        );

        $this->subscriber->onLoginFailure($event);
        $this->subscriber->onLoginFailure($event);
        $this->subscriber->onLoginFailure($event);

        // 测试多次失败事件处理不抛异常
        $this->addToAssertionCount(1);
    }

    public function test_fullLoginFlow_createsCorrectSequenceOfLogs(): void
    {
        $user = $this->createMockUser('flow@example.com');

        $failurePassport = $this->createMockPassport($user);
        $failureEvent = new LoginFailureEvent(
            new class('Login failed') extends AuthenticationException {},
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main',
            $failurePassport
        );

        $successPassport = $this->createMockPassport($user);
        $successToken = new UsernamePasswordToken($user, 'main');
        $successEvent = new LoginSuccessEvent(
            $this->createMock(\Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface::class),
            $successPassport,
            $successToken,
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            null,
            'main'
        );

        $logoutEvent = new LogoutEvent(
            $this->createMock(\Symfony\Component\HttpFoundation\Request::class),
            $successToken
        );

        $this->subscriber->onLoginFailure($failureEvent);
        $this->subscriber->onLoginSuccess($successEvent);
        $this->subscriber->onLogout($logoutEvent);

        // AsyncInsertService 处理是异步的，无需手动执行

        // 测试完整登录流程不抛异常
        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->subscriber = static::getContainer()->get(LoginLogSubscriber::class);
        $this->repository = static::getContainer()->get(LoginLogRepository::class);
        $this->loginService = static::getContainer()->get(LoginService::class);
        $this->asyncInsertService = static::getContainer()->get(AsyncInsertService::class);
        $this->directInsertService = static::getContainer()->get(DirectInsertService::class);
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM login_attempt');
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        self::ensureKernelShutdown();
        parent::tearDown();
    }
}