<?php

declare(strict_types=1);

/**
 * This file contains class::Completor
 * @package Runalyze\Model\Activity\Splits
 */

namespace Runalyze\Model\Activity\Splits;

/**
 * Completor for splits: fill missing values from trackdata array
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity\Splits
 */
abstract class Completor
{
    /**
     * Mode: time
     * @var string
     */
    public const MODE_TIME = 'time';

    /**
     * Mode: distance
     * @var string
     */
    public const MODE_DISTANCE = 'distance';

    /**
     * Construct
     */
    public function __construct(
        protected Entity $Splits,
        protected array $Time,
        protected array $Distance
    ) {
    }

    abstract public function mode(): string;

    public function completeSplits(): void
    {
        $totalDistance = 0;
        $totalTime = 0;
        $size = min(count($this->Time), count($this->Distance));
        $i = 0;

        foreach ($this->Splits->asArray() as $split) {
            if ($this->mode() === self::MODE_DISTANCE) {
                $s = $split->time();

                while ($i < $size - 1 && $s > $this->Time[$i] - $totalTime) {
                    $i++;
                }

                $split->setDistance($this->Distance[$i] - $totalDistance);
            } else {
                $dist = $split->distance();

                while ($i < $size - 1 && $dist > $this->Distance[$i] - $totalDistance) {
                    $i++;
                }

                $split->setTime($this->Time[$i] - $totalTime);
            }

            $totalTime = $this->Time[$i];
            $totalDistance = $this->Distance[$i];
        }
    }
}
