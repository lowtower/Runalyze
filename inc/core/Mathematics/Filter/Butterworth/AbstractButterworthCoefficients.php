<?php

namespace Runalyze\Mathematics\Filter\Butterworth;

abstract class AbstractButterworthCoefficients
{
    /** @var array */
    protected $InputCoefficients = [];

    /** @var array */
    protected $OutputCoefficients = [];

    /**
     * @param float $normalizedFrequency
     */
    public function __construct($normalizedFrequency)
    {
        $this->OutputCoefficients[0] = 1.0;

        $this->calculateCoefficients($normalizedFrequency);
    }

    /**
     * @param float $frequencyRatio
     */
    abstract protected function calculateCoefficients($frequencyRatio);

    /**
     * @return array
     */
    public function getInputCoefficients()
    {
        return $this->InputCoefficients;
    }

    /**
     * @return array
     */
    public function getOutputCoefficients()
    {
        return $this->OutputCoefficients;
    }
}
