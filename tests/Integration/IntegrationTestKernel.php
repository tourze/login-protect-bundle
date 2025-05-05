<?php

namespace Tourze\LoginProtectBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Tourze\DoctrineAsyncBundle\DoctrineAsyncBundle;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\LoginProtectBundle\LoginProtectBundle;
use Tourze\SnowflakeBundle\SnowflakeBundle;

class IntegrationTestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new SnowflakeBundle();
        yield new DoctrineSnowflakeBundle();
        yield new DoctrineAsyncBundle();
        yield new DoctrineIndexedBundle();
        yield new DoctrineIpBundle();
        yield new DoctrineTimestampBundle();
        yield new LoginProtectBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // 基本框架配置
        $container->extension('framework', [
            'secret' => 'TEST_SECRET',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
            'uid' => [
                'default_uuid_version' => 7,
                'time_based_uuid_version' => 7,
            ],
            'validation' => [
                'email_validation_mode' => 'html5',
            ],
        ]);

        // Doctrine 配置 - 使用内存数据库
        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'url' => 'sqlite:///:memory:',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'controller_resolver' => [
                    'auto_mapping' => false,
                ],
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'auto_mapping' => true,
                'mappings' => [
                    'TestEntity' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/../../src/Entity',
                        'prefix' => 'Tourze\LoginProtectBundle\Entity',
                    ],
                ],
            ],
        ]);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/login_protect_bundle_tests/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/login_protect_bundle_tests/log';
    }
}
