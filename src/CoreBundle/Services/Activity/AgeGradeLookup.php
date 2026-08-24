<?php

namespace Runalyze\Bundle\CoreBundle\Services\Activity;

use Runalyze\AgeGrade\Lookup;
use Runalyze\AgeGrade\Table\FemaleTable;
use Runalyze\AgeGrade\Table\MaleTable;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\TokenStorageAwareServiceTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class AgeGradeLookup
{
    use TokenStorageAwareServiceTrait;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->TokenStorage = $tokenStorage;
    }

    public function isLookupPossible(?Account $account = null): bool
    {
        $account = $account ?: $this->getUser();

        return $account !== null && $account->knowsGender() && $account->knowsBirthYear();
    }

    /**
     * @return null|Lookup
     */
    public function getLookup(?Account $account = null)
    {
        if (! $this->isLookupPossible($account)) {
            return null;
        }

        $account = $account ?: $this->getUser();

        return new Lookup(
            $account->isFemale() ? new FemaleTable() : new MaleTable(),
            $account->getAge()
        );
    }

    public function getDefaultLookup(): \Runalyze\AgeGrade\Lookup
    {
        return new Lookup(new MaleTable(), 25);
    }
}
