<?php

namespace Runalyze\Mathematics\Distribution;

/**
 * Abstract distribution
 *
 * General class that can hold various statistics for any distribution,
 * especially useful for empirical distributions and their statistical properties.
 */
abstract class AbstractDistribution
{
    /**
     * @var string
     */
    public const NUM = 'num';

    /**
     * @var string
     */
    public const MIN = 'min';

    /**
     * @var string
     */
    public const MAX = 'max';

    /**
     * @var string
     */
    public const MEAN = 'mean';

    /**
     * @var string
     */
    public const MEDIAN = 'median';

    /**
     * @var string
     */
    public const QUANTILES = 'quantiles';

    /**
     * @var string
     */
    public const MODE = 'mode';

    /**
     * @var string
     */
    public const VARIANCE = 'var';

    /**
     * @var array<string, int|float>
     */
    private array $Statistic = [
        'num' => 0,
        'min' => 0,
        'max' => 0,
        'mean' => 0,
        'median' => 0,
        'mode' => 0,
        'var' => 0,
    ];

    /**
     * @var array<string, float>
     */
    private array $Quantiles = [];

    /**
     * Histogram data
     */
    abstract public function histogram(): array;

    /**
     * @param float[] $quantiles
     */
    public function calculateStatistic(array $quantiles = [])
    {
        $this->calculateStatisticByHistogram($quantiles);
    }

    /**
     * @param float[] $quantiles
     */
    final protected function calculateStatisticByHistogram(array $quantiles)
    {
        $sortedData = $this->histogram();

        if ($sortedData === []) {
            return;
        }

        ksort($sortedData);

        $this->calculateMinAndMax($sortedData);
        $this->calculateMeanAndMode($sortedData);
        $this->calculateQuantilesAndVariance($sortedData, $quantiles);
    }

    private function calculateMinAndMax(array $sortedData)
    {
        $keys = array_keys($sortedData);

        $this->setStatistic(self::MIN, $keys[0]);
        $this->setStatistic(self::MAX, end($keys));
    }

    private function calculateMeanAndMode(array $sortedData)
    {
        $sum = 0;
        $num = 0;
        $maxCount = 0;
        $mode = 0;

        foreach ($sortedData as $value => $count) {
            $sum += $value * $count;
            $num += $count;

            if ($count > $maxCount) {
                $maxCount = $count;
                $mode = $value;
            }
        }

        $mean = $sum / $num;
        $this->setStatistic(self::NUM, $num);
        $this->setStatistic(self::MEAN, $mean);
        $this->setStatistic(self::MODE, $mode);
    }

    /**
     * @param float[] $quantiles
     */
    private function calculateQuantilesAndVariance(array $sortedData, array $quantiles)
    {
        ksort($quantiles);

        $num = $this->Statistic[self::NUM];
        $currentQuantilesIndex = 0;
        $currentQuantile = array_shift($quantiles);
        $desiredQuantileIndex = $currentQuantile !== null ? $currentQuantile * $num : $num + 1;
        $desiredMedianIndex = $num / 2;

        $mean = $this->Statistic[self::MEAN];
        $median = false;
        $var = 0;

        foreach ($sortedData as $value => $count) {
            $var += $count * ($value - $mean) * ($value - $mean);
            $currentQuantilesIndex += $count;

            if ($median === false && $currentQuantilesIndex >= $desiredMedianIndex) {
                $median = $value;
            }

            while ($currentQuantile !== null && $currentQuantilesIndex >= $desiredQuantileIndex) {
                $this->Quantiles['p' . $currentQuantile] = $value;

                $currentQuantile = array_shift($quantiles);
                $desiredQuantileIndex = $currentQuantile !== null ? $currentQuantile * $num : $num + 1;
            }
        }

        $this->setStatistic(self::MEDIAN, $median);
        $this->setStatistic(self::VARIANCE, $var / $this->Statistic[self::NUM]);
    }

    final protected function setStatistic(string $key, float $value)
    {
        $this->Statistic[$key] = $value;
    }

    final public function min(): float
    {
        return $this->Statistic[self::MIN];
    }

    final public function max(): float
    {
        return $this->Statistic[self::MAX];
    }

    final public function mean(): float
    {
        return $this->Statistic[self::MEAN];
    }

    final public function median(): float
    {
        return $this->Statistic[self::MEDIAN];
    }

    /**
     * @throws \InvalidArgumentException
     */
    final public function quantile(float $alpha): float
    {
        if (! isset($this->Quantiles['p' . $alpha])) {
            throw new \InvalidArgumentException('No quantile calculated for alpha = ' . $alpha);
        }

        return $this->Quantiles['p' . $alpha];
    }

    final public function mode(): float
    {
        return $this->Statistic[self::MODE];
    }

    final public function variance(): float
    {
        return $this->Statistic[self::VARIANCE];
    }

    final public function stdDev(): float
    {
        return sqrt($this->Statistic[self::VARIANCE]);
    }

    /**
     * @return bool|float boolean false is returned if mean is zero
     */
    final public function coefficientOfVariation(): bool|float
    {
        if ($this->Statistic[self::MEAN] == 0) {
            return false;
        }

        return sqrt($this->Statistic[self::VARIANCE]) / $this->Statistic[self::MEAN];
    }
}
