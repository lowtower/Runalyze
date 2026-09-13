<?php

namespace Runalyze\Calculation\Route;

use Runalyze\Calculation\Math\MovingAverage\Kernel\AbstractKernel;
use Runalyze\Calculation\Math\MovingAverage\WithKernel;
use Runalyze\Mathematics\Numerics\Derivative;
use Runalyze\Model;

class Gradient
{
    protected array $Elevation = [];

    protected array $Distance = [];

    protected array $Gradient = [];

    protected ?AbstractKernel $MovingAverageKernel = null;

    public function __construct(array $elevation = [], array $distance = [])
    {
        if ($elevation !== [] && $distance !== []) {
            $this->setData($elevation, $distance);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setData(array $elevation, array $distance): void
    {
        if (count($elevation) !== count($distance)) {
            throw new \InvalidArgumentException('Input arrays must be of same size.');
        }

        $this->Elevation = $elevation;
        $this->Distance = $distance;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setDataFrom(Model\Route\Entity $route, Model\Trackdata\Entity $trackdata): void
    {
        if (! $route->hasElevations() || ! $trackdata->has(Model\Trackdata\Entity::DISTANCE)) {
            throw new \InvalidArgumentException('Route must have elevations and trackdata must have distances.');
        }

        $this->Elevation = $route->elevations();
        $this->Distance = $trackdata->distance();
    }

    /**
     * @param AbstractKernel $kernel the kernel will be applied to elevation data only without using distance as index
     */
    public function setMovingAverageKernel(AbstractKernel $kernel): void
    {
        $this->MovingAverageKernel = $kernel;
    }

    public function calculate(): void
    {
        if ($this->Elevation === []) {
            return;
        }

        $elevation = $this->Elevation;

        $this->applyMovingAverage($elevation);

        $this->Gradient = array_map(fn ($value) => $value / 10, (new Derivative())->calculate(
            $elevation,
            $this->Distance
        ));
    }

    protected function applyMovingAverage(array &$elevation)
    {
        if ($this->MovingAverageKernel instanceof \Runalyze\Calculation\Math\MovingAverage\Kernel\AbstractKernel) {
            $movingAverage = new WithKernel($elevation);
            $movingAverage->setKernel($this->MovingAverageKernel);
            $movingAverage->calculate();

            $elevation = $movingAverage->movingAverage();
        }
    }

    /**
     * @return array
     */
    public function getSeries()
    {
        return $this->Gradient;
    }
}
