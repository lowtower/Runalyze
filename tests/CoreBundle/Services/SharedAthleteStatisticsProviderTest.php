<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\SharedAthleteStatisticsProvider;

#[Group('plugin')]
final class SharedAthleteStatisticsProviderTest extends TestCase
{
    public function testRejectsAnAccountThatHasNotBeenPersisted(): void
    {
        $provider = new SharedAthleteStatisticsProvider(
            $this->createStub(ManagerRegistry::class),
            new LegacyPluginInstantiator()
        );

        $this->expectException(\\LogicException::class);
        $provider->getYearComparisonTable(new Account());
    }

    public function testReturnsEmptyMessageWhenTheAccountHasNoInstalledRacePlugin(): void
    {
        $account = new Account();
        $repository = $this->createMock(PluginRepository::class);
        $repository->expects($this->once())
            ->method('findDataForAccount')
            ->with($account)
            ->willReturn([]);

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getRepository')
            ->with(Plugin::class)
            ->willReturn($repository);

        $provider = new SharedAthleteStatisticsProvider(
            $doctrine,
            new LegacyPluginInstantiator()
        );

        $this->assertSame(
            '<em>' . __('No data available.') . '</em>',
            $provider->getTabForComparisonOfYears($account)
        );
    }
}
