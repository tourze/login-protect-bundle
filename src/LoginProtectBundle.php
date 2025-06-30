<?php

namespace Tourze\LoginProtectBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\ScheduleEntityCleanBundle\ScheduleEntityCleanBundle;

class LoginProtectBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            ScheduleEntityCleanBundle::class => ['all' => true],
        ];
    }
}
