<?php

namespace Runalyze\Mathematics\PointReduction;

/**
 * @see https://en.wikipedia.org/wiki/Ramer%E2%80%93Douglas%E2%80%93Peucker_algorithm
 * @see http://karthaus.nl/rdp/
 */
class RamerDouglasPeucker extends AbstractPointReductionAlgorithm
{
    public function __construct(
        array $x,
        array $y,
        protected float $Epsilon
    ) {
        if ($x === []) {
            throw new \InvalidArgumentException(
                'Arrays must not be empty.'
            );
        }

        if (count($x) !== count($y)) {
            throw new \InvalidArgumentException('Arrays must be of same size.');
        }

        $this->ReducedIndices = [0, count($x) - 1];
        [$this->ReducedX, $this->ReducedY] = $this->reducePointSet($x, $y);

        sort($this->ReducedIndices);
    }

    /**
     * @param array<int, mixed> $x
     * @param array<int, mixed> $y
     * @return array [reduced_x[], reduced_y[]]
     */
    protected function reducePointSet(array $x, array $y, int $offset = 0): array
    {
        $num = count($x);

        [$maxDist, $index] = $this->findMaximalDistanceAndIndexInPointSet($x, $y);

        if ($maxDist > $this->Epsilon) {
            $this->ReducedIndices[] = $offset + $index;

            return $this->splitAndReducePointSet($x, $y, $index, $offset);
        }

        return [
            [$x[0], $x[$num - 1]],
            [$y[0], $y[$num - 1]],
        ];
    }

    /**
     * @param array<int, mixed> $x
     * @param array<int, mixed> $y
     * @return array<int, float|int> [max dist, index]
     */
    protected function findMaximalDistanceAndIndexInPointSet(array $x, array $y): array
    {
        $maxDist = 0;
        $index = 0;
        $num = count($x);

        for ($i = 1; $i < $num - 1; $i++) {
            $dist = self::shortestDistance($x[$i], $y[$i], $x[0], $y[0], $x[$num - 1], $y[$num - 1]);

            if ($dist > $maxDist) {
                $index = $i;
                $maxDist = $dist;
            }
        }

        return [$maxDist, $index];
    }

    /**
     * @return array<int, mixed[]> [x_reduced[], y_reduced[]]
     */
    protected function splitAndReducePointSet(array $x, array $y, int $index, int $offset): array
    {
        [$firstX, $firstY] = $this->reducePointSet(
            array_slice($x, 0, $index + 1),
            array_slice($y, 0, $index + 1),
            $offset
        );
        [$secondX, $secondY] = $this->reducePointSet(
            array_slice($x, $index),
            array_slice($y, $index),
            $offset + $index
        );

        array_pop($firstX);
        array_pop($firstY);

        return [
            array_merge($firstX, $secondX),
            array_merge($firstY, $secondY),
        ];
    }
}
