<?php

namespace Tourze\LoginProtectBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\LoginProtectBundle\Service\LoginService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(LoginService::class)]
#[RunTestsInSeparateProcesses]
final class LoginServiceTest extends AbstractIntegrationTestCase
{
    private LoginService $loginService;

    protected function onSetUp(): void
    {
        $this->loginService = self::getService(LoginService::class);
    }

    public function testSaveLoginLogWithUserInterfaceExecutesWithoutError(): void
    {
        $user = $this->createNormalUser('user@example.com');

        $this->loginService->saveLoginLog($user, 'success');

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogWithUserInterfaceAndSessionIdExecutesWithoutError(): void
    {
        $user = $this->createNormalUser('user@example.com');
        $sessionId = 'test-session-123';

        $this->loginService->saveLoginLog($user, 'login', $sessionId);

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogWithStringIdentifierExecutesWithoutError(): void
    {
        $identifier = 'string-user';

        $this->loginService->saveLoginLog($identifier, 'failure');

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogWithStringIdentifierAndSessionIdExecutesWithoutError(): void
    {
        $identifier = 'string-user';
        $sessionId = 'session-456';

        $this->loginService->saveLoginLog($identifier, 'logout', $sessionId);

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogWithNullUserExecutesWithoutError(): void
    {
        $this->loginService->saveLoginLog(null, 'success');

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogWithEmptySessionIdExecutesWithoutError(): void
    {
        $user = $this->createNormalUser('user@example.com');

        $this->loginService->saveLoginLog($user, 'success', '');

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogMultipleCallsExecutesWithoutError(): void
    {
        $user = $this->createNormalUser('user@example.com');

        $this->loginService->saveLoginLog($user, 'login', 'session1');
        $this->loginService->saveLoginLog($user, 'logout', 'session1');
        $this->loginService->saveLoginLog($user, 'login', 'session2');

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogWithDifferentUsersExecutesWithoutError(): void
    {
        $user1 = $this->createNormalUser('user1@example.com');
        $user2 = $this->createNormalUser('user2@example.com');

        $this->loginService->saveLoginLog($user1, 'success');
        $this->loginService->saveLoginLog($user2, 'failure');

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testSaveLoginLogWithSpecialCharactersExecutesWithoutError(): void
    {
        $specialIdentifier = 'user+test@example.com';

        $this->loginService->saveLoginLog($specialIdentifier, 'login');

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testServiceIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }

    public function testServiceHasCorrectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->loginService);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor?->getParameters() ?? [];

        $this->assertCount(2, $parameters);

        $parameterTypes = array_map(
            fn ($param) => $param->getType() instanceof \ReflectionNamedType ? $param->getType()->getName() : (string) $param->getType(),
            $parameters
        );

        $this->assertContains('Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService', $parameterTypes);
        $this->assertContains('Psr\Log\LoggerInterface', $parameterTypes);
    }

    public function testSaveLoginLogWithUnlockTimeExecutesWithoutError(): void
    {
        $user = $this->createNormalUser('user@example.com');
        $unlockTime = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->loginService->saveLoginLogWithUnlockTime($user, 'failure', $unlockTime);

        $this->assertInstanceOf(LoginService::class, $this->loginService);
    }
}
