<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;

#[\PHPUnit\Framework\Attributes\Group('plugin')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class PluginRepositoryTest extends AbstractRepositoryTestCase
{
    protected PluginRepository $PluginRepository;

    protected Account $EmptyAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->PluginRepository = $this->getEntityManager()
            ->getRepository(Plugin::class);
        $this->EmptyAccount = $this->getEmptyAccount();
    }

    protected function insertPanel(int $position, ?string $key = null): Plugin
    {
        $panel = new Plugin();
        $panel->setAccount($this->EmptyAccount);
        $panel->setType(Plugin::TYPE_PANEL);
        $panel->setActive(Plugin::STATE_ACTIVE);
        $panel->setOrder($position);
        $panel->setKey($key ?: bin2hex(openssl_random_pseudo_bytes(16)));

        $this->PluginRepository->save($panel);

        return $panel;
    }

    public function testFindKeysForAccountReturnsInstalledPluginKeys(): void
    {
        $this->insertPanel(1, 'first-plugin');
        $this->insertPanel(2, 'second-plugin');

        $this->assertEqualsCanonicalizing(
            ['first-plugin', 'second-plugin'],
            $this->PluginRepository->findKeysForAccount($this->EmptyAccount)
        );
    }

    public function testFindDataForAccountReturnsPluginState(): void
    {
        $plugin = $this->insertPanel(4, 'installed-plugin');

        $this->assertSame([
            [
                'id' => $plugin->getId(),
                'key' => 'installed-plugin',
                'type' => Plugin::TYPE_PANEL,
                'active' => Plugin::STATE_ACTIVE,
                'order' => 4,
            ],
        ], $this->PluginRepository->findDataForAccount($this->EmptyAccount));
    }

    public function testFindDataForAccountReadsCurrentDatabaseStateInsteadOfManagedEntities(): void
    {
        $plugin = $this->insertPanel(4, 'installed-plugin');

        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE runalyze_plugin SET `key` = :key WHERE id = :id',
            [
                'key' => 'updated-plugin',
                'id' => $plugin->getId(),
            ]
        );

        $this->assertSame([
            [
                'id' => $plugin->getId(),
                'key' => 'updated-plugin',
                'type' => Plugin::TYPE_PANEL,
                'active' => Plugin::STATE_ACTIVE,
                'order' => 4,
            ],
        ], $this->PluginRepository->findDataForAccount($this->EmptyAccount));
    }

    public function testFindKeysForAccountDoesNotReturnPluginsFromAnotherAccount(): void
    {
        $this->insertPanel(1, 'empty-account-plugin');

        $defaultAccountPlugin = new Plugin()
            ->setAccount($this->getDefaultAccount())
            ->setKey('default-account-plugin')
            ->setType(Plugin::TYPE_PANEL)
            ->setActive(Plugin::STATE_ACTIVE)
            ->setOrder(1);

        $this->PluginRepository->save($defaultAccountPlugin);

        $this->assertSame(
            ['empty-account-plugin'],
            $this->PluginRepository->findKeysForAccount($this->EmptyAccount)
        );
    }

    public function testInstallForAccountPersistsPluginWithDefaultState(): void
    {
        $plugin = $this->PluginRepository->installForAccount(
            $this->EmptyAccount,
            'new-plugin',
            Plugin::TYPE_PANEL
        );

        $this->assertNotNull($plugin->getId());
        $this->assertSame($this->EmptyAccount, $plugin->getAccount());
        $this->assertSame('new-plugin', $plugin->getKey());
        $this->assertSame(Plugin::TYPE_PANEL, $plugin->getType());
        $this->assertSame(Plugin::STATE_ACTIVE, $plugin->getActive());
        $this->assertSame(99, $plugin->getOrder());
        $this->assertSame(['new-plugin'], $this->PluginRepository->findKeysForAccount($this->EmptyAccount));
    }

    public function testMoving(): void
    {
        $firstPanel = $this->insertPanel(1);
        $secondPanel = $this->insertPanel(2);

        $this->PluginRepository->movePanelUp($secondPanel);

        $this->assertSame(1, $secondPanel->getOrder());
        $this->assertSame(2, $firstPanel->getOrder());

        $this->PluginRepository->movePanelUp($secondPanel);

        $this->assertSame(1, $secondPanel->getOrder());
        $this->assertSame(2, $firstPanel->getOrder());

        $this->PluginRepository->movePanelDown($secondPanel);

        $this->assertSame(2, $secondPanel->getOrder());
        $this->assertSame(1, $firstPanel->getOrder());
    }
}
