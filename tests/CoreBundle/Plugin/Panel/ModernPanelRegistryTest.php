<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Plugin\Panel;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;

#[Group('plugin')]
final class ModernPanelRegistryTest extends TestCase
{
    public function testItResolvesRegisteredPanelsByStableKey(): void
    {
        $panel = $this->createStub(ModernPanelInterface::class);
        $panel->method('getKey')->willReturn('example-panel');

        $registry = new ModernPanelRegistry([$panel]);

        $this->assertTrue($registry->has('example-panel'));
        $this->assertFalse($registry->has('unknown-panel'));
        $this->assertSame($panel, $registry->get('example-panel'));
    }

    public function testItRejectsUnknownPanelKeys(): void
    {
        $registry = new ModernPanelRegistry([]);

        $this->expectException(InvalidArgumentException::class);

        $registry->get('unknown-panel');
    }

    public function testItRejectsDuplicatePanelKeys(): void
    {
        $firstPanel = $this->createStub(ModernPanelInterface::class);
        $firstPanel->method('getKey')->willReturn('duplicate-panel');

        $secondPanel = $this->createStub(ModernPanelInterface::class);
        $secondPanel->method('getKey')->willReturn('duplicate-panel');

        $this->expectException(LogicException::class);

        new ModernPanelRegistry([$firstPanel, $secondPanel]);
    }
}
