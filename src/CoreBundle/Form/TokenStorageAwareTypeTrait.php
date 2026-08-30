<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait TokenStorageAwareTypeTrait
{
    /**
     * @var TokenStorageInterface
     */
    protected $TokenStorageInterface;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->TokenStorageInterface = $tokenStorage;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorageInterface->getToken() ? $this->TokenStorageInterface->getToken()
            ->getUser() : null;

        if (! ($account instanceof Account)) {
            throw new \RuntimeException('Activity type must have a valid account token.');
        }

        return $account;
    }
}
