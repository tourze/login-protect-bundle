<?php

namespace Tourze\LoginProtectBundle\Service;

use Tourze\LoginProtectBundle\Exception\LockedAuthenticationException;

/**
 * 异常工厂服务
 *
 * 用于创建异常实例，避免在测试中直接实例化
 */
class ExceptionFactory
{
    /**
     * 创建 LockedAuthenticationException 实例
     */
    public function createLockedAuthenticationException(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ): LockedAuthenticationException {
        return new LockedAuthenticationException($message, $code, $previous);
    }
}
