<?php

namespace Runalyze\\Bundle\\CoreBundle\\Repository;

use Doctrine\\ORM\\EntityRepository;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Account;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Plugin;

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

        return array_column($rows, 'key');
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
