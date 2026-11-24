<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Plugin\Statistics;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\InputBag;

#[Group('plugin')]
final class StatisticsFilterFactoryTest extends TestCase
{
    public function testDateBoundsUseTheAccountsLocalCalendarAtNewYear(): void
    {
        $account = new Account()->setTimezone(Timezone::BERLIN);
        $sports = $this->createStub(SportRepository::class);
        $sports->method('findAllFor')->willReturn([]);
        $training = $this->createStub(TrainingRepository::class);
        $training->method('getStartTime')->willReturn(strtotime('2025-12-31 23:59:59 UTC'));
        $factory = new StatisticsFilterFactory(new MockClock('2026-12-31 23:30:00 UTC'), $sports, $training);

        $currentYear = $factory->fromQuery($account, new InputBag());
        $this->assertSame(-1, $currentYear['sport']);
        $this->assertSame(2027, $currentYear['year']);
        $this->assertSame([-1, 6, 12, 2027, 2026, 2025], array_keys($currentYear['years']));
        $this->assertSame(strtotime('2027-01-01 00:00:00 UTC'), $currentYear['from']);
        $this->assertSame(strtotime('2028-01-01 00:00:00 UTC'), $currentYear['until']);

        $lastSixMonths = $factory->fromQuery($account, new InputBag([
            'jahr' => '6',
            'sport' => '54321',
        ]));
        $this->assertSame(-1, $lastSixMonths['sport']);
        $this->assertSame(strtotime('2026-08-01 00:00:01 UTC'), $lastSixMonths['from']);
        $this->assertNull($lastSixMonths['until']);

        $lastTwelveMonths = $factory->fromQuery($account, new InputBag([
            'jahr' => '12',
        ]));
        $this->assertSame(strtotime('2026-02-01 00:00:01 UTC'), $lastTwelveMonths['from']);
        $this->assertNull($lastTwelveMonths['until']);

        $allYears = $factory->fromQuery($account, new InputBag([
            'jahr' => '-1',
        ]));
        $this->assertNull($allYears['from']);
        $this->assertNull($allYears['until']);
    }
}
