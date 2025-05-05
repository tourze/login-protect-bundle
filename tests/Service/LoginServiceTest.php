<?php

namespace Tourze\LoginProtectBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\LoginProtectBundle\Entity\LoginLog;
use Tourze\LoginProtectBundle\Service\LoginService;

class LoginServiceTest extends TestCase
{
    private DoctrineService|MockObject $doctrineService;
    private LoggerInterface|MockObject $logger;
    private LoginService $loginService;

    protected function setUp(): void
    {
        $this->doctrineService = $this->createMock(DoctrineService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->loginService = new LoginService($this->doctrineService, $this->logger);
    }

    /**
     * 测试使用 UserInterface 对象保存登录日志
     */
    public function testSaveLoginLog_withUserInterface(): void
    {
        // 创建模拟对象
        $user = $this->createMock(UserInterface::class);

        // 设置期望
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user@example.com');

        $this->doctrineService->expects($this->once())
            ->method('asyncInsert')
            ->with($this->callback(function (LoginLog $log) {
                return $log->getIdentifier() === 'user@example.com'
                    && $log->getAction() === 'success'
                    && $log->getSessionId() === '';
            }));

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('saveLoginLog'),
                $this->callback(function (array $context) use ($user) {
                    return $context['user'] === $user
                        && $context['action'] === 'success';
                })
            );

        // 执行测试
        $this->loginService->saveLoginLog($user, 'success');
    }

    /**
     * 测试使用字符串标识符保存登录日志
     */
    public function testSaveLoginLog_withString(): void
    {
        // 设置期望
        $this->doctrineService->expects($this->once())
            ->method('asyncInsert')
            ->with($this->callback(function (LoginLog $log) {
                return $log->getIdentifier() === 'string-user'
                    && $log->getAction() === 'failure'
                    && $log->getSessionId() === '';
            }));

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('saveLoginLog'),
                $this->callback(function (array $context) {
                    return $context['user'] === 'string-user'
                        && $context['action'] === 'failure';
                })
            );

        // 执行测试
        $this->loginService->saveLoginLog('string-user', 'failure');
    }

    /**
     * 测试使用 null 用户参数保存登录日志 (应该无操作)
     */
    public function testSaveLoginLog_withNull(): void
    {
        // 设置期望
        $this->doctrineService->expects($this->never())
            ->method('asyncInsert');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('saveLoginLog'),
                $this->callback(function (array $context) {
                    return $context['user'] === null
                        && $context['action'] === 'logout';
                })
            );

        // 执行测试
        $this->loginService->saveLoginLog(null, 'logout');
    }

    /**
     * 测试使用会话 ID 保存登录日志
     */
    public function testSaveLoginLog_withSessionId(): void
    {
        // 设置期望
        $this->doctrineService->expects($this->once())
            ->method('asyncInsert')
            ->with($this->callback(function (LoginLog $log) {
                return $log->getIdentifier() === 'session-user'
                    && $log->getAction() === 'login'
                    && $log->getSessionId() === 'test-session-id';
            }));

        // 执行测试
        $this->loginService->saveLoginLog('session-user', 'login', 'test-session-id');
    }
}
