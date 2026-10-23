<?php

declare(strict_types=1);

namespace Runalyze\Sports\Performance;

use Runalyze\Mathematics\Scale;

/**
 * @see http://fellrnr.com/wiki/Training_Monotony
 */
class Monotony
{
    /**
     * @var float Maximum
     */
    public const float MAX = 10.0;

    /**
     * @var float Minimum
     */
    public const float MIN = 0.0;

    /**
     * @var float Warning
     */
    public const float WARNING = 1.5;

    /**
     * @var float Critical value
     */
    public const float CRITICAL = 2.0;

    /**
     * @var int Number of days
     */
    public const int DAYS = 7;

    protected float $Avg = 0.0;

    protected ?float $Value = null;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        protected array $Trimp,
        protected ?float $MaximumPerDay = null
    ) {}

    public function calculate(): void
    {
        if ($this->Trimp === []) {
            $this->Value = 0;

            return;
        }

        $this->Avg = array_sum($this->Trimp) / self::DAYS;
        $var = 0;

        foreach ($this->Trimp as $trimp) {
            $var += ($trimp - $this->Avg) * ($trimp - $this->Avg);
        }

        $var += (self::DAYS - count($this->Trimp)) * $this->Avg * $this->Avg;

        $var /= self::DAYS;

        $this->Value = ($var == 0) ? self::MAX : $this->Avg / sqrt($var);
    }

    /**
     * @throws \RuntimeException
     */
    public function value(): float
    {
        if ($this->Value === null) {
            throw new \RuntimeException('Monotony has to be calculated first.');
        }

        return max(self::MIN, min($this->Value, self::MAX));
    }

    /**
     * We use the Trimp-average instead of the sum to keep this value comparable.
     */
    public function trainingStrain(): float
    {
        return $this->Avg * self::DAYS * $this->value();
    }

    public function valueAsPercentage(): float
    {
        return new Scale\TwoPartPercental(self::MIN, self::CRITICAL, self::MAX)->transform($this->value());
    }

    /**
     * @throws \RuntimeException
     */
    public function trainingStrainAsPercentage(): float
    {
        if ($this->MaximumPerDay === null) {
            throw new \RuntimeException('Maximum per day needs to be set to get a percentage.');
        }

        return new Scale\Percental(0.0, $this->MaximumPerDay * self::DAYS)->transform($this->trainingStrain());
    }
}
