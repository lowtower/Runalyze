<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginRepository;

/**
 * @group requiresDoctrine
 */
class PluginRepositoryTest extends AbstractRepositoryTestCase
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

    public function testMoving(): void
    {
        $firstPanel = $this->insertPanel(1);
        $secondPanel = $this->insertPanel(2);

        $this->PluginRepository->movePanelUp($secondPanel);

        $this->assertEquals(1, $secondPanel->getOrder());
        $this->assertEquals(2, $firstPanel->getOrder());

        $this->PluginRepository->movePanelUp($secondPanel);

        $this->assertEquals(1, $secondPanel->getOrder());
        $this->assertEquals(2, $firstPanel->getOrder());

        $this->PluginRepository->movePanelDown($secondPanel);

        $this->assertEquals(2, $secondPanel->getOrder());
        $this->assertEquals(1, $firstPanel->getOrder());
    }
}
