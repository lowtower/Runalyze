<?php

namespace Runalyze\Bundle\CoreBundle\Services\Activity;

use Runalyze\AgeGrade\Lookup;
use Runalyze\AgeGrade\Table\FemaleTable;
use Runalyze\AgeGrade\Table\MaleTable;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\TokenStorageAwareServiceTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AgeGradeLookup
{
    use TokenStorageAwareServiceTrait;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->TokenStorageInterface = $tokenStorage;
    }

    public function isLookupPossible(?Account $account = null): bool
    {
        $account = $account ?: $this->getUser();

        return $account instanceof Account && $account->knowsGender() && $account->knowsBirthYear();
    }

    public function getLookup(?Account $account = null): ?Lookup
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

    public function getDefaultLookup(): Lookup
    {
        return new Lookup(new MaleTable(), 25);
    }
}
