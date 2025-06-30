<?php

namespace Tourze\LoginProtectBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\LoginProtectBundle\LoginProtectBundle;

class LoginProtectBundleTest extends TestCase
{
    private LoginProtectBundle $bundle;

    public function test_bundle_extendsSymfonyBundle(): void
    {
        $this->assertInstanceOf(Bundle::class, $this->bundle);
    }

    public function test_bundle_isInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(LoginProtectBundle::class, $this->bundle);
    }

    public function test_getPath_returnsCorrectPath(): void
    {
        $expectedPath = dirname(__DIR__) . '/src';
        $actualPath = $this->bundle->getPath();

        $this->assertEquals($expectedPath, $actualPath);
    }

    public function test_getName_returnsCorrectName(): void
    {
        $expectedName = 'LoginProtectBundle';
        $actualName = $this->bundle->getName();

        $this->assertEquals($expectedName, $actualName);
    }

    public function test_getNamespace_returnsCorrectNamespace(): void
    {
        $expectedNamespace = 'Tourze\\LoginProtectBundle';
        $actualNamespace = $this->bundle->getNamespace();

        $this->assertEquals($expectedNamespace, $actualNamespace);
    }

    public function test_getContainerExtension_returnsCorrectExtension(): void
    {
        $extension = $this->bundle->getContainerExtension();

        $this->assertNotNull($extension);
        $this->assertInstanceOf(
            \Tourze\LoginProtectBundle\DependencyInjection\LoginProtectExtension::class,
            $extension
        );
    }

    public function test_getContainerExtensionClass_returnsCorrectClass(): void
    {
        $reflection = new \ReflectionClass($this->bundle);
        $method = $reflection->getMethod('getContainerExtensionClass');
        $method->setAccessible(true);

        $extensionClass = $method->invoke($this->bundle);

        $this->assertEquals(
            'Tourze\\LoginProtectBundle\\DependencyInjection\\LoginProtectExtension',
            $extensionClass
        );
    }

    public function test_bundle_hasCorrectDirectory(): void
    {
        $path = $this->bundle->getPath();
        $bundleDir = dirname($path);

        $this->assertDirectoryExists($path);
        $this->assertDirectoryExists($bundleDir . '/tests');
        $this->assertFileExists($path . '/LoginProtectBundle.php');
    }

    public function test_bundle_implementsCorrectMethods(): void
    {
        $this->assertNotEmpty($this->bundle->getPath());
        $this->assertNotEmpty($this->bundle->getName());
        $this->assertNotEmpty($this->bundle->getNamespace());
        $this->assertNotNull($this->bundle->getContainerExtension());
    }

    public function test_bundle_canBeInstantiatedMultipleTimes(): void
    {
        $bundle1 = new LoginProtectBundle();
        $bundle2 = new LoginProtectBundle();

        $this->assertInstanceOf(LoginProtectBundle::class, $bundle1);
        $this->assertInstanceOf(LoginProtectBundle::class, $bundle2);
        $this->assertNotSame($bundle1, $bundle2);
    }

    public function test_getName_isConsistentAcrossInstances(): void
    {
        $bundle1 = new LoginProtectBundle();
        $bundle2 = new LoginProtectBundle();

        $this->assertEquals($bundle1->getName(), $bundle2->getName());
    }

    public function test_getPath_isConsistentAcrossInstances(): void
    {
        $bundle1 = new LoginProtectBundle();
        $bundle2 = new LoginProtectBundle();

        $this->assertEquals($bundle1->getPath(), $bundle2->getPath());
    }

    public function test_getNamespace_isConsistentAcrossInstances(): void
    {
        $bundle1 = new LoginProtectBundle();
        $bundle2 = new LoginProtectBundle();

        $this->assertEquals($bundle1->getNamespace(), $bundle2->getNamespace());
    }

    public function test_getContainerExtension_returnsSameTypeAcrossInstances(): void
    {
        $bundle1 = new LoginProtectBundle();
        $bundle2 = new LoginProtectBundle();

        $extension1 = $bundle1->getContainerExtension();
        $extension2 = $bundle2->getContainerExtension();

        $this->assertEquals(get_class($extension1), get_class($extension2));
    }

    public function test_bundle_hasCorrectStructure(): void
    {
        $path = $this->bundle->getPath();
        $bundleDir = dirname($path);

        $expectedDirectories = [
            $path,
            $bundleDir . '/tests',
        ];

        foreach ($expectedDirectories as $directory) {
            $this->assertDirectoryExists($directory, "Directory {$directory} should exist");
        }

        $expectedFiles = [
            $bundleDir . '/composer.json',
            $path . '/LoginProtectBundle.php',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists($file, "File {$file} should exist");
        }
    }

    public function test_bundle_classExists(): void
    {
        $this->assertTrue(class_exists(LoginProtectBundle::class));
    }

    public function test_bundle_hasCorrectClassStructure(): void
    {
        $reflection = new \ReflectionClass(LoginProtectBundle::class);

        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isSubclassOf(Bundle::class));
    }

    public function test_getContainerExtension_multipleCalls_returnsSameInstance(): void
    {
        $extension1 = $this->bundle->getContainerExtension();
        $extension2 = $this->bundle->getContainerExtension();

        $this->assertSame($extension1, $extension2);
    }

    public function test_bundle_parentClass(): void
    {
        $reflection = new \ReflectionClass($this->bundle);
        $parentClass = $reflection->getParentClass();

        $this->assertEquals(Bundle::class, $parentClass->getName());
    }

    public function test_getName_matchesClassName(): void
    {
        $reflection = new \ReflectionClass($this->bundle);
        $shortName = $reflection->getShortName();

        $this->assertEquals($shortName, $this->bundle->getName());
    }

    protected function setUp(): void
    {
        $this->bundle = new LoginProtectBundle();
    }
}