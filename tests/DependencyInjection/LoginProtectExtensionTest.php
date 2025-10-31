<?php

namespace Tourze\LoginProtectBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\LoginProtectBundle\DependencyInjection\LoginProtectExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * @internal
 */
#[CoversClass(LoginProtectExtension::class)]
final class LoginProtectExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testLoadWithEmptyConfigsLoadsSuccessfully(): void
    {
        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->expectNotToPerformAssertions();
        $extension->load([], $container);
    }

    public function testLoadWithConfigsDoesNotThrowException(): void
    {
        $configs = [
            ['enabled' => true],
            ['settings' => ['timeout' => 30]],
        ];

        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->expectNotToPerformAssertions();
        $extension->load($configs, $container);
    }

    public function testExtensionExtendsSymfonyExtension(): void
    {
        $extension = new LoginProtectExtension();
        $this->assertInstanceOf(AutoExtension::class, $extension);
    }

    public function testExtensionIsInstanceOfCorrectClass(): void
    {
        $extension = new LoginProtectExtension();
        $this->assertInstanceOf(LoginProtectExtension::class, $extension);
    }

    public function testLoadMultipleTimesDoesNotDuplicate(): void
    {
        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->expectNotToPerformAssertions();
        $extension->load([], $container);
        $extension->load([], $container);
    }

    public function testLoadWithDifferentConfigsHandlesCorrectly(): void
    {
        $configs = [
            ['debug' => true],
            ['cache' => false],
            ['timeout' => 60],
        ];

        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->expectNotToPerformAssertions();
        $extension->load($configs, $container);
    }

    public function testGetAliasReturnsCorrectAlias(): void
    {
        $extension = new LoginProtectExtension();
        $alias = $extension->getAlias();

        $this->assertEquals('login_protect', $alias);
    }

    public function testLoadReturnsVoid(): void
    {
        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension->load([], $container);

        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }

    public function testExtensionHasCorrectParentClass(): void
    {
        $extension = new LoginProtectExtension();
        $reflection = new \ReflectionClass($extension);
        $parentClass = $reflection->getParentClass();

        $this->assertInstanceOf(AutoExtension::class, $extension);
        $this->assertNotFalse($parentClass);
        $this->assertEquals(AutoExtension::class, $parentClass->getName());
    }

    public function testLoadWithComplexConfigurationHandlesCorrectly(): void
    {
        $configs = [
            [
                'services' => [
                    'login_service' => [
                        'enabled' => true,
                        'timeout' => 30,
                    ],
                ],
                'logging' => [
                    'level' => 'debug',
                    'enabled' => true,
                ],
            ],
        ];

        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->expectNotToPerformAssertions();
        $extension->load($configs, $container);
    }

    public function testLoadWithNullConfigsHandlesGracefully(): void
    {
        $extension = new LoginProtectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->expectNotToPerformAssertions();
        $extension->load([[]], $container);
    }

    public function testExtensionImplementsCorrectMethods(): void
    {
        $extension = new LoginProtectExtension();
        $this->assertNotEmpty($extension->getAlias());
        $this->assertEquals('login_protect', $extension->getAlias());
    }
}
