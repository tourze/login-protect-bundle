<?php

namespace Tourze\LoginProtectBundle\Tests\Service;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\LoginProtectBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 无需特殊设置
    }

    public function testInvokeCreatesSecurityMenu(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);
        $adminMenu($rootMenu);

        $securityMenu = $rootMenu->getChild('安全管理');
        self::assertNotNull($securityMenu);
        self::assertSame('fas fa-shield-alt', $securityMenu->getAttribute('icon'));

        $loginLogMenu = $securityMenu->getChild('登录日志');
        self::assertNotNull($loginLogMenu);
        self::assertSame('fas fa-history', $loginLogMenu->getAttribute('icon'));
    }

    public function testInvokeUsesExistingSecurityMenu(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);
        $existingSecurityMenu = $rootMenu->addChild('安全管理');

        $adminMenu($rootMenu);

        $securityMenu = $rootMenu->getChild('安全管理');
        self::assertSame($existingSecurityMenu, $securityMenu);

        $loginLogMenu = $securityMenu->getChild('登录日志');
        self::assertNotNull($loginLogMenu);
    }
}
