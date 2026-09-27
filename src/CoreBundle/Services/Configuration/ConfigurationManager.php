<?php

namespace Runalyze\Bundle\CoreBundle\Services\Configuration;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\ConfRepository;
use Runalyze\Bundle\CoreBundle\Services\TokenStorageAwareServiceTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ConfigurationManager
{
    use TokenStorageAwareServiceTrait;

    protected ?RunalyzeConfigurationList $CurrentConfigurationList = null;

    protected ?RunalyzeConfigurationList $DefaultList = null;

    public function __construct(
        protected ConfRepository $Repository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->TokenStorageInterface = $tokenStorage;
    }

    /**
     * @param ?Account $account defaults to current user or default config, if there is no user logged in
     */
    public function getList(?Account $account = null): RunalyzeConfigurationList
    {
        if (! $account instanceof Account || ($this->knowsUser() && $account->getId() == $this->getUser()->getId())) {
            if (! $this->CurrentConfigurationList instanceof RunalyzeConfigurationList) {
                $this->setListForCurrentUser();
            }

            return $this->CurrentConfigurationList;
        }

        return $this->getListFor($account);
    }

    protected function getListFor(Account $account): RunalyzeConfigurationList
    {
        $listData = [];

        foreach ($this->Repository->findByAccount($account) as $conf) {
            $listData[$conf->getCategory() . '.' . $conf->getKey()] = $conf->getValue();
        }

        $list = new RunalyzeConfigurationList();
        $list->mergeWith($listData);

        return $list;
    }

    protected function setListForCurrentUser(): void
    {
        $user = $this->getUser();

        if ($user instanceof Account) {
            $this->CurrentConfigurationList = $this->getListFor($user);
        } else {
            $this->CurrentConfigurationList = new RunalyzeConfigurationList();
        }
    }
}
