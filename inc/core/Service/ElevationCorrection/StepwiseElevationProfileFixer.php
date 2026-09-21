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

    protected int $MidpointIndexOfConstantGroup;

    public function __construct(
        protected int $NumberOfGroupedPoints = 5,
        protected int $Method = self::METHOD_FIXED_GROUP_SIZE
    ) {
        $this->MidpointIndexOfConstantGroup = (int) floor(($this->NumberOfGroupedPoints - 1) / 2);
    }

    /**
     * Smooth elevation profile if it's composed of constant groups
     */
    public function fixStepwiseElevations(?array $elevations = null, array $distances = []): ?array
    {
        if ($this->isProfileStepwise($elevations)) {
            $numElevations = count($elevations);

            if ($distances === []) {
                $distances = range(0.0, $numElevations - 1.0);
            }

            if ($this->Method === self::METHOD_FIXED_GROUP_SIZE) {
                $midpoints = $this->getMidpointsForFixedGroupSize($numElevations);
            } else {
                $midpoints = $this->findMidpointsForConstantParts($elevations);
            }

            $elevations = $this->fixStepwiseElevationsForMidpoints($elevations, $distances, $midpoints);
        }

        return $elevations;
    }

    protected function getMidpointsForFixedGroupSize(int $arraySize): array
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
     */
    protected function fixStepwiseElevationsForMidpoints(array $elevations, array $distances, array $midpoints): array
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
     */
    public function isProfileStepwise(array $elevations): bool
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
