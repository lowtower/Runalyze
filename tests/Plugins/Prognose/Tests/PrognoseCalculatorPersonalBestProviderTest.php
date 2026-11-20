<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPersonalBestProvider;

#[Group('plugin')]
final class PrognoseCalculatorPersonalBestProviderTest extends TestCase
{
    public function testReturnsPersonalBestsForCustomDistancesWithActivityTimestamps(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn(12);

        $races = $this->createMock(RaceresultRepository::class);
        $races->expects($this->once())
            ->method('findPersonalBestDetailsForDistances')
            ->with($account, 3, [5.72, 8.9])
            ->willReturn([
                '5.72' => [
                    'activity_id' => 51,
                    'seconds' => 2064.0,
                ],
                '8.90' => [
                    'activity_id' => 89,
                    'seconds' => 3196.0,
                ],
            ]);

        $first = $this->createStub(Training::class);
        $first->method('getTime')->willReturn(1700000000);
        $second = $this->createStub(Training::class);
        $second->method('getTime')->willReturn(1710000000);

        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects($this->exactly(2))
            ->method('findForAccount')
            ->willReturnCallback(static function (int $activityId, int $accountId) use ($first, $second): ?Training {
                self::assertSame(12, $accountId);

                return match ($activityId) {
                    51 => $first,
                    89 => $second,
                    default => null,
                };
            });

        $provider = new PrognoseCalculatorPersonalBestProvider($races, $trainings);

        $this->assertSame([
            '5.72' => [
                'seconds' => 2064.0,
                'activity_id' => 51,
                'timestamp' => 1700000000,
            ],
            '8.90' => [
                'seconds' => 3196.0,
                'activity_id' => 89,
                'timestamp' => 1710000000,
            ],
        ], $provider->findForDistances($account, 3, [5.72, 8.9]));
    }

    public function testSkipsResultsWhoseActivityIsUnavailableForAccount(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn(12);

        $races = $this->createMock(RaceresultRepository::class);
        $races->expects($this->once())->method('findPersonalBestDetailsForDistances')
            ->willReturn([
                '5.72' => [
                    'activity_id' => 51,
                    'seconds' => 2064.0,
                ],
            ]);

        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects($this->once())
            ->method('findForAccount')
            ->with(51, 12)
            ->willReturn(null);

        $provider = new PrognoseCalculatorPersonalBestProvider($races, $trainings);

        $this->assertSame([], $provider->findForDistances($account, 3, [5.72]));
    }

    public function testUnpersistedAccountDoesNotReadAnyPersonalBests(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn(null);

        $races = $this->createMock(RaceresultRepository::class);
        $races->expects($this->never())->method('findPersonalBestDetailsForDistances');

        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects($this->never())->method('findForAccount');

        $provider = new PrognoseCalculatorPersonalBestProvider($races, $trainings);

        $this->assertSame([], $provider->findForDistances($account, 3, [5.72]));
    }
}
