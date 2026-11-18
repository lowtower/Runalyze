<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Sports\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Model\Sport\SportStatistics;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Plugins\Sports\SportsPanelPresenter;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class SportsPanelPresenterTest extends TestCase
{
    public function testPresentsStatisticsForAllPeriodsUsingTheCurrentTime(): void
    {
        $account = new Account();
        $installation = new Plugin();
        $now = new DateTimeImmutable('2026-11-18T12:34:56+00:00');

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $today = new DateTimeImmutable('2026-11-18T00:00:00+00:00');
        $expectedTimestamps = [
            $today->modify('-2 days')->getTimestamp(),
            $today->modify('first day of this month')->getTimestamp(),
            $today->setDate(2026, 1, 1)->getTimestamp(),
            null,
        ];

        $configurationList = $this->createStub(RunalyzeConfigurationList::class);
        $configurationList->method('get')->with('general.WEEK_START')->willReturn('1');
        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')->with($account)->willReturn($configurationList);
        $actualTimestamps = [];

        $statistics = [
            new SportStatistics(new \DateTime('2026-11-18'), []),
            new SportStatistics(new \DateTime('2026-11-01'), []),
            new SportStatistics(new \DateTime('2026-01-01'), []),
            new SportStatistics(new \DateTime('2020-01-01'), []),
        ];

        $repository = $this->createMock(SportRepository::class);
        $repository->expects($this->exactly(4))
            ->method('getSportStatisticsSince')
            ->willReturnCallback(function (?int $timestamp, Account $receivedAccount) use (&$actualTimestamps, $account, $statistics): SportStatistics {
                $this->assertSame($account, $receivedAccount);
                $actualTimestamps[] = $timestamp;

                return $statistics[count($actualTimestamps) - 1];
            });

        $presenter = new SportsPanelPresenter($repository, $configurationManager, $clock);

        $data = $presenter->present($account, $installation);

        $this->assertSame($statistics[0], $data['week_statistics']);
        $this->assertSame($statistics[1], $data['month_statistics']);
        $this->assertSame($statistics[2], $data['year_statistics']);
        $this->assertSame($statistics[3], $data['total_statistics']);
        $this->assertSame($expectedTimestamps, $actualTimestamps);
    }

    public function testDeclaresItsStableKeyTemplateAndTrainingReloadBehavior(): void
    {
        $clock = $this->createStub(ClockInterface::class);
        $repository = $this->createStub(SportRepository::class);
        $configurationManager = $this->createStub(ConfigurationManager::class);
        $presenter = new SportsPanelPresenter($repository, $configurationManager, $clock);

        $this->assertSame('RunalyzePluginPanel_Sports', $presenter->getKey());
        $this->assertSame('@Sports/panel.html.twig', $presenter->getTemplate());
        $this->assertTrue($presenter->shouldReloadAfterTraining());
    }

    public function testUsesTheConfiguredSundayAsTheWeekStart(): void
    {
        $account = new Account();
        $now = new DateTimeImmutable('2026-11-18T12:34:56+00:00');

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $configurationList = $this->createStub(RunalyzeConfigurationList::class);
        $configurationList->method('get')->with('general.WEEK_START')->willReturn('0');
        $configurationManager = $this->createStub(ConfigurationManager::class);
        $configurationManager->method('getList')->with($account)->willReturn($configurationList);

        $statistics = new SportStatistics(new \DateTime('2026-11-18'), []);
        $actualTimestamps = [];
        $repository = $this->createMock(SportRepository::class);
        $repository->expects($this->exactly(4))
            ->method('getSportStatisticsSince')
            ->willReturnCallback(function (?int $timestamp) use (&$actualTimestamps, $statistics): SportStatistics {
                $actualTimestamps[] = $timestamp;

                return $statistics;
            });

        $presenter = new SportsPanelPresenter($repository, $configurationManager, $clock);

        $presenter->present($account, new Plugin());

        $this->assertSame((new DateTimeImmutable('2026-11-15T00:00:00+00:00'))->getTimestamp(), $actualTimestamps[0]);
    }
}
