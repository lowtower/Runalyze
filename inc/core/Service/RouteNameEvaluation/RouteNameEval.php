<?php

declare(strict_types=1);

namespace Runalyze\Service\RouteNameEvaluation;

use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;

/**
 * Interface for retrieving route details from a external service.
 * #TSC
 */
interface RouteNameEval
{
    /**
     * Retrieves the route-name and some other relevant informations of the route.
     *
     * @throws Exception
     */
    public function evaluate(Sport $sport, Route $route, float $distance): ?RouteNameEvalResult;
}
