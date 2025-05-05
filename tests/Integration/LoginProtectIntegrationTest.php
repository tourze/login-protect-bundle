<?php

namespace Tourze\LoginProtectBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
use Tourze\LoginProtectBundle\Service\LoginService;

/**
 * 测试 LoginProtectBundle 与 Symfony 框架的集成
 *
 * 注意: 运行此测试需要安装 Tourze\DoctrineEntityCheckerBundle 依赖
 */
class LoginProtectIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected function setUp(): void
    {
        // 由于依赖问题，暂时跳过集成测试
        $this->markTestSkipped('Integration tests temporarily disabled due to dependency issues.');
    }

    /**
     * 测试服务容器正确注册了所有服务
     */
    public function testServiceWiring(): void
    {
        $container = static::getContainer();

        // 验证核心服务存在于容器中
        $this->assertNotNull($container->get(LoginService::class));
        $this->assertNotNull($container->get(LoginLogRepository::class));
        $this->assertNotNull($container->get(DoctrineService::class));

        // 验证服务类型
        $this->assertInstanceOf(LoginService::class, $container->get(LoginService::class));
        $this->assertInstanceOf(LoginLogRepository::class, $container->get(LoginLogRepository::class));
    }

    /**
     * 测试登录日志持久化
     */
    public function testLoginLogPersistence(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        assert($entityManager instanceof EntityManagerInterface);

        // 创建测试登录日志
        $log = new LoginLog();
        $log->setIdentifier('test@example.com');
        $log->setAction('success');
        $log->setSessionId('test-session');

        // 直接保存
        $entityManager->persist($log);
        $entityManager->flush();

        // 验证ID已生成
        $this->assertNotNull($log->getId());

        // 清除实体管理器
        $entityManager->clear();

        // 从数据库重新查询
        $repository = $container->get(LoginLogRepository::class);
        $foundLogs = $repository->findBy(['identifier' => 'test@example.com']);

        // 验证查询结果
        $this->assertCount(1, $foundLogs);
        $this->assertEquals('success', $foundLogs[0]->getAction());
        $this->assertEquals('test-session', $foundLogs[0]->getSessionId());
    }

    /**
     * 测试登录服务的正确行为
     */
    public function testLoginService(): void
    {
        $container = static::getContainer();
        $loginService = $container->get(LoginService::class);
        $repository = $container->get(LoginLogRepository::class);

        // 使用LoginService记录登录日志
        $loginService->saveLoginLog('test-user', 'login', 'service-test-session');

        // 执行异步操作，确保数据被保存
        $doctrineService = $container->get(DoctrineService::class);
        $doctrineService->executeAsyncInserts();

        // 查询数据库验证
        $foundLogs = $repository->findBy(['identifier' => 'test-user']);

        // 验证结果
        $this->assertCount(1, $foundLogs);
        $this->assertEquals('login', $foundLogs[0]->getAction());
        $this->assertEquals('service-test-session', $foundLogs[0]->getSessionId());
    }
}
