<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\Numerics;

class Bisection
{
    /**
     * @var int
     */
    protected $MaxIterations = 100;

    /**
     * @var float
     */
    protected $Epsilon = 0.01;

    /**
     * @var float
     */
    protected $Current;

    /**
     * @var \Closure
     */
    protected $Function;

    /**
     * @var int
     */
    protected $decreasingFactor;

    /**
     * @param float $Target
     * @param float $Lower
     * @param float $Upper
     */
    public function __construct(
        protected $Target,
        protected $Lower,
        protected $Upper,
        \Closure $function
    ) {
        $this->Function = $function;

        $this->Current = ($this->Lower + $this->Upper) / 2;
        $this->decreasingFactor = ($this->Function->__invoke($this->Lower) > $this->Function->__invoke($this->Upper)) ? -1 : 1;
    }

    /**
     * @param int $iterations
     */
    public function setIterations($iterations): void
    {
        $this->MaxIterations = $iterations;
    }

    /**
     * @param float $epsilon
     */
    public function setEpsilon($epsilon): void
    {
        $this->Epsilon = $epsilon;
    }

    /**
     * @return float
     */
    public function findValue()
    {
        for ($i = 0; $i < $this->MaxIterations; ++$i) {
            $value = $this->Function->__invoke($this->Current);

            if (abs($value - $this->Target) < $this->Epsilon) {
                break;
            }

            if ($this->decreasingFactor * $value > $this->Target * $this->decreasingFactor) {
                $this->Upper = $this->Current;
            } else {
                $this->Lower = $this->Current;
            }

            $this->Current = ($this->Lower + $this->Upper) / 2;
        }

        return $this->Current;
    }
}
