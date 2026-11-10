<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Psr\Log\LoggerInterface;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Service\RouteNameEvaluation\RouteNameEval;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalFactory;

class ActivityContextAdapterFactory
{
    protected RouteNameEval $RouteNameEval;

    public function __construct(
        protected WeatherForecast $WeatherForecast,
        protected TrainingRepository $TrainingRepository,
        RouteNameEvalFactory $routeNameEvalFactory,
        protected ?LoggerInterface $logger = null
    ) {
        $this->RouteNameEval = $routeNameEvalFactory->getInstance();
    }

    public function getAdapterFor(ActivityContext $context): ActivityContextAdapter
    {
        return new ActivityContextAdapter(
            $context,
            $this->WeatherForecast,
            $this->TrainingRepository,
            $this->RouteNameEval,
            $this->logger
        );
    }
}
