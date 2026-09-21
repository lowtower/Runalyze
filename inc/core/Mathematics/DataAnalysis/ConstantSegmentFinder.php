<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\DataAnalysis;

class ConstantSegmentFinder
{
    protected int $DataSize;

    protected array $IndexData;

    protected float $ConstantDelta = 0.0;

    protected float $MinimumIndexDiff = 1.0;

    protected ?float $MaximumIndexDiff = null;

    public function __construct(
        protected array $Data,
        array $indexData = []
    ) {
        $this->DataSize = count($this->Data);
        $this->IndexData = $indexData === [] && $this->DataSize > 0 ? range(0, $this->DataSize - 1) : $indexData;

        if (count($this->IndexData) !== $this->DataSize) {
            throw new \InvalidArgumentException('Size of $indexData must match size of $data.');
        }
    }

    public function setConstantDelta(float $delta): void
    {
        $this->ConstantDelta = $delta;
    }

    public function setMinimumIndexDiff(float $length): void
    {
        $this->MinimumIndexDiff = $length;
    }

    public function setMaximumIndexDiff(?float $length): void
    {
        $this->MaximumIndexDiff = $length;
    }

    /**
     * @return array[] [index_start, index_end] for each constant segment
     */
    public function findConstantSegments(): array
    {
        if ($this->DataSize === 0) {
            return [];
        }

        $i = 0;
        $segments = [];
        $currentStartIndex = 0;
        $currentMin = $this->Data[0];
        $currentMax = $this->Data[0];

        while ($i < $this->DataSize) {
            $value = $this->Data[$i];

            if ($i === $currentStartIndex) {
                ++$i;
                continue;
            }

            $maxIndexDiffIsReached = $this->MaximumIndexDiff !== null && $this->MaximumIndexDiff <= $this->IndexData[$i - 1] - $this->IndexData[$currentStartIndex];

            if ($maxIndexDiffIsReached || $value > $currentMin + $this->ConstantDelta || $value < $currentMax - $this->ConstantDelta) {
                if ($maxIndexDiffIsReached || $this->MinimumIndexDiff <= $this->IndexData[$i - 1] - $this->IndexData[$currentStartIndex]) {
                    $segments[] = [$currentStartIndex, $i - 1];
                } else {
                    $i = $currentStartIndex + 1;
                }

                $currentStartIndex = $i;
                $currentMin = $this->Data[$i];
                $currentMax = $this->Data[$i];
            } else {
                $currentMin = $value < $currentMin ? $value : $currentMin;
                $currentMax = $value > $currentMax ? $value : $currentMax;
                ++$i;
            }
        }

        if ($currentStartIndex < $this->DataSize - 1 && $this->MinimumIndexDiff <= $this->IndexData[$this->DataSize - 1] - $this->IndexData[$currentStartIndex]) {
            $segments[] = [$currentStartIndex, $this->DataSize - 1];
        }

        return $segments;
    }
}
