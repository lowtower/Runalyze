<?php

namespace Runalyze\View\Activity\Plot\Series;

use Runalyze\Calculation;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Sports\Running\GradeAdjustedPace\Algorithm\Minetti;
use Runalyze\View\Activity;

class GradeAdjustedPace extends Pace
{
    public const string COLOR = 'rgb(100,184,100)';

    protected bool $adjustAxis = false;

    protected int $KernelWidth = 20;

    public function __construct(Activity\Context $context)
    {
        $internalContext = clone $context;

        $this->adjustTrackdata($internalContext);

        parent::__construct($internalContext);
    }

    protected function initOptions()
    {
        parent::initOptions();

        $this->Label = __('Grade adjusted pace') . ' (beta)';
        $this->Color = self::COLOR;
    }

    protected function adjustTrackdata(Activity\Context $context)
    {
        // #TSC the based Minetti-algorithm only makes sence in RUNNING
        if ($context->sport()->getInternalProfileEnum() !== SportProfile::RUNNING || ! $context->hasRoute() || ! $context->route()->hasElevations() || ! $context->hasTrackdata() || ! $context->trackdata()->has(Trackdata::DISTANCE)) {
            $context->trackdata()
                ->set(Trackdata::PACE, []);

            return;
        }

        $context->trackdata()
            ->setTheoreticalPace($this->getGradeAdjustedPace($context));
    }

    protected function getGradeAdjustedPace(Activity\Context $context): array
    {
        $algorithm = new Minetti();
        $gradient = $this->getGradientSeries($context);
        $gap = $context->trackdata()
            ->pace();

        foreach (array_keys($gap) as $i) {
            $gap[$i] *= $algorithm->getTimeFactor($gradient[$i] / 100.0);
        }

        return $gap;
    }

    protected function getGradientSeries(Activity\Context $context): array
    {
        $gradient = new Calculation\Route\Gradient();
        $gradient->setDataFrom($context->route(), $context->trackdata());
        $gradient->setMovingAverageKernel(new Calculation\Math\MovingAverage\Kernel\Uniform($this->KernelWidth));
        $gradient->calculate();

        return $gradient->getSeries();
    }
}
