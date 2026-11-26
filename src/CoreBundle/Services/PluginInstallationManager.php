<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use LogicException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelMetadataInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class PluginInstallationManager
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private TokenStorageInterface $tokenStorage,
        private ModernPanelRegistry $modernPanelRegistry,
        private ModernStatisticsRegistry $modernStatisticsRegistry
    ) {}

    /**
     * @return list<string>
     *
     * @throws LogicException if there is no authenticated account
     */
    public function getInstalledPluginKeys(): array
    {
        $account = $this->getAccount();

        return $this->getPluginRepository()->findKeysForAccount($account);
    }

    /**
     * @return list<array{id: int, key: string, type: string, active: int, order: int}>
     *
     * @throws LogicException if there is no authenticated account
     */
    public function getInstalledPluginData(): array
    {
        $account = $this->getAccount();

        return $this->getPluginRepository()->findDataForAccount($account);
    }

    /**
     * @param array<int, array{active: int, order: int}> $settings
     *
     * @throws LogicException if there is no authenticated account
     * @throws InvalidArgumentException if a plugin state or order is invalid
     */
    public function updateInstalledPluginSettings(array $settings): bool
    {
        $account = $this->getAccount();

        foreach ($settings as $setting) {
            if (! in_array($setting['active'], [
                Plugin::STATE_INACTIVE,
                Plugin::STATE_ACTIVE,
                Plugin::STATE_HIDDEN,
            ], true) || $setting['order'] < 1) {
                throw new InvalidArgumentException('Plugin settings contain an invalid state or order.');
            }
        }

        return $this->getPluginRepository()->updateSettingsForAccount($account, $settings);
    }

    /**
     * @throws LogicException if there is no authenticated account
     * @throws InvalidArgumentException if the activation state is invalid
     */
    public function setInstalledPluginActive(int $pluginId, int $active): bool
    {
        if (! in_array($active, [
            Plugin::STATE_INACTIVE,
            Plugin::STATE_ACTIVE,
            Plugin::STATE_HIDDEN,
        ], true)) {
            throw new InvalidArgumentException('Plugin activation state is invalid.');
        }

        $account = $this->getAccount();

        return $this->getPluginRepository()->setActiveForAccount($account, $pluginId, $active);
    }

    public function getInstallationForAccount(Account $account, int $pluginId): ?Plugin
    {
        return $this->getPluginRepository()->findForAccountAndId($account, $pluginId);
    }

    /**
     * @throws LogicException if there is no authenticated account
     */
    public function isInstalled(string $key): bool
    {
        $account = $this->getAccount();

        return $this->getPluginRepository()->findForAccountAndKey($account, $key) instanceof Plugin;
    }

    /**
     * @throws LogicException if there is no authenticated account
     * @throws \InvalidArgumentException if the plugin implementation is invalid
     */
    public function install(string $key): bool
    {
        $account = $this->getAccount();

        if ($this->getPluginRepository()->findForAccountAndKey($account, $key) instanceof Plugin) {
            return false;
        }

        if ($this->modernPanelRegistry->has($key)
            && $this->modernPanelRegistry->get($key) instanceof ModernPanelMetadataInterface
        ) {
            $this->getPluginRepository()->installForAccount($account, $key, Plugin::TYPE_PANEL);

            return true;
        }

        if ($this->modernStatisticsRegistry->find($key) instanceof ModernStatisticsInterface) {
            $this->getPluginRepository()->installForAccount($account, $key, Plugin::TYPE_STAT);

            return true;
        }

        throw new InvalidArgumentException(sprintf('Unknown plugin key "%s".', $key));
    }

    /**
     * @throws LogicException if there is no authenticated account
     */
    private function getAccount(): Account
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (! $user instanceof Account) {
            throw new LogicException('Plugin installation requires an authenticated account.');
        }

        return $user;
    }

    private function getPluginRepository(): PluginRepository
    {
        $repository = $this->doctrine->getRepository(Plugin::class);
        assert($repository instanceof PluginRepository);

        return $repository;
    }
}
