<?php

namespace Tourze\LoginProtectBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncInsertBundle\DoctrineAsyncInsertBundle;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\DoctrineDirectInsertBundle\DoctrineDirectInsertBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\LoginProtectBundle\LoginProtectBundle;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
use Tourze\LoginProtectBundle\Service\LoginService;

class LoginServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LoginService $loginService;
    private AsyncInsertService $asyncInsertService;

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

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->loginService = static::getContainer()->get(LoginService::class);
        $this->asyncInsertService = static::getContainer()->get(AsyncInsertService::class);
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM login_attempt');
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

    public function test_saveLoginLog_withUserInterface_executesWithoutError(): void
    {
        $user = $this->createMockUser('user@example.com');

        // 测试方法执行不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($user, 'success');
    }

    public function test_saveLoginLog_withUserInterface_andSessionId_executesWithoutError(): void
    {
        $user = $this->createMockUser('user@example.com');
        $sessionId = 'test-session-123';

        // 测试方法执行不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($user, 'login', $sessionId);
    }

    public function test_saveLoginLog_withStringIdentifier_executesWithoutError(): void
    {
        $identifier = 'string-user';

        // 测试方法执行不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($identifier, 'failure');
    }

    public function test_saveLoginLog_withStringIdentifier_andSessionId_executesWithoutError(): void
    {
        $identifier = 'string-user';
        $sessionId = 'session-456';

        // 测试方法执行不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($identifier, 'logout', $sessionId);
    }

    public function test_saveLoginLog_withNullUser_executesWithoutError(): void
    {
        // 测试方法执行不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog(null, 'success');
    }

    public function test_saveLoginLog_withEmptySessionId_executesWithoutError(): void
    {
        $user = $this->createMockUser('user@example.com');

        // 测试方法执行不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($user, 'success', '');
    }

    public function test_saveLoginLog_multipleCalls_executesWithoutError(): void
    {
        $user = $this->createMockUser('user@example.com');

        // 测试多次调用不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($user, 'login', 'session1');
        $this->loginService->saveLoginLog($user, 'logout', 'session1');
        $this->loginService->saveLoginLog($user, 'login', 'session2');
    }

    public function test_saveLoginLog_withDifferentUsers_executesWithoutError(): void
    {
        $user1 = $this->createMockUser('user1@example.com');
        $user2 = $this->createMockUser('user2@example.com');

        // 测试不同用户调用不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($user1, 'success');
        $this->loginService->saveLoginLog($user2, 'failure');
    }

    public function test_saveLoginLog_withSpecialCharacters_executesWithoutError(): void
    {
        $specialIdentifier = 'user+test@example.com';

        // 测试特殊字符处理不抛异常
        $this->expectNotToPerformAssertions();
        $this->loginService->saveLoginLog($specialIdentifier, 'login');
    }

    public function test_service_isInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function test_service_hasCorrectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->loginService);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(2, $parameters);
        
        $parameterTypes = array_map(
            fn($param) => $param->getType() instanceof \ReflectionNamedType ? $param->getType()->getName() : (string) $param->getType(),
            $parameters
        );
        
        $this->assertContains(AsyncInsertService::class, $parameterTypes);
        $this->assertContains('Psr\Log\LoggerInterface', $parameterTypes);
    }

    public function test_asyncInsertService_isAvailable(): void
    {
        // 测试 AsyncInsertService 服务可用
        $this->assertInstanceOf(AsyncInsertService::class, $this->asyncInsertService);
    }
}