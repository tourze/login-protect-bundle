<?php

namespace Tourze\LoginProtectBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(LoginLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class LoginLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected static function getRepositoryClass(): string
    {
        return LoginLogRepository::class;
    }

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase 已经自动清理数据库，这里不需要手动清理
    }

    protected function getRepository(): LoginLogRepository
    {
        return self::getService(LoginLogRepository::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createTestLoginLog(array $data = []): LoginLog
    {
        $log = new LoginLog();

        /** @var string */
        $identifier = $data['identifier'] ?? 'test-' . uniqid() . '@example.com';
        $log->setIdentifier($identifier);

        /** @var string */
        $action = $data['action'] ?? 'success';
        $log->setAction($action);

        /** @var string */
        $sessionId = $data['sessionId'] ?? 'test-session-' . uniqid();
        $log->setSessionId($sessionId);

        if (isset($data['createTime'])) {
            /** @var \DateTimeInterface|null */
            $createTime = $data['createTime'];
            $log->setCreateTime($createTime);
        }

        if (isset($data['unlockTime'])) {
            /** @var \DateTimeInterface|null */
            $unlockTime = $data['unlockTime'];
            $log->setUnlockTime($unlockTime);
        }

        if (isset($data['createdFromIp'])) {
            /** @var string|null */
            $createdFromIp = $data['createdFromIp'];
            $log->setCreatedFromIp($createdFromIp);
        }

        return $log;
    }

    public function testRepositoryManagesLoginLogEntity(): void
    {
        $repository = $this->getRepository();
        $log = $this->createTestLoginLog();

        // 测试保存操作
        $repository->save($log, false);
        self::getEntityManager()->flush();

        // 验证实体已保存
        $this->assertNotNull($log->getId());
    }

    public function testLoginLogEntityCanBeCreated(): void
    {
        $log = $this->createTestLoginLog([
            'identifier' => 'test@example.com',
            'action' => 'login',
            'sessionId' => 'session123',
        ]);

        $this->assertEquals('test@example.com', $log->getIdentifier());
        $this->assertEquals('login', $log->getAction());
        $this->assertEquals('session123', $log->getSessionId());
    }

    public function testLoginLogEntityWithUnlockTimeCanBeCreated(): void
    {
        $unlockTime = new \DateTimeImmutable('+30 minutes');
        $log = $this->createTestLoginLog(['unlockTime' => $unlockTime]);

        $this->assertInstanceOf(\DateTimeInterface::class, $log->getUnlockTime());
        $this->assertEquals($unlockTime->format('Y-m-d H:i:s'), $log->getUnlockTime()->format('Y-m-d H:i:s'));
    }

    public function testSave(): void
    {
        $repository = $this->getRepository();
        $log = $this->createTestLoginLog();

        $repository->save($log);
        $this->assertNotNull($log->getId());
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();
        $log = $this->createTestLoginLog();

        $repository->save($log);
        $id = $log->getId();
        $this->assertNotNull($id);

        $repository->remove($log);
        $found = $repository->find($id);
        $this->assertNull($found);
    }

    // 健壮性测试：字段验证

    // IS NULL 查询测试 - 针对可空字段

    protected function createNewEntity(): object
    {
        return $this->createTestLoginLog([
            'identifier' => 'test-entity-' . uniqid() . '@example.com',
            'action' => 'test-action',
            'sessionId' => 'test-session-' . uniqid(),
        ]);
    }
}
