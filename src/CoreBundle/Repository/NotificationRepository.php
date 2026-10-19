<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;

/**
 * @extends EntityRepository<Notification>
 */
class NotificationRepository extends EntityRepository
{
    /**
     * @return Notification[]
     */
    public function findAllFor(Account $account): array
    {
        return $this->findAllSince(null, $account);
    }

    /**
     * @return Notification[]
     */
    public function findAllSince(?int $timestamp, Account $account): array
    {
        $queryBuilder = $this->createQueryBuilder('n')
            ->where('n.account = :account')
            ->setParameter('account', $account)
            ->orderBy('n.createdAt', \SortDirection::Descending);

        if ($timestamp !== null && $timestamp !== 0) {
            $queryBuilder
                ->andWhere('n.wasRead = 0') // Only necessary as long as headline menu is filled via request with $timestamp = 1
                ->andWhere('n.createdAt > :time')
                ->setParameter('time', $timestamp)
                ->orderBy('n.createdAt', \SortDirection::Ascending);
        }

        return $queryBuilder->getQuery()
            ->getResult();
    }

    public function markAsRead(Notification $notification): void
    {
        $this->save($notification->setRead());
    }

    public function markAllAsRead(Account $account): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.wasRead', true)
            ->where('n.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->execute();

        $this->getEntityManager()->clear();
    }

    public function removeExpiredNotifications(): int
    {
        $numDeleted = $this->createQueryBuilder('n')
            ->delete()
            ->where('n.expirationAt < :time')
            ->setParameter('time', time())
            ->getQuery()
            ->execute();

        $this->getEntityManager()->clear();

        return $numDeleted;
    }

    public function save(Notification $notification): void
    {
        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();
    }
}
