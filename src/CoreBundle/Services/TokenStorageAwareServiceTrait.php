<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

trait TokenStorageAwareServiceTrait
{
    /**
     * @var TokenStorage
     */
    protected $TokenStorage;

    protected function knowsUser(): bool
    {
        return $this->TokenStorage->getToken() !== null && $this->TokenStorage->getToken()
            ->getUser() instanceof Account;
    }

    /**
     * @return Account|null
     */
    protected function getUser()
    {
        return $this->knowsUser() ? $this->TokenStorage->getToken()
            ->getUser() : null;
    }
}
