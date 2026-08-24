<?php

namespace Runalyze\Service\ElevationCorrection;

class StepwiseElevationProfileFixer
{
    /**
     * @var int
     */
    public const METHOD_FIXED_GROUP_SIZE = 0;

    /**
     * @var int
     */
    public const METHOD_VARIABLE_GROUP_SIZE = 1;

    /**
     * @var int
     */
    protected $Method;

    /**
     * @var int
     */
    protected $NumberOfGroupedPoints;

    /**
     * @var int
     */
    protected $MidpointIndexOfConstantGroup;

    public function __construct($numberOfGroupedPoints = 5, $method = self::METHOD_FIXED_GROUP_SIZE)
    {
        $this->NumberOfGroupedPoints = $numberOfGroupedPoints;
        $this->MidpointIndexOfConstantGroup = (int) floor(($numberOfGroupedPoints - 1) / 2);
        $this->Method = $method;
    }

    /**
     * Smooth elevation profile if it's composed of constant groups
     *
     * @return array
     */
    public function fixStepwiseElevations(?array $elevations = null, array $distances = [])
    {
        if ($this->isProfileStepwise($elevations)) {
            $numElevations = count($elevations);

            if ($distances === []) {
                $distances = range(0.0, $numElevations - 1.0);
            }

            if ($this->Method == self::METHOD_FIXED_GROUP_SIZE) {
                $midpoints = $this->getMidpointsForFixedGroupSize($numElevations);
            } else {
                $midpoints = $this->findMidpointsForConstantParts($elevations);
            }

            $elevations = $this->fixStepwiseElevationsForMidpoints($elevations, $distances, $midpoints);
        }

        return $elevations;
    }

    /**
     * @param int $arraySize
     */
    protected function getMidpointsForFixedGroupSize($arraySize): array
    {
        if ($this->NumberOfGroupedPoints >= $arraySize) {
            return [$this->MidpointIndexOfConstantGroup];
        }

        return range($this->MidpointIndexOfConstantGroup, $arraySize - 1, $this->NumberOfGroupedPoints);
    }

    /**
     * @param array<int, mixed> $elevations
     * @return float[]
     */
    protected function findMidpointsForConstantParts(array $elevations): array
    {
        $midpoints = [];
        $lastDiffIndex = -1;
        $numElevations = count($elevations);
        $currentElevation = $elevations[0];

        for ($i = 1; $i < $numElevations - 1; ++$i) {
            if ($elevations[$i] != $currentElevation) {
                $midpoints[] = floor(($lastDiffIndex + $i) / 2);

                $lastDiffIndex = $i - 1;
                $currentElevation = $elevations[$i];
            }
        }

        if ($elevations[$numElevations - 1] == $elevations[$numElevations - 2]) {
            $midpoints[] = floor(($lastDiffIndex + $numElevations - 1) / 2);
        }

        return $midpoints;
    }

    /**
     * @param array<int, mixed> $midpoints
     * @return array
     */
    protected function fixStepwiseElevationsForMidpoints(array $elevations, array $distances, array $midpoints)
    {
        $numMidpoints = count($midpoints);

        for ($i = 0; $i < $numMidpoints - 1; ++$i) {
            for ($j = $midpoints[$i] + 1; $j < $midpoints[$i + 1]; ++$j) {
                if ($distances[$midpoints[$i + 1]] != $distances[$midpoints[$i]]) {
                    $partial = ($distances[$j] - $distances[$midpoints[$i]]) / ($distances[$midpoints[$i + 1]] - $distances[$midpoints[$i]]);
                    $elevations[$j] = round((1.0 - $partial) * $elevations[$midpoints[$i]] + $partial * $elevations[$midpoints[$i + 1]]);
                }
            }
        }

        return $elevations;
    }

    /**
     * @param array<int, mixed> $elevations
     * @return bool
     */
    public function isProfileStepwise(array $elevations)
    {
        $num = count($elevations);

        for ($i = 1; $i < $num; ++$i) {
            if ($i % $this->NumberOfGroupedPoints !== 0 && $elevations[$i] != $elevations[$i - 1]) {
                return false;
            }
        }

        return $num > $this->NumberOfGroupedPoints;
    }
}
