<?php

namespace Tourze\LoginProtectBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\LoginProtectBundle\DependencyInjection\LoginProtectExtension;
use Tourze\LoginProtectBundle\EventSubscriber\LoginCheckSubscriber;
use Tourze\LoginProtectBundle\EventSubscriber\LoginLogSubscriber;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
use Tourze\LoginProtectBundle\Service\LoginService;

class LoginProtectExtensionTest extends TestCase
{
    private LoginProtectExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new LoginProtectExtension();
        $this->container = new ContainerBuilder();
    }

    public function test_load_withEmptyConfigs_loadsSuccessfully(): void
    {
        $this->extension->load([], $this->container);

        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function test_load_withConfigs_doesNotThrowException(): void
    {
        $configs = [
            ['enabled' => true],
            ['settings' => ['timeout' => 30]]
        ];

        $this->extension->load($configs, $this->container);

        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function test_extension_extendsSymfonyExtension(): void
    {
        $this->assertInstanceOf(Extension::class, $this->extension);
    }

    public function test_extension_isInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(LoginProtectExtension::class, $this->extension);
    }

    public function test_load_registersExpectedServices(): void
    {
        $this->extension->load([], $this->container);

        $expectedServices = [
            LoginService::class,
            LoginLogRepository::class,
            LoginCheckSubscriber::class,
            LoginLogSubscriber::class,
        ];

        $definitions = $this->container->getDefinitions();
        $serviceIds = array_keys($definitions);

        foreach ($expectedServices as $expectedService) {
            $this->assertContains($expectedService, $serviceIds, "Service {$expectedService} should be registered");
        }
    }

    public function test_load_withContainerBuilder_setsCorrectServices(): void
    {
        $this->extension->load([], $this->container);

        $this->assertTrue($this->container->hasDefinition(LoginService::class));
        $this->assertTrue($this->container->hasDefinition(LoginLogRepository::class));
        $this->assertTrue($this->container->hasDefinition(LoginCheckSubscriber::class));
        $this->assertTrue($this->container->hasDefinition(LoginLogSubscriber::class));
    }

    public function test_load_configuresServicesCorrectly(): void
    {
        $this->extension->load([], $this->container);

        $loginServiceDefinition = $this->container->getDefinition(LoginService::class);
        $this->assertTrue($loginServiceDefinition->isAutowired());
        $this->assertTrue($loginServiceDefinition->isAutoconfigured());

        $repositoryDefinition = $this->container->getDefinition(LoginLogRepository::class);
        $this->assertTrue($repositoryDefinition->isAutowired());
        $this->assertTrue($repositoryDefinition->isAutoconfigured());
    }

    public function test_load_setsCorrectTags(): void
    {
        $this->extension->load([], $this->container);

        $subscriberDefinition = $this->container->getDefinition(LoginCheckSubscriber::class);
        $this->assertTrue($subscriberDefinition->isAutoconfigured());

        $logSubscriberDefinition = $this->container->getDefinition(LoginLogSubscriber::class);
        $this->assertTrue($logSubscriberDefinition->isAutoconfigured());
    }

    public function test_load_multipleTimes_doesNotDuplicate(): void
    {
        $this->extension->load([], $this->container);
        $definitionsCount1 = count($this->container->getDefinitions());

        $this->extension->load([], $this->container);
        $definitionsCount2 = count($this->container->getDefinitions());

        $this->assertEquals($definitionsCount1, $definitionsCount2);
    }

    public function test_load_withDifferentConfigs_handlesCorrectly(): void
    {
        $configs = [
            ['debug' => true],
            ['cache' => false],
            ['timeout' => 60]
        ];

        $this->extension->load($configs, $this->container);

        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function test_getAlias_returnsCorrectAlias(): void
    {
        $alias = $this->extension->getAlias();

        $this->assertEquals('login_protect', $alias);
    }

    public function test_load_returnsVoid(): void
    {
        $this->extension->load([], $this->container);

        $this->addToAssertionCount(1);
    }

    public function test_extension_hasCorrectParentClass(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $parentClass = $reflection->getParentClass();

        $this->assertEquals(Extension::class, $parentClass->getName());
    }

    public function test_load_withComplexConfiguration_handlesCorrectly(): void
    {
        $configs = [
            [
                'services' => [
                    'login_service' => [
                        'enabled' => true,
                        'timeout' => 30
                    ]
                ],
                'logging' => [
                    'level' => 'debug',
                    'enabled' => true
                ]
            ]
        ];

        $this->extension->load($configs, $this->container);

        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function test_container_afterLoad_hasExpectedStructure(): void
    {
        $this->extension->load([], $this->container);

        $definitions = $this->container->getDefinitions();
        $this->assertNotEmpty($definitions);

        foreach ($definitions as $definition) {
            $this->assertNotNull($definition->getClass());
        }
    }

    public function test_load_withNullConfigs_handlesGracefully(): void
    {
        $this->extension->load([[]], $this->container);

        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function test_extension_implementsCorrectMethods(): void
    {
        $this->assertNotEmpty($this->extension->getAlias());
        $this->assertEquals('login_protect', $this->extension->getAlias());
    }

    public function test_load_setsCorrectServiceClasses(): void
    {
        $this->extension->load([], $this->container);

        $loginServiceDefinition = $this->container->getDefinition(LoginService::class);
        $this->assertEquals(LoginService::class, $loginServiceDefinition->getClass());

        $repositoryDefinition = $this->container->getDefinition(LoginLogRepository::class);
        $this->assertEquals(LoginLogRepository::class, $repositoryDefinition->getClass());
    }
}
