<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\Filter;

/**
 * IIR filter
 *
 * y[n] = 1/a0 * (b0 * x[n] + b1 * x[n-1] + ... + bP * x[n-P]
 *                          - a1 * y[n-1] - ... - aQ * y[n-Q])
 *
 * For this implementation, P and Q are assumed to be equal.
 *
 * @see https://en.wikipedia.org/wiki/Infinite_impulse_response
 */
class InfiniteImpulseResponseFilter
{
    protected int $Order = 0;

    public function __construct(
        protected array $InputCoefficients,
        protected array $OutputCoefficients
    ) {
        $this->Order = count($this->InputCoefficients) - 1;

        if (count($this->OutputCoefficients) - 1 !== $this->Order) {
            throw new \InvalidArgumentException('Number of input/output coefficients must be equal.');
        }
    }

    /**
     * @param array $inputData 0-index numerical array
     * @param bool $returnEnlargedData set to true to return an array of size [n + 2*order], starting at [-order]
     * @return array remember: applying the filter only once causes a shift of the original signal
     */
    public function filter(array $inputData, $returnEnlargedData = false): array
    {
        $signalLength = count($inputData);
        $outputData = array_fill(0, $signalLength, 0);

        $this->enlargeData($inputData, $outputData, $signalLength);
        $this->applyFilter($inputData, $outputData, $signalLength + $this->Order);

        if ($returnEnlargedData) {
            return $outputData;
        }

        return array_slice($outputData, $this->Order, -$this->Order);
    }

    /**
     * @param array $inputData 0-index numerical array
     * @return array signal filtered forwards as well as backwards
     */
    public function filterFilter(array $inputData): array
    {
        $filteredData = array_reverse(
            $this->filter(
                array_reverse($this->filter($inputData, true))
            )
        );

        return array_slice($filteredData, $this->Order, -$this->Order);
    }

    /**
     * @param array<int, mixed> $inputData 0-index numerical array
     * @param array<int, int> $outputData 0-index numerical array
     * @param-out array<int, mixed> $outputData
     * @param int $currentSize must equal size of $inputData and $outputData
     */
    protected function enlargeData(array &$inputData, array &$outputData, $currentSize)
    {
        $firstElement = $inputData[0];
        $lastElement = $inputData[$currentSize - 1];

        for ($i = 1; $i <= $this->Order; ++$i) {
            array_unshift($inputData, $firstElement);
            array_unshift($outputData, $firstElement);
            $inputData[] = $lastElement;
            $outputData[] = null;
        }
    }

    /**
     * @param array<int, mixed> $inputData numerical array, must  be of size [order + $lengh]
     * @param array<int, mixed> $outputData numerical array, must be of size [order + $length]
     * @param int $length number of points to filter (in general [signalLength + order])
     */
    protected function applyFilter(array &$inputData, array &$outputData, $length)
    {
        $length += $this->Order;

        for ($n = $this->Order; $n < $length; ++$n) {
            $outputData[$n] = $this->InputCoefficients[0] * $inputData[$n];

            for ($i = 1; $i <= $this->Order; ++$i) {
                $outputData[$n] +=
                    $this->InputCoefficients[$i] * $inputData[$n - $i]
                    - $this->OutputCoefficients[$i] * $outputData[$n - $i];
            }

            $outputData[$n] /= $this->OutputCoefficients[0];
        }
    }
}
