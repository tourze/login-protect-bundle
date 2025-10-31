<?php

declare(strict_types=1);

namespace Tourze\LoginProtectBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\LoginProtectBundle\Controller\Admin\LoginLogCrudController;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(LoginLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class LoginLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testGetEntityFqcn(): void
    {
        self::assertSame(LoginLog::class, LoginLogCrudController::getEntityFqcn());
    }

    /**
     * @return AbstractCrudController<LoginLog>
     * @phpstan-return LoginLogCrudController
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(LoginLogCrudController::class);
        self::assertInstanceOf(AbstractCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'user_identifier' => ['用户标识'];
        yield 'login_result' => ['登录结果'];
        yield 'login_ip' => ['登录IP'];
        yield 'session_id' => ['会话ID'];
        yield 'created_at' => ['创建时间'];
        yield 'unlocked_at' => ['解锁时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // LoginLogCrudController 禁用了编辑操作，但父类测试需要这个方法
        // 提供一个虚拟字段以避免空数据集错误
        yield 'readonly' => ['readonly_entity_no_edit_fields'];
    }


    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // LoginLogCrudController 禁用了新建操作，但父类测试需要这个方法
        // 提供一个虚拟字段以避免空数据集错误
        yield 'readonly' => ['readonly_entity_no_new_fields'];
    }
}
