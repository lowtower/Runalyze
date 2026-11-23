<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Selection;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\TokenStorageAwareServiceTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SportSelectionFactory
{
    use TokenStorageAwareServiceTrait;

    public const string ALL = 'all';

    public function __construct(
        protected SportRepository $SportRepository,
        protected ConfigurationManager $ConfigurationManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->TokenStorageInterface = $tokenStorage;
    }

    /**
     * @param string|null $param
     * @param bool $allIsAvailable
     */
    public function getSelection($param = null, $allIsAvailable = true, ?Account $account = null): Selection
    {
        $account ??= $this->getUser();

        if (! $account instanceof Account) {
            throw new \RuntimeException('Sport selection can only be collected if the user is known.');
        }

        $sportList = [];

        if ($allIsAvailable) {
            $sportList[self::ALL] = 'All';
        }

        foreach ($this->SportRepository->findAllFor($account) as $sport) {
            $sportList[(string) $sport->getId()] = $sport->getName();
        }

        if ($param === null || ! isset($sportList[$param])) {
            $param = (string) $this->ConfigurationManager->getList($account)
                ->getGeneral()
                ->getRunningSport();
        }

        return new Selection($sportList, $param);
    }
}
