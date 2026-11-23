<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Plugin\Statistics;

use LogicException;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;

final class ModernStatisticsRegistryTest extends TestCase
{
    public function testRejectsDuplicateInstallationKeys(): void
    {
        $statistic = $this->createStub(ModernStatisticsInterface::class);
        $statistic->method('getKey')->willReturn('RunalyzePluginStat_MonthlyStats');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('already registered');
        new ModernStatisticsRegistry([$statistic, $statistic]);
    }
}
