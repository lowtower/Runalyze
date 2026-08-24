<?php

namespace Runalyze\Sports\ClimbScore;

/**
 * Fiets-Index based Climb Score
 *
 * This climb score is calculated as sum of all fiets indices for relevant climbs (i.e. gradient >= 0.02),
 * weighted by the course's total distance and the course's percentage of flat vs. hilly and finally
 * logarithmically scaled to a scale from 0.0 to 10.0.
 *
 * CS = 2.0 * log2(0.5 + (1.0 + S) * (1 - p^2))
 *      with S = SUM(Fiets | gradient > 0.02) / max(1.0, sqrt(distance [km] / 20))
 *       and p = percentage flat
 */
class ClimbScore
{
    /**
     * @var float|null
     */
    protected $Score;

    public function __construct($score = null)
    {
        $this->setScore($score);
    }

    /**
     * @param float|null $score
     */
    public function setScore($score): self
    {
        $this->Score = $score;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getScore()
    {
        return $this->Score;
    }

    public function isKnown(): bool
    {
        return $this->Score !== null;
    }

    /**
     * @param float[] $fietsIndices fiets indices for climbs (assuming gradient >= 0.02)
     * @param float $totalDistance [km]
     * @param float $percentageFlat [0.00 .. 1.00]
     */
    public function setScoreFromClassifiedClimbs(array $fietsIndices, $totalDistance, $percentageFlat): self
    {
        $this->Score = $this->getScoreForSumOfSingleScores(
            $this->getSumOfScoresForClassifiedClimbs($fietsIndices, $totalDistance),
            $percentageFlat
        );

        return $this;
    }

    /**
     * @param float[] $fietsIndices fiets indices for climbs (assuming gradient >= 2.0)
     * @param float $totalDistance [km]
     * @return float
     */
    public function getSumOfScoresForClassifiedClimbs(array $fietsIndices, $totalDistance)
    {
        return array_sum($fietsIndices) / max(1.0, sqrt($totalDistance / 20));
    }

    /**
     * @param float $sumOfSingleScores
     * @param float $percentageFlat [0.00 .. 1.00]
     */
    public function getScoreForSumOfSingleScores($sumOfSingleScores, $percentageFlat = 0.0): float
    {
        // TODO: Think about stretching the part between 0.0 and 1.0

        return min(10.0, max(0.0, 2.0 * log(0.5 + (1.0 + $sumOfSingleScores) * $this->getCompensationForFlatParts($percentageFlat), 2.0)));
    }

    /**
     * @param float $percentageFlat [0.00 .. 1.00]
     */
    public function getCompensationForFlatParts($percentageFlat): float
    {
        return min(1.0, max(0.0, 1.0 - $percentageFlat * $percentageFlat));
    }
}
