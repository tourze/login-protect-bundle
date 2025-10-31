<?php

declare(strict_types=1);

namespace Tourze\LoginProtectBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\LoginProtectBundle\Entity\LoginLog;

/**
 * 登录日志数据填充
 *
 * 创建测试用的登录日志数据
 * 只在 test 和 dev 环境中加载
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class LoginLogFixtures extends Fixture implements FixtureGroupInterface
{
    public const LOGIN_LOG_REFERENCE_PREFIX = 'login-log-';
    public const LOGIN_LOG_COUNT = 30;

    private const ACTIONS = [
        'success',
        'failure',
        'logout',
        'locked',
    ];

    private const IDENTIFIERS = [
        'user1@tourze.dev',
        'user2@tourze.dev',
        'admin@tourze.dev',
        'test@tourze.dev',
        'john.doe@tourze.dev',
        'jane.smith@tourze.dev',
        'bob.wilson@tourze.dev',
        'alice.brown@tourze.dev',
    ];

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::LOGIN_LOG_COUNT; ++$i) {
            $loginLog = new LoginLog();

            // 随机选择用户标识符
            $identifier = self::IDENTIFIERS[array_rand(self::IDENTIFIERS)];
            $loginLog->setIdentifier($identifier);

            // 随机选择操作类型
            $action = self::ACTIONS[array_rand(self::ACTIONS)];
            $loginLog->setAction($action);

            // 设置创建时间 (过去30天内的随机时间)
            $daysAgo = mt_rand(0, 30);
            $hoursAgo = mt_rand(0, 23);
            $minutesAgo = mt_rand(0, 59);
            $createTime = CarbonImmutable::now()
                ->modify("-{$daysAgo} days")
                ->modify("-{$hoursAgo} hours")
                ->modify("-{$minutesAgo} minutes")
            ;
            $loginLog->setCreateTime($createTime);

            // 设置随机IP地址
            $ip = sprintf('%d.%d.%d.%d',
                mt_rand(1, 255),
                mt_rand(1, 255),
                mt_rand(1, 255),
                mt_rand(1, 255)
            );
            $loginLog->setCreatedFromIp($ip);

            // 设置会话ID
            $sessionId = bin2hex(random_bytes(16));
            $loginLog->setSessionId($sessionId);

            // 如果是locked动作，设置解锁时间 (30分钟后)
            if ('locked' === $action) {
                $unlockTime = $createTime->modify('+30 minutes');
                $loginLog->setUnlockTime($unlockTime);
            }

            $manager->persist($loginLog);
            $this->addReference(self::LOGIN_LOG_REFERENCE_PREFIX . $i, $loginLog);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'login',
            'auth',
        ];
    }
}
