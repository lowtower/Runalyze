<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class PrivacyGuard
{
    use TokenStorageAwareServiceTrait;

    /** @var ConfigurationManager */
    protected $ConfigurationManager;

    public function __construct(TokenStorage $tokenStorage, ConfigurationManager $configurationManager)
    {
        $this->TokenStorage = $tokenStorage;
        $this->ConfigurationManager = $configurationManager;
    }

    /**
     * @return \Runalyze\Configuration\Category\Privacy
     */
    protected function getPrivacyFor(Account $account)
    {
        return $this->ConfigurationManager->getList($account)
            ->getPrivacy()
            ->getLegacyCategory();
    }

    /**
     * @return bool
     */
    public function isMapVisible(Training $activity, ?Raceresult $raceresult = null)
    {
        if ($this->knowsUser() && $this->getUser()->getId() == $activity->getAccount()->getId()) {
            return true;
        }

        $mapVisibility = $this->getPrivacyFor($activity->getAccount())
            ->RoutePrivacy();

        if ($mapVisibility->showRace()) {
            return $raceresult instanceof \Runalyze\Bundle\CoreBundle\Entity\Raceresult;
        }

        return $mapVisibility->showAlways();
    }
}
