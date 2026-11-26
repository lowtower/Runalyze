<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Goals\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Plugins\Panels\Goals\GoalsTrainingDataProvider;

#[Group('plugin')]
final class GoalsTrainingDataProviderTest extends TestCase
{
    public function testDelegatesAccountSportAndTimezoneNeutralStartTime(): void
    {
        $account = $this->createStub(Account::class);
        $repository = $this->createMock(TrainingRepository::class);

        $repository->expects($this->once())
            ->method('findTrainingTotalsSince')
            ->with($account, 3, strtotime('2026-04-01 00:00:00 UTC'))
            ->willReturn([
                'activity_count' => 2,
                'distance_sum' => 15.75,
            ]);

        $provider = new GoalsTrainingDataProvider($repository);

        $this->assertSame([
            'activity_count' => 2,
            'distance_sum' => 15.75,
        ], $provider->getTotalsSince(
            $account,
            3,
            new DateTimeImmutable('2026-04-01 00:00:00', new DateTimeZone('Europe/Berlin'))
        ));
    }

    public function testRetainsTimeOfDayAndEmptyResult(): void
    {
        $account = $this->createStub(Account::class);
        $repository = $this->createMock(TrainingRepository::class);

        $repository->expects($this->once())
            ->method('findTrainingTotalsSince')
            ->with($account, 7, strtotime('2026-10-25 02:30:00 UTC'))
            ->willReturn([
                'activity_count' => 0,
                'distance_sum' => 0.0,
            ]);

        $provider = new GoalsTrainingDataProvider($repository);

        $this->assertSame([
            'activity_count' => 0,
            'distance_sum' => 0.0,
        ], $provider->getTotalsSince(
            $account,
            7,
            new DateTimeImmutable('2026-10-25 02:30:00', new DateTimeZone('Europe/Berlin'))
        ));
    }
}
