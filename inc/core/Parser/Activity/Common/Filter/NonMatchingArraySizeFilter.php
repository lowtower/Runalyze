<?php

namespace Runalyze\Parser\Activity\Common\Filter;

use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Runalyze\Parser\Activity\Common\Data\ContinuousData;
use Runalyze\Parser\Activity\Common\Exception\InvalidDataException;

class NonMatchingArraySizeFilter extends AbstractFilter
{
    /**
     * @var int
     */
    public const MIN_MAX_TOLERANCE = 2;

    public function filter(ActivityDataContainer $container, bool $strict = false)
    {
        $nonEmptyKeys = $this->getNonEmptyContinuousDataKeys($container->ContinuousData);

        if ($nonEmptyKeys === []) {
            return;
        }

        $arraySizes = $this->getArraySizes($nonEmptyKeys, $container->ContinuousData);

        if (! $this->areAllElementsEqual($arraySizes)) {
            if ($strict) {
                $this->throwErrorForNonMatchingArraySizes($arraySizes);
            }

            $this->tryToHandleNonMatchingArraySizes($arraySizes, $container->ContinuousData);
        }
    }

    /**
     * @param int[] $arraySizes
     */
    protected function throwErrorForNonMatchingArraySizes(array $arraySizes)
    {
        throw new InvalidDataException($this->getErrorOrLogMessage($arraySizes, false));
    }

    /**
     * @param int[] $arraySizes
     */
    protected function logFixedArraySizes(array $arraySizes)
    {
        $this->logger->warning($this->getErrorOrLogMessage($arraySizes, true));
    }

    /**
     * @param int[] $arraySizes
     * @param bool $sizesHaveBeenFixed
     */
    protected function getErrorOrLogMessage(array $arraySizes, $sizesHaveBeenFixed = false): string
    {
        return sprintf(
            '%s array sizes %s (%s).',
            $sizesHaveBeenFixed ? 'Non-matching' : 'Unrecoverable non-matching',
            $sizesHaveBeenFixed ? 'fixed' : 'detected',
            implode(', ', array_map(fn ($key, int $size): string => $key . ': ' . $size, array_keys($arraySizes), $arraySizes))
        );
    }

    /**
     * @return string[] keys
     */
    protected function getNonEmptyContinuousDataKeys(ContinuousData $continuousData): array
    {
        $keys = $continuousData->getPropertyNamesOfArrays();

        foreach ($keys as $i => $key) {
            if (empty($continuousData->{$key})) {
                unset($keys[$i]);
            }
        }

        return $keys;
    }

    /**
     * @param string[] $keys
     * @return int[]
     */
    protected function getArraySizes(array $keys, ContinuousData $continuousData): array
    {
        $sizes = [];

        foreach ($keys as $key) {
            $sizes[$key] = count($continuousData->{$key});
        }

        return $sizes;
    }

    /**
     * @param int[] $data
     */
    protected function areAllElementsEqual(array $data): bool
    {
        return count(array_unique($data)) === 1;
    }

    /**
     * @param int[] $arraySizes
     */
    protected function tryToHandleNonMatchingArraySizes(array $arraySizes, ContinuousData $continuousData)
    {
        $minimalSize = min($arraySizes);
        $maximalSize = max($arraySizes);

        if ($minimalSize >= $maximalSize - self::MIN_MAX_TOLERANCE) {
            $this->reduceArrays($minimalSize, $arraySizes, $continuousData);
        } else {
            $this->throwErrorForNonMatchingArraySizes($arraySizes);
        }
    }

    /**
     * @param int[] $arraySizes
     */
    protected function reduceArrays(int $newSize, array $arraySizes, ContinuousData $continuousData)
    {
        foreach ($arraySizes as $key => $size) {
            if ($size != $newSize) {
                $continuousData->{(string) $key} = array_slice($continuousData->{(string) $key}, 0, $newSize);
            }
        }

        $this->logFixedArraySizes($arraySizes);
    }
}
