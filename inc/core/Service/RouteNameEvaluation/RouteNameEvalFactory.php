<?php

declare(strict_types=1);

namespace Runalyze\Service\RouteNameEvaluation;

/**
 * Creates a RouteNameEvaluation implementations.
 * Currently there is only the OSM implementation available.
 * #TSC
 */
class RouteNameEvalFactory
{
    public function __construct(
        protected RouteNameEvalOsm $RouteNameEval
    ) {}

    public function getInstance(): RouteNameEval
    {
        return $this->RouteNameEval;
    }
}
