<?php

declare(strict_types=1);

namespace Tourze\LoginProtectBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\LoginProtectBundle\LoginProtectBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(LoginProtectBundle::class)]
#[RunTestsInSeparateProcesses]
final class LoginProtectBundleTest extends AbstractBundleTestCase
{
}
