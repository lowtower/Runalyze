<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait TokenStorageAwareTypeTrait
{
    protected TokenStorageInterface $TokenStorageInterface;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->TokenStorageInterface = $tokenStorage;
    }

    protected function getAccount(): Account
    {
        $account = $this->TokenStorageInterface->getToken() ? $this->TokenStorageInterface->getToken()
            ->getUser() : null;

        if (! ($account instanceof Account)) {
            throw new \RuntimeException('Activity type must have a valid account token.');
        }

        return $account;
    }
}
