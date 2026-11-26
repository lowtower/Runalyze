<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Plugin\Statistics;

use LogicException;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRegistry;

final class StatisticsRegistryTest extends TestCase
{
    public function testRejectsDuplicateInstallationKeys(): void
    {
        $statistic = $this->createStub(StatisticsInterface::class);
        $statistic->method('getKey')->willReturn('RunalyzePluginStat_MonthlyStats');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('already registered');
        new StatisticsRegistry([$statistic, $statistic]);
    }
}
