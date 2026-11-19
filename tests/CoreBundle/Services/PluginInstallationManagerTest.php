<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelMetadataInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class ModernInstallablePanelFixture implements ModernPanelInterface, ModernPanelMetadataInterface
{
    public function getKey(): string
    {
        return 'ModernPanelWithoutLegacyClass';
    }

    public function getName(): string
    {
        return 'Modern';
    }

    public function getDescription(): string
    {
        return 'Modern panel';
    }

    public function getTemplate(): string
    {
        return 'panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    public function present(Account $account, Plugin $installation): array
    {
        return [];
    }
}

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

    public function testSetInstalledPluginActiveRequiresAnAuthenticatedAccount(): void
    {
        $this->expectException(LogicException::class);

        $this->createManagerWithoutAuthenticatedAccount()->setInstalledPluginActive(1, 1);
    }

    public function testSetInstalledPluginActiveRejectsAnInvalidActivationState(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManagerWithAuthenticatedAccount()->setInstalledPluginActive(1, 99);
    }

    public function testInstallsModernPanelWithoutInstantiatingLegacyClass(): void
    {
        $account = new Account();
        $repository = $this->createMock(PluginRepository::class);
        $repository->method('findForAccountAndKey')
            ->with($account, 'ModernPanelWithoutLegacyClass')
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('installForAccount')
            ->with($account, 'ModernPanelWithoutLegacyClass', Plugin::TYPE_PANEL);

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($repository);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($account);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $panel = new ModernInstallablePanelFixture();

        $manager = new PluginInstallationManager(
            $doctrine,
            $tokenStorage,
            new LegacyPluginInstantiator(),
            new ModernPanelRegistry([$panel])
        );

        $this->assertTrue($manager->install('ModernPanelWithoutLegacyClass'));
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
