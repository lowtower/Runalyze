<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait TokenStorageAwareServiceTrait
{
    protected TokenStorageInterface $TokenStorageInterface;

    protected function knowsUser(): bool
    {
        return $this->TokenStorageInterface->getToken() !== null && $this->TokenStorageInterface->getToken()
            ->getUser() instanceof Account;
    }

    protected function getUser(): ?Account
    {
        if (! $this->knowsUser()) {
            return null;
        }

        $user = $this->TokenStorageInterface->getToken()
            ->getUser();
        return $user instanceof Account ? $user : null;
    }
}
