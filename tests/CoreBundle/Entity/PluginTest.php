<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Plugin;

final class PluginTest extends \PHPUnit\Framework\TestCase
{
    protected Plugin $Plugin;

    protected function setUp(): void
    {
        $this->Plugin = new Plugin();
    }

    public function testMoving(): void
    {
        $this->Plugin->setOrder(4);

        $this->assertSame(5, $this->Plugin->moveDown()->getOrder());
        $this->assertSame(4, $this->Plugin->moveUp()->getOrder());
    }

    public function testTogglingState(): void
    {
        $this->Plugin->setActive(Plugin::STATE_ACTIVE);

        $this->assertSame(Plugin::STATE_HIDDEN, $this->Plugin->toggleHidden()->getActive());
        $this->assertSame(Plugin::STATE_ACTIVE, $this->Plugin->toggleHidden()->getActive());

        $this->assertSame(Plugin::STATE_INACTIVE, $this->Plugin->setActive(Plugin::STATE_INACTIVE)->toggleHidden()->getActive());
    }
}
