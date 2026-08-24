<?php

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;

class RouteListener
{
    /**
     * @var ConfigurationManager
     */
    protected $ConfigurationManager;

    /**
     * @var ElevationCorrection
     */
    protected $ElevationCorrection;

    public function __construct(
        ConfigurationManager $configurationManager,
        ElevationCorrection $elevationCorrection
    )
    {
        $this->ConfigurationManager = $configurationManager;
        $this->ElevationCorrection = $elevationCorrection;
    }

    public function prePersist(Route $route, LifecycleEventArgs $args)
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

    public function preUpdate(Route $route, PreUpdateEventArgs $args)
    {
        if ($this->hasChangedElevations($args)) {
            $this->calculateElevation($route);
        }
    }

    protected function hasChangedElevations(PreUpdateEventArgs $args): bool
    {
        return (
            $args->hasChangedField('elevationsCorrected') || (
                $args->hasChangedField('elevationsOriginal') &&
                $args->getNewValue('elevationsCorrected') === null
            )
        );
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
