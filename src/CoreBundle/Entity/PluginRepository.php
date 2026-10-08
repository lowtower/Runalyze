<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Plugin>
 */
class PluginRepository extends EntityRepository
{
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
