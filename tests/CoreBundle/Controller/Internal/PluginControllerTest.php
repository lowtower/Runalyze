<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal;

use DB;
use PluginFactory;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class PluginControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();

        PluginFactory::clearCache();
    }

    private function persistPluginForAccount(Account $account, string $type, int $active, int $order): Plugin
    {
        $plugin = new Plugin()
            ->setKey('test-plugin')
            ->setType($type)
            ->setActive($active)
            ->setOrder($order)
            ->setAccount($account);

        $this->getEntityManager()
            ->persist($plugin);
        $this->getEntityManager()
            ->flush();

        return $plugin;
    }

    private function removeExistingPanelPluginsFor(Account $account): void
    {
        $existingPanels = $this->getEntityManager()
            ->getRepository(Plugin::class)
            ->findBy([
                'account' => $account,
                'type' => Plugin::TYPE_PANEL,
            ]);

        foreach ($existingPanels as $existingPanel) {
            $this->getEntityManager()
                ->remove($existingPanel);
        }

        $this->getEntityManager()
            ->flush();
    }

    private function insertLegacyPanelPlugin(int $accountId, string $key, int $active): int
    {
        return (int) DB::getInstance()->insert(
            'plugin',
            ['key', 'type', 'active', 'order', 'accountid'],
            [$key, 'panel', (string) $active, '0', (string) $accountId]
        );
    }

    private function deleteLegacyPluginsForAccount(int $accountId): void
    {
        DB::getInstance()->exec('DELETE FROM `' . PREFIX . 'plugin` WHERE `accountid` = ' . $accountId);

        PluginFactory::clearCache();
    }

    private function reloadPlugin(int $id): ?Plugin
    {
        $this->getEntityManager()
            ->clear();

        return $this->getEntityManager()
            ->getRepository(Plugin::class)
            ->find($id);
    }

    public function testThatTogglePanelActionMakesAnActivePluginHidden(): void
    {
        $plugin = $this->persistPluginForAccount($this->Account, Plugin::TYPE_STAT, Plugin::STATE_ACTIVE, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/toggle/' . $plugin->getId());

        $this->isSuccessful($this->Client->getResponse());
        $this->assertTrue($this->reloadPlugin($plugin->getId())->isHidden());
    }

    public function testThatTogglePanelActionMakesAHiddenPluginActive(): void
    {
        $plugin = $this->persistPluginForAccount($this->Account, Plugin::TYPE_STAT, Plugin::STATE_HIDDEN, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/toggle/' . $plugin->getId());

        $this->isSuccessful($this->Client->getResponse());
        $this->assertTrue($this->reloadPlugin($plugin->getId())->isActive());
    }

    public function testThatTogglePanelActionDoesNotAffectAnInactivePlugin(): void
    {
        $plugin = $this->persistPluginForAccount($this->Account, Plugin::TYPE_STAT, Plugin::STATE_INACTIVE, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/toggle/' . $plugin->getId());

        $this->isSuccessful($this->Client->getResponse());
        $this->assertTrue($this->reloadPlugin($plugin->getId())->isInactive());
    }

    public function testThatTogglePanelActionThrowsAccessDeniedForUnknownId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/toggle/314159');

        $this->assertStatusCode(403, $this->Client);
    }

    public function testThatTogglePanelActionThrowsAccessDeniedForPluginOwnedByAnotherAccount(): void
    {
        $plugin = $this->persistPluginForAccount($this->getEmptyAccount(), Plugin::TYPE_STAT, Plugin::STATE_ACTIVE, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/toggle/' . $plugin->getId());

        $this->assertStatusCode(403, $this->Client);
    }

    public function testThatMovePanelUpActionSwapsOrderWithPrecedingPanel(): void
    {
        $this->removeExistingPanelPluginsFor($this->Account);

        $lowerPanel = $this->persistPluginForAccount($this->Account, Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 0);
        $upperPanel = $this->persistPluginForAccount($this->Account, Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 1);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/move/' . $upperPanel->getId() . '/up');

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(0, $this->reloadPlugin($upperPanel->getId())->getOrder());
        $this->assertSame(1, $this->reloadPlugin($lowerPanel->getId())->getOrder());
    }

    public function testThatMovePanelUpActionDoesNothingWhenNoPrecedingPanelExists(): void
    {
        $this->removeExistingPanelPluginsFor($this->Account);

        $panel = $this->persistPluginForAccount($this->Account, Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/move/' . $panel->getId() . '/up');

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(0, $this->reloadPlugin($panel->getId())->getOrder());
    }

    public function testThatMovePanelDownActionSwapsOrderWithFollowingPanel(): void
    {
        $this->removeExistingPanelPluginsFor($this->Account);

        $upperPanel = $this->persistPluginForAccount($this->Account, Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 0);
        $lowerPanel = $this->persistPluginForAccount($this->Account, Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 1);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/move/' . $upperPanel->getId() . '/down');

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(1, $this->reloadPlugin($upperPanel->getId())->getOrder());
        $this->assertSame(0, $this->reloadPlugin($lowerPanel->getId())->getOrder());
    }

    public function testThatMovePanelDownActionDoesNothingWhenNoFollowingPanelExists(): void
    {
        $this->removeExistingPanelPluginsFor($this->Account);

        $panel = $this->persistPluginForAccount($this->Account, Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/move/' . $panel->getId() . '/down');

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(0, $this->reloadPlugin($panel->getId())->getOrder());
    }

    public function testThatMovePanelUpActionThrowsAccessDeniedForPluginOwnedByAnotherAccount(): void
    {
        $panel = $this->persistPluginForAccount($this->getEmptyAccount(), Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/move/' . $panel->getId() . '/up');

        $this->assertStatusCode(403, $this->Client);
    }

    public function testThatMovePanelDownActionThrowsAccessDeniedForPluginOwnedByAnotherAccount(): void
    {
        $panel = $this->persistPluginForAccount($this->getEmptyAccount(), Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 0);

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('GET', '/_internal/plugin/move/' . $panel->getId() . '/down');

        $this->assertStatusCode(403, $this->Client);
    }

    public function testThatContentPanelsActionReturnsEmptyContentForAccountWithoutAnyPanels(): void
    {
        $this->loginClient($this->Client, $this->getEmptyAccount(), 'default');

        ob_start();
        $this->Client->request('GET', '/_internal/plugin/all-panels');
        ob_end_clean();

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame('', $this->Client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    #[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
    public function testThatContentPanelsActionDoesNotRenderAFullyInactivePanel(): void
    {
        $accountId = $this->Account->getId();
        $this->deleteLegacyPluginsForAccount($accountId);
        $id = $this->insertLegacyPanelPlugin($accountId, 'RunalyzePluginPanel_Equipment', Plugin::STATE_INACTIVE);
        PluginFactory::clearCache();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');

            ob_start();
            $this->Client->request('GET', '/_internal/plugin/all-panels');
            ob_end_clean();

            $this->isSuccessful($this->Client->getResponse());
            $this->assertStringNotContainsString('id="panel-' . $id . '"', $this->Client->getResponse()->getContent());
        } finally {
            $this->deleteLegacyPluginsForAccount($accountId);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    #[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
    public function testThatContentPanelsActionMarksAHiddenEnabledPanelWithDisplayNoneStyle(): void
    {
        $accountId = $this->Account->getId();
        $this->deleteLegacyPluginsForAccount($accountId);
        $this->insertLegacyPanelPlugin($accountId, 'RunalyzePluginPanel_Equipment', Plugin::STATE_HIDDEN);
        PluginFactory::clearCache();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');

            ob_start();
            $this->Client->request('GET', '/_internal/plugin/all-panels');
            ob_end_clean();

            $this->isSuccessful($this->Client->getResponse());
            $this->assertStringContainsString(
                '<div class="panel-content" style="display:none;">',
                $this->Client->getResponse()
                    ->getContent()
            );
        } finally {
            $this->deleteLegacyPluginsForAccount($accountId);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('plugin')]
    #[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
    public function testThatContentPanelsActionDoesNotHideAnActivePanel(): void
    {
        $accountId = $this->Account->getId();
        $this->deleteLegacyPluginsForAccount($accountId);
        $id = $this->insertLegacyPanelPlugin($accountId, 'RunalyzePluginPanel_Equipment', Plugin::STATE_ACTIVE);
        PluginFactory::clearCache();

        try {
            $this->loginClient($this->Client, $this->Account, 'default');

            ob_start();
            $this->Client->request('GET', '/_internal/plugin/all-panels');
            ob_end_clean();
            $content = $this->Client->getResponse()
                ->getContent();

            $this->isSuccessful($this->Client->getResponse());
            $this->assertStringContainsString('id="panel-' . $id . '"', $content);
            $this->assertStringContainsString('<div class="panel-content">', $content);
            $this->assertStringNotContainsString('style="display:none;"', $content);
        } finally {
            $this->deleteLegacyPluginsForAccount($accountId);
        }
    }
}
