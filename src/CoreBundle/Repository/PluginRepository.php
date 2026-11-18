<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use SortDirection;

/**
 * @extends EntityRepository<Plugin>
 */
class PluginRepository extends EntityRepository
{
    /**
     * @return list<string>
     */
    public function findKeysForAccount(Account $account): array
    {
        $rows = $this->createQueryBuilder('plugin')
            ->select('plugin.key')
            ->where('plugin.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getArrayResult();

        $keys = [];

        foreach ($rows as $row) {
            $keys[] = (string) $row['key'];
        }

        return $keys;
    }

    /**
     * @return list<array{id: int, key: string, type: string, active: int, order: int}>
     */
    public function findDataForAccount(Account $account): array
    {
        return $this->createQueryBuilder('plugin')
            ->select('plugin.id, plugin.key, plugin.type, plugin.active, plugin.order')
            ->where('plugin.account = :account')
            ->setParameter('account', $account)
            ->orderBy('plugin.order', SortDirection::Ascending)
            ->addOrderBy('plugin.id', SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult();
    }

    public function findForAccountAndId(Account $account, int $id): ?Plugin
    {
        return $this->findOneBy([
            'account' => $account,
            'id' => $id,
        ]);
    }

    public function findForAccountAndKey(Account $account, string $key): ?Plugin
    {
        return $this->findOneBy([
            'account' => $account,
            'key' => $key,
        ]);
    }

    public function installForAccount(Account $account, string $key, string $type): Plugin
    {
        $plugin = new Plugin()
            ->setAccount($account)
            ->setKey($key)
            ->setType($type)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(99);

        $this->save($plugin);

        return $plugin;
    }

    public function setActiveForAccount(Account $account, int $pluginId, int $active): bool
    {
        $plugin = $this->findOneBy([
            'id' => $pluginId,
            'account' => $account,
        ]);

        if (! $plugin instanceof Plugin) {
            return false;
        }

        if ($plugin->getActive() === $active) {
            return false;
        }

        $this->save($plugin->setActive($active));

        return true;
    }

    /**
     * @param array<int, array{active: int, order: int}> $settings
     */
    public function updateSettingsForAccount(Account $account, array $settings): bool
    {
        $plugins = $this->findBy([
            'account' => $account,
        ]);
        $changed = false;

        foreach ($plugins as $plugin) {
            $pluginId = $plugin->getId();

            if ($pluginId === null) {
                continue;
            }

            $setting = $settings[$pluginId] ?? null;

            if ($setting === null) {
                continue;
            }

            if ($plugin->getActive() !== $setting['active'] || $plugin->getOrder() !== $setting['order']) {
                $plugin
                    ->setActive($setting['active'])
                    ->setOrder($setting['order']);

                $changed = true;
            }
        }

        if ($changed) {
            $this->saveMultiple($plugins);
        }

        return $changed;
    }

    public function findPanelByPosition(Account $account, int $position): ?Plugin
    {
        return $this->findOneBy([
            'account' => $account,
            'type' => 'panel',
            'order' => $position,
        ]);
    }

    public function movePanelUp(Plugin $panel): void
    {
        $otherPanel = $this->findPanelByPosition($panel->getAccount(), $panel->getOrder() - 1);

        if ($otherPanel instanceof Plugin) {
            $otherPanel->moveDown();

            $panel->moveUp();

            $this->saveMultiple([$panel, $otherPanel]);
        }
    }

    public function movePanelDown(Plugin $panel): void
    {
        $otherPanel = $this->findPanelByPosition($panel->getAccount(), $panel->getOrder() + 1);

        if ($otherPanel instanceof Plugin) {
            $otherPanel->moveUp();

            $panel->moveDown();

            $this->saveMultiple([$panel, $otherPanel]);
        }
    }

    public function toggleHidden(Plugin $plugin): void
    {
        $this->save($plugin->toggleHidden());
    }

    public function save(Plugin $plugin): void
    {
        $this->getEntityManager()->persist($plugin);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Plugin[] $plugins
     */
    public function saveMultiple(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            $this->getEntityManager()->persist($plugin);
        }

        $this->getEntityManager()->flush();
    }
}
