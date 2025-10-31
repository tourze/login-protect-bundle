<?php

namespace Tourze\LoginProtectBundle\Tests\TestBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;

class TestBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // 创建一个简单的Mock实现
        $mockDefinition = new Definition();
        $mockDefinition->setSynthetic(true);
        $mockDefinition->setPublic(true);
        $container->setDefinition('Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService', $mockDefinition);

        // 注册别名确保autowiring正常工作
        $container->setAlias(AsyncInsertService::class, 'Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService')
            ->setPublic(true)
        ;

        // 直接注册Repository，避免自动发现
        $container->register('Tourze\LoginProtectBundle\Repository\LoginLogRepository', LoginLogRepository::class)
            ->setArguments(['@doctrine'])
            ->setPublic(true)
        ;
    }
}
