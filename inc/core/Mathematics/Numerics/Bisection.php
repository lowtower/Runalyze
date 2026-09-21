<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\Numerics;

class Bisection
{
    protected int $MaxIterations = 100;

    protected float $Epsilon = 0.01;

    protected float $Current;

    protected int $decreasingFactor;

    public function __construct(
        protected float $Target,
        protected float $Lower,
        protected float $Upper,
        protected \Closure $Function
    ) {
        $this->Current = ($this->Lower + $this->Upper) / 2;
        $this->decreasingFactor = ($this->Function->__invoke($this->Lower) > $this->Function->__invoke($this->Upper)) ? -1 : 1;
    }

    public function setIterations(int $iterations): void
    {
        $this->MaxIterations = $iterations;
    }

    public function setEpsilon(float $epsilon): void
    {
        $this->Epsilon = $epsilon;
    }

    public function findValue(): float
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
