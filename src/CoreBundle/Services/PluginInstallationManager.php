<?php

declare(strict_types=1);

namespace Runalyze\\Bundle\\CoreBundle\\Services;

use LogicException;
use PluginInstaller;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Account;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Plugin;
use Runalyze\\Bundle\\CoreBundle\\Repository\\PluginRepository;
use Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface;

final readonly class PluginInstallationManager
{
    public function __construct(
        private PluginRepository $pluginRepository,
        private TokenStorageInterface $tokenStorage,
        private LegacyPluginInstantiator $pluginInstantiator
    ) {}

    /**
     * @return list<string>
     */
    public function getInstalledPluginKeys(): array
    {
        return $this->pluginRepository->findKeysForAccount($this->getAccount());
    }

    public function isInstalled(string $key): bool
    {
        return in_array($key, $this->getInstalledPluginKeys(), true);
    }

    /**
     * @throws LogicException if there is no authenticated account
     * @throws \\InvalidArgumentException if the plugin implementation is invalid
     */
    public function install(string $key): bool
    {
        $account = $this->getAccount();

        if (in_array($key, $this->pluginRepository->findKeysForAccount($account), true)) {
            return false;
        }

        $plugin = $this->pluginInstantiator->instantiate($key, PluginInstaller::ID);

        $this->pluginRepository->installForAccount($account, $key, $plugin->typeString());

        return true;
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
}
