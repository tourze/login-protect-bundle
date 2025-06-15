<?php

namespace Tourze\LoginProtectBundle\Tests\Repository;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\DoctrineAsyncInsertBundle\DoctrineAsyncInsertBundle;
use Tourze\DoctrineDirectInsertBundle\DoctrineDirectInsertBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\LoginProtectBundle;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;

class LoginLogRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LoginLogRepository $repository;

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
        $this->repository = static::getContainer()->get(LoginLogRepository::class);
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

    private function createTestLoginLog(array $data = []): LoginLog
    {
        $log = new LoginLog();
        $log->setIdentifier($data['identifier'] ?? 'test@example.com');
        $log->setAction($data['action'] ?? 'success');
        $log->setSessionId($data['sessionId'] ?? 'test-session');
        
        if (isset($data['createTime'])) {
            $log->setCreateTime($data['createTime']);
        }
        
        if (isset($data['unlockTime'])) {
            $log->setUnlockTime($data['unlockTime']);
        }
        
        if (isset($data['createdFromIp'])) {
            $log->setCreatedFromIp($data['createdFromIp']);
        }

        return $log;
    }

    public function test_find_withValidId_returnsEntity(): void
    {
        $log = $this->createTestLoginLog();
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $result = $this->repository->find($log->getId());

        $this->assertNotNull($result);
        $this->assertInstanceOf(LoginLog::class, $result);
        $this->assertEquals($log->getId(), $result->getId());
    }

    public function test_find_withInvalidId_returnsNull(): void
    {
        $result = $this->repository->find('999999999');

        $this->assertNull($result);
    }

    public function test_findAll_withNoData_returnsEmptyArray(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_findAll_withData_returnsAllEntities(): void
    {
        $log1 = $this->createTestLoginLog(['identifier' => 'user1@example.com']);
        $log2 = $this->createTestLoginLog(['identifier' => 'user2@example.com']);
        
        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->flush();

        $result = $this->repository->findAll();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(LoginLog::class, $result);
    }

    public function test_findBy_withIdentifier_returnsMatchingEntities(): void
    {
        $log1 = $this->createTestLoginLog(['identifier' => 'user1@example.com']);
        $log2 = $this->createTestLoginLog(['identifier' => 'user2@example.com']);
        $log3 = $this->createTestLoginLog(['identifier' => 'user1@example.com', 'action' => 'failure']);
        
        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->persist($log3);
        $this->entityManager->flush();

        $result = $this->repository->findBy(['identifier' => 'user1@example.com']);

        $this->assertCount(2, $result);
        foreach ($result as $log) {
            $this->assertEquals('user1@example.com', $log->getIdentifier());
        }
    }

    public function test_findBy_withAction_returnsMatchingEntities(): void
    {
        $log1 = $this->createTestLoginLog(['action' => 'success']);
        $log2 = $this->createTestLoginLog(['action' => 'failure']);
        $log3 = $this->createTestLoginLog(['action' => 'success']);
        
        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->persist($log3);
        $this->entityManager->flush();

        $result = $this->repository->findBy(['action' => 'success']);

        $this->assertCount(2, $result);
        foreach ($result as $log) {
            $this->assertEquals('success', $log->getAction());
        }
    }

    public function test_findBy_withOrderBy_returnsOrderedEntities(): void
    {
        $log1 = $this->createTestLoginLog(['identifier' => 'aaa@example.com']);
        $log2 = $this->createTestLoginLog(['identifier' => 'zzz@example.com']);
        $log3 = $this->createTestLoginLog(['identifier' => 'mmm@example.com']);
        
        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->persist($log3);
        $this->entityManager->flush();

        $result = $this->repository->findBy([], ['identifier' => 'ASC']);

        $this->assertCount(3, $result);
        $this->assertEquals('aaa@example.com', $result[0]->getIdentifier());
        $this->assertEquals('mmm@example.com', $result[1]->getIdentifier());
        $this->assertEquals('zzz@example.com', $result[2]->getIdentifier());
    }

    public function test_findBy_withLimit_returnsLimitedEntities(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $log = $this->createTestLoginLog(['identifier' => "user{$i}@example.com"]);
            $this->entityManager->persist($log);
        }
        $this->entityManager->flush();

        $result = $this->repository->findBy([], null, 3);

        $this->assertCount(3, $result);
    }

    public function test_findBy_withOffset_returnsOffsetEntities(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $log = $this->createTestLoginLog(['identifier' => "user{$i}@example.com"]);
            $this->entityManager->persist($log);
        }
        $this->entityManager->flush();

        $result = $this->repository->findBy([], ['id' => 'ASC'], 3, 2);

        $this->assertCount(3, $result);
    }

    public function test_findOneBy_withIdentifier_returnsFirstMatch(): void
    {
        $log1 = $this->createTestLoginLog(['identifier' => 'user@example.com', 'action' => 'success']);
        $log2 = $this->createTestLoginLog(['identifier' => 'user@example.com', 'action' => 'failure']);
        
        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->flush();

        $result = $this->repository->findOneBy(['identifier' => 'user@example.com']);

        $this->assertNotNull($result);
        $this->assertInstanceOf(LoginLog::class, $result);
        $this->assertEquals('user@example.com', $result->getIdentifier());
    }

    public function test_findOneBy_withNonExistentCriteria_returnsNull(): void
    {
        $log = $this->createTestLoginLog();
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $result = $this->repository->findOneBy(['identifier' => 'nonexistent@example.com']);

        $this->assertNull($result);
    }

    public function test_findOneBy_withOrderBy_returnsOrderedResult(): void
    {
        $createTime1 = new DateTime('2023-01-01 10:00:00');
        $createTime2 = new DateTime('2023-01-01 12:00:00');
        
        $log1 = $this->createTestLoginLog(['identifier' => 'user@example.com', 'createTime' => $createTime1]);
        $log2 = $this->createTestLoginLog(['identifier' => 'user@example.com', 'createTime' => $createTime2]);
        
        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->flush();

        $result = $this->repository->findOneBy(['identifier' => 'user@example.com'], ['createTime' => 'DESC']);

        $this->assertNotNull($result);
        $this->assertEquals($createTime2, $result->getCreateTime());
    }

    public function test_count_withNoCriteria_returnsTotal(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $log = $this->createTestLoginLog(['identifier' => "user{$i}@example.com"]);
            $this->entityManager->persist($log);
        }
        $this->entityManager->flush();

        $result = $this->repository->count([]);

        $this->assertEquals(3, $result);
    }

    public function test_count_withCriteria_returnsMatchingCount(): void
    {
        $log1 = $this->createTestLoginLog(['action' => 'success']);
        $log2 = $this->createTestLoginLog(['action' => 'failure']);
        $log3 = $this->createTestLoginLog(['action' => 'success']);
        
        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->persist($log3);
        $this->entityManager->flush();

        $result = $this->repository->count(['action' => 'success']);

        $this->assertEquals(2, $result);
    }

    public function test_repository_extendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(
            \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class,
            $this->repository
        );
    }

    public function test_repository_managesLoginLogEntity(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getEntityName');
        $method->setAccessible(true);

        $entityName = $method->invoke($this->repository);

        $this->assertEquals(LoginLog::class, $entityName);
    }

    public function test_entityPersistence_worksCorrectly(): void
    {
        $log = $this->createTestLoginLog([
            'identifier' => 'persistence@example.com',
            'action' => 'login',
            'sessionId' => 'session123',
            'createdFromIp' => '192.168.1.100'
        ]);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $persistedLog = $this->repository->find($log->getId());

        $this->assertNotNull($persistedLog);
        $this->assertEquals('persistence@example.com', $persistedLog->getIdentifier());
        $this->assertEquals('login', $persistedLog->getAction());
        $this->assertEquals('session123', $persistedLog->getSessionId());
        $this->assertEquals('192.168.1.100', $persistedLog->getCreatedFromIp());
    }

    public function test_unlockTime_persistenceWorksCorrectly(): void
    {
        $unlockTime = new DateTime('+30 minutes');
        $log = $this->createTestLoginLog(['unlockTime' => $unlockTime]);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $persistedLog = $this->repository->find($log->getId());

        $this->assertNotNull($persistedLog);
        $this->assertInstanceOf(DateTimeInterface::class, $persistedLog->getUnlockTime());
        $this->assertEquals($unlockTime->format('Y-m-d H:i:s'), $persistedLog->getUnlockTime()->format('Y-m-d H:i:s'));
    }
}