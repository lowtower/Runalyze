<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
final class PluginInstallationManagerTest extends TestCase
final class PluginInstallationManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testInstalledPluginKeysRequireAnAuthenticatedAccount(): void
    {
        $this->expectException(LogicException::class);

        $this->createManagerWithoutAuthenticatedAccount()->getInstalledPluginKeys();
    }

    public function testInstalledPluginDataRequiresAnAuthenticatedAccount(): void
    {
        $this->expectException(LogicException::class);

        $this->createManagerWithoutAuthenticatedAccount()->getInstalledPluginData();
    }

    public function testIsInstalledRequiresAnAuthenticatedAccount(): void
    {
        $this->expectException(LogicException::class);

        $this->createManagerWithoutAuthenticatedAccount()->isInstalled('some-plugin');
    }

    public function testInstallRequiresAnAuthenticatedAccount(): void
    {
        $this->expectException(LogicException::class);

        $this->createManagerWithoutAuthenticatedAccount()->install('some-plugin');
    }

    private function createManagerWithoutAuthenticatedAccount(): PluginInstallationManager
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        return new PluginInstallationManager(
            $this->createMock(ManagerRegistry::class),
            $tokenStorage,
            new LegacyPluginInstantiator()
        );
    }
}
