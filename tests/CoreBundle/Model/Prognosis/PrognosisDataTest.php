<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Model\Prognosis;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Model\Prognosis\PrognosisData;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

final class PrognosisDataTest extends TestCase
{
    public function testUsesAccountScopedPersonalBestDetailsForEachDistance(): void
    {
        $account = new Account();
        $runningSportId = 1;
        $distances = [5.0, 10.0];

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->once())
            ->method('findTopResults')
            ->with($account, $runningSportId, 2)
            ->willReturn([]);

        $raceResultRepository = $this->createMock(RaceresultRepository::class);
        $raceResultRepository->expects($this->once())
            ->method('findPersonalBestDetailsForDistances')
            ->with($account, $runningSportId, $distances)
            ->willReturn([
                '5.00' => [
                    'activity_id' => 17,
                    'seconds' => 1200.0,
                ],
            ]);

        $data = new PrognosisData(
            $trainingRepository,
            $raceResultRepository,
            $account,
            $runningSportId,
            $distances,
            'vo2max',
            true,
            50.0,
            100
        );

        $this->assertSame(1, $data->numberOfPersonalBests());
        $this->assertEqualsWithDelta(1200.0, $data->rows()[0]['pbSeconds'], PHP_FLOAT_EPSILON);
        $this->assertSame(17, $data->rows()[0]['pbActivityId']);
        $this->assertNull($data->rows()[1]['pbSeconds']);
        $this->assertNull($data->rows()[1]['pbActivityId']);
    }
}
