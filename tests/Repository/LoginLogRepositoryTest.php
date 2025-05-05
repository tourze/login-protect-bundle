<?php

namespace Tourze\LoginProtectBundle\Tests\Repository;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;

class LoginLogRepositoryTest extends TestCase
{
    /**
     * 测试仓库类的存在
     */
    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists(LoginLogRepository::class));
    }

    /**
     * 测试仓库继承自 ServiceEntityRepository
     */
    public function testRepositoryParentClass(): void
    {
        $reflection = new \ReflectionClass(LoginLogRepository::class);
        $this->assertTrue($reflection->isSubclassOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class));
    }

    /**
     * 测试仓库构造函数参数
     */
    public function testRepositoryConstructorParameters(): void
    {
        $reflection = new \ReflectionClass(LoginLogRepository::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('registry', $parameters[0]->getName());
        $this->assertEquals(ManagerRegistry::class, $parameters[0]->getType()->getName());
    }
}
