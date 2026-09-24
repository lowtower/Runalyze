<?php

namespace Runalyze\Calculation\Trimp;

use Runalyze\Mathematics\Distribution\EmpiricalDistribution;
use Runalyze\Mathematics\Distribution\TimeSeries;

/**
 * Data collector
 *
 * This data collector builds the appropriate array for a trimp calculator.
 *
 * Example:
 * <code>
 * $Collector = new DataCollector($HeartRateArray);
 * print_r( $Collector->result() );
 * </code>
 * will for example result in
 * <pre>
 * array(
 *  [120] => 15,
 *  [121] => 27,
 *  [122] => 5,
 *  ...
 * )
 * </pre>
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Trimp
 */
class DataCollector
{
    protected array $Histogram;

    /**
     * Construct data collector
     *
     * Duration array may be empty. In that case equalsized steps of 1s are assumed
     *
     * @param array $HeartRate [bpm]
     * @param array $Duration ascending time in [s]
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        protected array $HeartRate,
        protected array $Duration = []
    ) {
        if ($this->HeartRate === []) {
            throw new \InvalidArgumentException('Heart rate array must not be empty.');
        }

        if ($this->Duration !== [] && count($this->HeartRate) !== count($this->Duration)) {
            throw new \InvalidArgumentException('Heart rate and duration array must be of equal size.');
        }

        $this->calculate();
    }

    protected function calculate(): void
    {
        $distribution = $this->Duration === []
            ? new EmpiricalDistribution($this->HeartRate)
            : new TimeSeries($this->HeartRate, $this->Duration);

        $this->Histogram = $distribution->histogram();
    }

    /**
     * @return array histogram
     */
    public function result(): array
    {
        return $this->Histogram;
    }
}
