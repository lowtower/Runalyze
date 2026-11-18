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
use Runalyze\Plugins\Sports\SportsPanelPresenter;
use Runalyze\Util\LocalTime;
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

        $statistics = [
            new SportStatistics(new \DateTime('2026-11-18'), []),
            new SportStatistics(new \DateTime('2026-11-01'), []),
            new SportStatistics(new \DateTime('2026-01-01'), []),
            new SportStatistics(new \DateTime('2020-01-01'), []),
        ];

        $repository = $this->createMock(SportRepository::class);
        $repository->expects($this->exactly(4))
            ->method('getSportStatisticsSince')
            ->willReturnOnConsecutiveCalls(...$statistics);

        $presenter = new SportsPanelPresenter($repository, $clock);

        $data = $presenter->present($account, $installation);

        $this->assertSame($statistics[0], $data['week_statistics']);
        $this->assertSame($statistics[1], $data['month_statistics']);
        $this->assertSame($statistics[2], $data['year_statistics']);
        $this->assertSame($statistics[3], $data['total_statistics']);
    }

    public function testDeclaresItsStableKeyTemplateAndTrainingReloadBehavior(): void
    {
        $clock = $this->createStub(ClockInterface::class);
        $repository = $this->createStub(SportRepository::class);
        $presenter = new SportsPanelPresenter($repository, $clock);

        $this->assertSame('RunalyzePluginPanel_Sports', $presenter->getKey());
        $this->assertSame('@Sports/panel.html.twig', $presenter->getTemplate());
        $this->assertTrue($presenter->shouldReloadAfterTraining());
    }
}
