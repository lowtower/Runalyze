<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleTrimpDataProvider;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class RechenspieleTrimpDataProviderTest extends TestCase
{
    public function testMapsDailyTrimpToModelOffsetsRelativeToTheAccountTimezone(): void
    {
        $account = $this->account(Timezone::BERLIN);
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findDailyTrimpForAccount')
            ->with($account)
            ->willReturn([
                [
                    'day' => '2026-11-18',
                    'trimp' => 10.0,
                ],
                [
                    'day' => '2026-11-19',
                    'trimp' => 50.0,
                ],
                [
                    'day' => '2026-11-20',
                    'trimp' => 30.0,
                ],
            ]);

        $data = $this->provider($repository, '2026-11-19 23:30:00 UTC')
            ->getPerformanceModelData($account);

        $this->assertSame([
            -2 => 10.0,
            -1 => 50.0,
            0 => 30.0,
        ], $data);
    }

    public function testReturnsEmptyModelDataWhenTheAccountHasNoActivities(): void
    {
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('findDailyTrimpForAccount')->willReturn([]);

        $this->assertSame(
            [],
            $this->provider($repository)->getPerformanceModelData($this->account(Timezone::UTC))
        );
    }

    public function testFetchesTheLastSevenAccountLocalDaysForMonotony(): void
    {
        $account = $this->account(Timezone::BERLIN);
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findDailyTrimpForAccount')
            ->with(
                $account,
                strtotime('2026-11-14 00:00:00 UTC'),
                strtotime('2026-11-20 23:59:59 UTC')
            )
            ->willReturn([
                [
                    'day' => '2026-11-14',
                    'trimp' => 40.0,
                ],
                [
                    'day' => '2026-11-19',
                    'trimp' => 25.0,
                ],
            ]);

        $data = $this->provider($repository, '2026-11-19 23:30:00 UTC')
            ->getMonotonyData($account);

        $this->assertSame([40.0, 25.0], $data);
    }

    private function provider(
        TrainingRepository $repository,
        string $now = '2026-11-19 12:00:00 UTC'
    ): RechenspieleTrimpDataProvider {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable($now));

        return new RechenspieleTrimpDataProvider($repository, $clock);
    }

    private function account(int $timezone): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getTimezone')->willReturn($timezone);

        return $account;
    }
}
