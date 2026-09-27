<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Service\RouteNameEvaluation\RouteNameEval;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalFactory;

class ActivityContextAdapterFactory
{
    protected RouteNameEval $RouteNameEval;

    public function __construct(
        protected WeatherForecast $WeatherForecast,
        protected DuplicateFinder $DuplicateFinder,
        RouteNameEvalFactory $routeNameEvalFactory
    ) {
        $this->RouteNameEval = $routeNameEvalFactory->getInstance();
    }

    public function getAdapterFor(ActivityContext $context): ActivityContextAdapter
    {
        return new ActivityContextAdapter(
            $context,
            $this->WeatherForecast,
            $this->DuplicateFinder,
            $this->RouteNameEval
        );
    }
}
