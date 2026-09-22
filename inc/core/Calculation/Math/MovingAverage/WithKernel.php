<?php

/**
 * This file contains class::WithKernel
 * @package Runalyze\Calculation\Math\MovingAverage
 */

namespace Runalyze\Calculation\Math\MovingAverage;

use Runalyze\Calculation\Math\MovingAverage\Kernel\AbstractKernel;

/**
 * Moving average with kernel smoothing
 *
 * @see https://en.wikipedia.org/wiki/Moving_average#Cumulative_moving_average
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage
 */
class WithKernel extends AbstractMovingAverage
{
    protected ?AbstractKernel $Kernel = null;

    public function setKernel(?AbstractKernel $kernel): void
    {
        $this->Kernel = $kernel;
    }

    /**
     * Check that kernel is set
     * @throws \RuntimeException
     */
    protected function checkThatKernelExists()
    {
    }

    /**
     * Calculate if index data is there
     *
     * Y_hat (X_0) = sum(K(X_0, X_i) * Y(X_i)) / sum(K(X_0, X_i))
     *
     * @throws \RuntimeException
     */
    public function calculateWithIndexData(): void
    {
        $this->checkThatKernelExists();

        $this->IndexData[-1] = 0;
        $maxDiff = $this->Kernel->width() / 2;

        for ($i = 0; $i < $this->Length; ++$i) {
            $upper = 0;
            $lower = 0;

            foreach ($this->deltasToRespect($i, $maxDiff) as $deltaIndex => $delta) {
                $kernelFactor = $this->Kernel->at($delta);
                $indexDelta = $this->IndexData[$i + $deltaIndex] - $this->IndexData[$i + $deltaIndex - 1];
                $upper += $kernelFactor * $indexDelta * $this->Data[$i + $deltaIndex];
                $lower += $kernelFactor * $indexDelta;
            }

            if ($lower == 0) {
                $this->MovingAverage[] = $this->Data[$i];
            } else {
                $this->MovingAverage[] = $upper / $lower;
            }
        }
    }

    /**
     * @return array deltaIndex => delta
     */
    protected function deltasToRespect(int $currentIndex, float $maxDiff): array
    {
        $x = $this->IndexData[$currentIndex];
        $deltas = [0];

        $delta = -1;
        while (
            $currentIndex + $delta >= 0 &&
            $x - $this->IndexData[$currentIndex + $delta] <= $maxDiff
        ) {
            $deltas[$delta--] = $this->IndexData[$currentIndex + $delta + 1] - $x;
        }

        $delta = +1;
        while (
            $currentIndex + $delta < $this->Length &&
            $maxDiff >= $this->IndexData[$currentIndex + $delta] - $x
        ) {
            $deltas[$delta++] = $this->IndexData[$currentIndex + $delta - 1] - $x;
        }

        return $deltas;
    }

    /**
     * Calculate if index data is not there
     *
     * Y_hat (X_0) = sum(K(X_0, X_i) * Y(X_i)) / sum(K(X_0, X_i))
     *
     * @throws \RuntimeException
     */
    public function calculateWithoutIndexData(): void
    {
        $this->checkThatKernelExists();

        $linearIndices = $this->linearIndicesFor($this->Kernel->width());
        $kernelFactors = $this->Kernel->valuesAt($linearIndices);
        $y = $this->enlargedData($linearIndices);
        $normalizer = array_sum($kernelFactors);

        for ($i = 0; $i < $this->Length; ++$i) {
            $value = 0;

            foreach ($linearIndices as $j => $index) {
                $value += $kernelFactors[$j] * $y[$i + $index];
            }

            $this->MovingAverage[] = $value / $normalizer;
        }
    }

    protected function enlargedData(array $linearIndices): array
    {
        $prepend = [];
        $append = [];

        foreach ($linearIndices as $index) {
            if ($index < 0) {
                $prepend[$index] = $this->Data[0];
            } elseif ($index > 0) {
                $append[$this->Length - 1 + $index] = $this->Data[$this->Length - 1];
            }
        }

        return $prepend + $this->Data + $append;
    }

    protected function linearIndicesFor(float $width): array
    {
        $width = floor($width);

        if ($width <= 1) {
            return [0.0];
        }

        if ($width % 2 === 1) {
            return range(-($width - 1) / 2, ($width - 1) / 2);
        }

        return range(-$width / 2, $width / 2);
    }
}
