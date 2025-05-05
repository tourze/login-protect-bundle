<?php

namespace Tourze\LoginProtectBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\LoginProtectBundle\DependencyInjection\LoginProtectExtension;

class LoginProtectExtensionTest extends TestCase
{
    /**
     * 测试服务加载
     */
    public function testServiceLoading(): void
    {
        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();

        // 加载服务配置
        $extension->load([], $container);

        // 验证扩展名称
        $this->assertEquals('login_protect', $extension->getAlias());

        // 验证是否有服务定义
        $this->assertNotEmpty($container->getDefinitions());
    }

    /**
     * 测试空配置加载
     */
    public function testEmptyConfiguration(): void
    {
        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();

        // 应该不会抛出异常
        $this->assertNull($extension->load([], $container));
    }
}
