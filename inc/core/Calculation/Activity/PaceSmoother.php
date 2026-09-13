<?php

declare(strict_types=1);

/**
 * This file contains class::PaceSmoother
 * @package Runalyze\Calculation\Activity
 */

namespace Runalyze\Calculation\Activity;

use Runalyze\Model\Trackdata;

/**
 * Smooth pace curve
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Activity
 */
class PaceSmoother
{
    /**
     * @var int
     */
    public const MODE_STEP = 0;

    /**
     * @var int
     */
    public const MODE_TIME = 1;

    /**
     * @var int
     */
    public const MODE_DISTANCE = 2;

    protected Trackdata\Entity $Trackdata;

    protected Trackdata\Loop $Loop;

    /**
     * @var float[]|int[]
     */
    protected array $Smoothed = [];

    protected int $Mode = 0;

    protected int|float $StepSize = 1;

    protected bool $KeepArraySize = false;

    /**
     * Smoother
     */
    public function __construct(Trackdata\Entity $trackdata, bool $keepArraySize = false)
    {
        $this->Trackdata = $trackdata;
        $this->Loop = new Trackdata\Loop($trackdata);
        $this->KeepArraySize = $keepArraySize;
    }

    /**
     * Smooth data
     * @param int|float $stepSize integer as steps, float as distance [km], int as time [s]
     */
    public function smooth(int|float $stepSize, int $mode = self::MODE_STEP): array
    {
        $this->reset();
        $this->set($stepSize, $mode);
        $this->runFastSmoothing();

        return $this->result();
    }

    /**
     * Reset internals
     */
    protected function reset(): void
    {
        $this->Smoothed = [];
        $this->Loop->reset();
    }

    /**
     * Set internals
     */
    protected function set(int|float $stepSize, int $mode): void
    {
        if ($stepSize <= 0) {
            throw new \InvalidArgumentException('Step size must be greater than zero.');
        }

        $this->StepSize = $stepSize;
        $this->Mode = $mode;

        if ($mode === self::MODE_STEP) {
            if (! is_int($stepSize)) {
                throw new \InvalidArgumentException('Step size must be an integer in step mode.');
            }

            $this->Loop->setStepSize($stepSize);
        }
    }

    /**
     * @return float[]|int[]
     */
    public function result(): array
    {
        return $this->Smoothed;
    }

    protected function runFastSmoothing()
    {
        switch ($this->Mode) {
            case self::MODE_STEP:
                $this->runFastSmoothingForSteps();
                break;

            case self::MODE_TIME:
                $this->runFastSmoothingForKey(Trackdata\Entity::TIME);
                break;

            case self::MODE_DISTANCE:
                $this->runFastSmoothingForKey(Trackdata\Entity::DISTANCE);
                break;
        }
    }

    /**
     * Run fast smoothing for step size
     *
     * Although this does not look nice and is not the cleanest code,
     * direct access to the arrays is approx. 5-times faster.
     * (0.02s vs 0.11s for an array of 10.000 elements)
     */
    protected function runFastSmoothingForSteps()
    {
        $distance = $this->Trackdata->distance();
        $time = $this->Trackdata->time();
        $lastDist = 0;
        $lastTime = 0;

        foreach ($distance as $i => $dist) {
            if ($i != 0 && $i % $this->StepSize === 0) {
                $pace = $dist - $lastDist > 0 ? round(($time[$i] - $lastTime) / ($dist - $lastDist)) : 0;

                if ($this->KeepArraySize) {
                    for ($j = 0; $j < $this->StepSize; ++$j) {
                        $this->Smoothed[] = $pace;
                    }
                } else {
                    $this->Smoothed[] = $pace;
                }

                $lastDist = $dist;
                $lastTime = $time[$i];
            }
        }

        if ($this->KeepArraySize && ! empty($distance)) {
            $pace = $dist - $lastDist > 0 ? round(($time[$i] - $lastTime) / ($dist - $lastDist)) : 0;

            for ($j = count($this->Smoothed), $num = $this->Trackdata->num(); $j < $num; ++$j) {
                $this->Smoothed[] = $pace;
            }
        }
    }

    protected function runFastSmoothingForKey(string $key): void
    {
        $started = false;

        while (! $this->Loop->isAtEnd()) {
            $previousIndex = $this->Loop->index();

            $this->Loop->move($key, $this->StepSize);

            if (
                ! $this->Loop->isAtEnd()
                && $this->Loop->index() <= $previousIndex
            ) {
                throw new \RuntimeException(sprintf(
                    'Loop did not advance for key "%s" at index %d '
                    . 'with step size %s.',
                    $key,
                    $previousIndex,
                    (string) $this->StepSize
                ));
            }

            $dist = $this->Loop->difference(
                Trackdata\Entity::DISTANCE
            );

            $pace = $dist > 0
                ? round(
                    $this->Loop->difference(
                        Trackdata\Entity::TIME
                    ) / $dist
                )
                : 0;

            if ($this->KeepArraySize) {
                $steps = $this->Loop->currentStepSize();

                if (! $started) {
                    ++$steps;
                    $started = true;
                }

                for ($i = 0; $i < $steps; ++$i) {
                    $this->Smoothed[] = $pace;
                }
            } else {
                $this->Smoothed[] = $pace;
            }
        }
    }
}
