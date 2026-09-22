<?php

namespace Runalyze\Calculation\Math;

/**
 * Sub-segment maximization
 *
 * Find maximal sub-segments of specified lengths with respect to
 * their sum of some given input data. The length is calculated by
 * an index array.
 *
 * You can use `array_fill(0, count($data), 1)` to use
 * an equidistant index array.
 *
 * To find minimal sub-segments it's possible to apply a strictly
 * monotonous decreasing function to the input data, as long as it
 * stays strictly positive (e.g. f(x) = 1/x).
 *
 * See constructor for more details about the required input data.
 *
 * @package Runalyze\Calculation\Math
 */
class SubSegmentMaximization
{
    protected int $NumData;

    protected int $NumSegmentLengths;

    protected array $StartIndex;

    protected array $CurrentSum;

    protected array $CurrentLength;

    protected array $DataMax;

    protected array $DataMaxFromIndex;

    protected array $DataMaxToIndex;

    protected bool $InterpolateOverlength = true;

    protected int $MinimizationFactor = 1;

    /**
     * Construct sub-segment maximization
     *
     * Remind the following requirements and assumptions:
     *  - input data must be positive
     *  - index data must be strictly positive
     *  - segment lengths must be distinct and strictly positive
     *  - both input and index data are treated as deltas, i.e. time data is expected as e.g. [2, 1, 3, 1, 2, 1, 1, ...]
     *
     * @param array $Data input data that should be maximized (wrt to its sum for a given segment length)
     * @param array $IndexData index data used to select segments of appropriate length
     * @param array $SegmentLengths lengths for segments to look at
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        protected array $Data,
        protected array $IndexData,
        protected array $SegmentLengths
    ) {
        $this->NumData = count($this->Data);
        $this->NumSegmentLengths = count($this->SegmentLengths);

        $this->checkInputData();
    }

    /**
     * Values can be interpolated if segment length does not match exactly.
     *
     * An active interpolation will give a maximum of 400 for a length of 10,
     * if a segment of length 15 and value 600 is found only.
     *
     * This is enabled by default.
     */
    public function disableInterpolationForOverlength(bool $flag = false): void
    {
        $this->InterpolateOverlength = $flag;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function checkInputData()
    {
        if ($this->NumData === 0 || $this->NumSegmentLengths === 0) {
            throw new \InvalidArgumentException('Input data must not be empty.');
        }

        if ($this->NumData !== count($this->IndexData)) {
            throw new \InvalidArgumentException('Input data and index data must be of same size.');
        }
    }

    protected function prepareInternalVariables()
    {
        $this->StartIndex = [];
        $this->DataMax = [];

        for ($t = 0; $t < $this->NumSegmentLengths; ++$t) {
            $this->StartIndex[$t] = 0;
            $this->DataMax[$t] = (-1 * $this->MinimizationFactor) * PHP_INT_MAX;
            $this->CurrentSum[$t] = 0;
            $this->CurrentLength[$t] = 0;
            $this->DataMaxFromIndex[$t] = false;
            $this->DataMaxToIndex[$t] = false;
        }
    }

    public function maximize(): void
    {
        $this->prepareInternalVariables();

        for ($i = 0; $i < $this->NumData; ++$i) {
            $deltaData = $this->Data[$i];
            $deltaLength = $this->IndexData[$i];

            for ($t = 0; $t < $this->NumSegmentLengths; ++$t) {
                $this->CurrentSum[$t] += $deltaData;
                $this->CurrentLength[$t] += $deltaLength;

                if ($this->CurrentLength[$t] >= $this->SegmentLengths[$t]) {
                    $overlengthFactor = $this->InterpolateOverlength ? $this->SegmentLengths[$t] / $this->CurrentLength[$t] : 1;

                    if ($this->CurrentSum[$t] * $overlengthFactor * $this->MinimizationFactor > $this->DataMax[$t] * $this->MinimizationFactor) {
                        $this->DataMax[$t] = $this->CurrentSum[$t] * $overlengthFactor;
                        $this->DataMaxFromIndex[$t] = $this->StartIndex[$t];
                        $this->DataMaxToIndex[$t] = $i;
                    }

                    do {
                        $this->CurrentSum[$t] -= $this->Data[$this->StartIndex[$t]];
                        $this->CurrentLength[$t] -= $this->IndexData[$this->StartIndex[$t]];
                        $this->StartIndex[$t]++;
                    } while ($this->CurrentLength[$t] >= $this->SegmentLengths[$t]);
                }
            }
        }
    }

    public function minimize(): void
    {
        $this->MinimizationFactor = -1;

        $this->maximize();

        $this->MinimizationFactor = 1;
    }

    public function hasMaximumForLengthIndex(int $t): bool
    {
        return abs($this->DataMax[$t]) !== PHP_INT_MAX;
    }

    public function getMaximumForLengthIndex(int $t): float
    {
        return $this->DataMax[$t];
    }

    /**
     * @return float[] [from, to]
     */
    public function getIndizesOfMaximumForLengthIndex(int $t): array
    {
        return [$this->DataMaxFromIndex[$t], $this->DataMaxToIndex[$t]];
    }

    public function getAvailableSegmentLengths(): array
    {
        $lengths = [];

        foreach ($this->SegmentLengths as $index => $length) {
            if ($this->hasMaximumForLengthIndex($index)) {
                $lengths[] = $length;
            }
        }

        return $lengths;
    }
}
