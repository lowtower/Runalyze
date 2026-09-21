<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<User>
 */
class UserRepository extends EntityRepository
{
    /**
     * @return User[]
     */
    public function findAllFor(Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
        ], [
            'time' => 'DESC',
        ]);
    }

    /**
     * @return ?int [bpm]
     */
    public function getCurrentRestingHeartRate(Account $account): ?int
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.pulseRest')
            ->setMaxResults(1)
            ->where('u.account = :accountid')
            ->andWhere('u.pulseRest > 0')
            ->setParameter('accountid', $account->getId())
            ->orderBy('u.time', 'DESC')
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        return $result ? (int) $result['pulseRest'] : null;
    }

    /**
     * @return ?int [bpm]
     */
    public function getCurrentMaximalHeartRate(Account $account): ?int
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.pulseMax')
            ->setMaxResults(1)
            ->where('u.account = :accountid')
            ->andWhere('u.pulseMax > 0')
            ->setParameter('accountid', $account->getId())
            ->orderBy('u.time', 'DESC')
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        return $result ? (int) $result['pulseMax'] : null;
    }

    public function save(User $user, Account $account): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function getLatestEntryFor(Account $account): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.account = :account')
            ->setParameter('account', $account->getId())
            ->addOrderBy('u.time', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function remove(User $user): void
    {
        $this->_em->remove($user);
        $this->_em->flush();
    }
}
