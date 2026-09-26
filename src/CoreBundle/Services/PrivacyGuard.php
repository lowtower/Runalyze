<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Configuration\Category\Privacy;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PrivacyGuard
{
    use TokenStorageAwareServiceTrait;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        protected ConfigurationManager $ConfigurationManager
    ) {
        $this->TokenStorageInterface = $tokenStorage;
    }

    protected function getPrivacyFor(Account $account): Privacy
    {
        return $this->ConfigurationManager->getList($account)
            ->getPrivacy()
            ->getLegacyCategory();
    }

    public function isMapVisible(Training $activity, ?Raceresult $raceresult = null): bool
    {
        if ($this->knowsUser() && $this->getUser()->getId() == $activity->getAccount()->getId()) {
            return true;
        }

        $mapVisibility = $this->getPrivacyFor($activity->getAccount())
            ->RoutePrivacy();

        if ($mapVisibility->showRace()) {
            return $raceresult instanceof Raceresult;
        }

        return $mapVisibility->showAlways();
    }
}
