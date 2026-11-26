<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Plugin\Panel;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRegistry;

#[Group('plugin')]
final class PanelRegistryTest extends TestCase
{
    public function testItResolvesRegisteredPanelsByStableKey(): void
    {
        $panel = $this->createStub(PanelInterface::class);
        $panel->method('getKey')->willReturn('example-panel');

        $registry = new PanelRegistry([$panel]);

        $this->assertTrue($registry->has('example-panel'));
        $this->assertFalse($registry->has('unknown-panel'));
        $this->assertSame($panel, $registry->find('example-panel'));
        $this->assertNull($registry->find('unknown-panel'));
        $this->assertSame($panel, $registry->get('example-panel'));
    }

    public function testListsPanelKeysInStableSortedOrder(): void
    {
        $lastPanel = $this->createStub(PanelInterface::class);
        $lastPanel->method('getKey')->willReturn('ZuluPanel');

        $firstPanel = $this->createStub(PanelInterface::class);
        $firstPanel->method('getKey')->willReturn('AlphaPanel');

        $registry = new PanelRegistry([$lastPanel, $firstPanel]);

        $this->assertSame(['AlphaPanel', 'ZuluPanel'], $registry->getKeys());
    }

    public function testItRejectsUnknownPanelKeys(): void
    {
        $registry = new PanelRegistry([]);

        $this->expectException(InvalidArgumentException::class);

        $registry->get('unknown-panel');
    }

    public function testItRejectsDuplicatePanelKeys(): void
    {
        $firstPanel = $this->createStub(PanelInterface::class);
        $firstPanel->method('getKey')->willReturn('duplicate-panel');

        $secondPanel = $this->createStub(PanelInterface::class);
        $secondPanel->method('getKey')->willReturn('duplicate-panel');

        $this->expectException(LogicException::class);

        new PanelRegistry([$firstPanel, $secondPanel]);
    }
}
