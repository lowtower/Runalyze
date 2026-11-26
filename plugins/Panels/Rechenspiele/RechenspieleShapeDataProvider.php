<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;

/**
 * Connects account-scoped shape history to the daily plot model inputs.
 */
final readonly class RechenspieleShapeDataProvider
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private RechenspieleShapeDailyAggregator $aggregator
    ) {}

    /**
     * @return array{
     *     trimp: list<float>,
     *     distance: list<float>,
     *     vo2max_weighted: list<float>,
     *     vo2max_sum_time: list<float>,
     *     longjogs: list<list<float>>
     * }
     *
     * @throws InvalidArgumentException if the date range or warmup is invalid
     */
    public function getDailyData(
        Account $account,
        int $runningSportId,
        DateTimeImmutable $startDay,
        DateTimeImmutable $endDay,
        int $warmupDays,
        float $minimalLongJogDistance,
        bool $withElevation
    ): array {
        $timezone = new DateTimeZone(TimezoneEnum::getFullNameByEnum($account->getTimezone()));
        $start = new DateTimeImmutable($startDay->format('Y-m-d'), $timezone);
        $end = new DateTimeImmutable($endDay->format('Y-m-d'), $timezone);

        if ($end < $start || $warmupDays < 0) {
            throw new InvalidArgumentException('The shape plot date range and warmup must be valid.');
        }

        $numberOfDays = (int) $start->diff($end)->format('%a');
        $fromTimestamp = $start->modify('-' . $warmupDays . ' days')->getTimestamp();
        $toTimestamp = $end->setTime(23, 59, 59)->getTimestamp();

        $activities = $this->trainingRepository->findShapePlotTrainingData(
            $account,
            $runningSportId,
            $fromTimestamp,
            $toTimestamp,
            $withElevation,
            $timezone
        );

        return $this->aggregator->aggregate(
            $activities,
            $start,
            $warmupDays,
            $numberOfDays,
            $minimalLongJogDistance
        );
    }
}
