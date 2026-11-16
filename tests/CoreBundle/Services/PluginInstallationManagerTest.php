<?php

declare(strict_types=1);

namespace Runalyze\\Bundle\\CoreBundle\\Tests\\Services;

use Doctrine\\Persistence\\ManagerRegistry;
use InvalidArgumentException;
use LogicException;
use PHPUnit\\Framework\\Attributes\\Group;
use PHPUnit\\Framework\\TestCase;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Account;
use Runalyze\\Bundle\\CoreBundle\\Services\\LegacyPluginInstantiator;
use Runalyze\\Bundle\\CoreBundle\\Services\\PluginInstallationManager;
use Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface;
use Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface;

#[Group('plugin')]
final class PluginInstallationManagerTest extends TestCase
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

    public function testUpdateInstalledPluginSettingsRequiresAnAuthenticatedAccount(): void
    {
        $this->expectException(LogicException::class);

        $this->createManagerWithoutAuthenticatedAccount()->updateInstalledPluginSettings([]);
    }

    public function testUpdateInstalledPluginSettingsRejectsAnInvalidActivationState(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManagerWithAuthenticatedAccount()->updateInstalledPluginSettings([
            1 => [
                'active' => 99,
                'order' => 1,
            ],
        ]);
    }

    public function testUpdateInstalledPluginSettingsRejectsAnInvalidOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManagerWithAuthenticatedAccount()->updateInstalledPluginSettings([
            1 => [
                'active' => 1,
                'order' => 0,
            ],
        ]);
    }

    private function createManagerWithAuthenticatedAccount(): PluginInstallationManager
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new Account());

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return new PluginInstallationManager(
            $this->createStub(ManagerRegistry::class),
            $tokenStorage,
            new LegacyPluginInstantiator()
        );
    }

    private function createManagerWithoutAuthenticatedAccount(): PluginInstallationManager
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        return new PluginInstallationManager(
            $this->createStub(ManagerRegistry::class),
            $tokenStorage,
            new LegacyPluginInstantiator()
        );
    }
}
