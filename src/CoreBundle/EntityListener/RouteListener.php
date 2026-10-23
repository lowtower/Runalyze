<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;

class RouteListener
{
    public function __construct(
        protected ConfigurationManager $ConfigurationManager,
        protected ElevationCorrection $ElevationCorrection
    ) {}

    public function prePersist(Route $route): void
    {
        // #TSC if we have barometric data, no correction will be done (although the user wants it)
        if ($route->getElevationsCorrected() === null
            && ! $route->getAltitudeBarometric()
            && $this->userWantsElevationsToBeCorrected($route->getAccount())) {
            $route->getAdapter()
                ->correctElevation($this->ElevationCorrection);
        }

        $this->calculateElevation($route);
    }

    public function preUpdate(Route $route, PreUpdateEventArgs $args): void
    {
        if ($this->hasChangedElevations($args)) {
            $this->calculateElevation($route);
        }
    }

    protected function hasChangedElevations(PreUpdateEventArgs $args): bool
    {
        /** @var Route $route */
        $route = $args->getObject();

        return
            $args->hasChangedField('elevationsCorrected') || (
                $args->hasChangedField('elevationsOriginal')
                && $route->getElevationsCorrected() === null
            )
        ;
    }

    protected function calculateElevation(Route $route)
    {
        $configuration = $this->ConfigurationManager->getList($route->getAccount())
            ->getActivityView();

        $route->getAdapter()
            ->calculateElevation(
                $configuration->getElevationCalculationMethod(),
                $configuration->getElevationCalculationThreshold()
            );
    }

    protected function userWantsElevationsToBeCorrected(Account $account): bool
    {
        return $this->ConfigurationManager->getList($account)
            ->getActivityForm()
            ->isAutomaticElevationCorrectionActivated();
    }
}
