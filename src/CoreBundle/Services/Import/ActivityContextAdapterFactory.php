<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalFactory;

class ActivityContextAdapterFactory
{
    protected \Runalyze\Service\RouteNameEvaluation\RouteNameEval $RouteNameEval;

    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast $WeatherForecast,
        protected \Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder $DuplicateFinder,
        RouteNameEvalFactory $routeNameEvalFactory
    ) {
        $this->RouteNameEval = $routeNameEvalFactory->getInstance();
    }

    public function getAdapterFor(ActivityContext $context): \Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter
    {
        return new ActivityContextAdapter(
            $context,
            $this->WeatherForecast,
            $this->DuplicateFinder,
            $this->RouteNameEval
        );
    }
}
