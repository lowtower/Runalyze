<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Notification>
 */
class NotificationRepository extends EntityRepository
{
    /**
     * @return Notification[]
     */
    public function findAll(?Account $account = null): array
    {
        if (! $account instanceof \Runalyze\Bundle\CoreBundle\Entity\Account) {
            throw new \LogicException('Finding all notifications is only possible for a specific account.');
        }

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
            ->orderBy('n.createdAt', 'DESC');

        if ($timestamp !== null && $timestamp !== 0) {
            $queryBuilder
                ->andWhere('n.wasRead = 0') // Only necessary as long as headline menu is filled via request with $timestamp = 1
                ->andWhere('n.createdAt > :time')
                ->setParameter('time', $timestamp)
                ->orderBy('n.createdAt', 'ASC');
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

        $this->_em->clear();
    }

    /**
     * @return int number of removed notifications
     */
    public function removeExpiredNotifications(): int
    {
        $numDeleted = $this->createQueryBuilder('n')
            ->delete()
            ->where('n.expirationAt < :time')
            ->setParameter('time', time())
            ->getQuery()
            ->execute();

        $this->_em->clear();

        return $numDeleted;
    }

    public function save(Notification $notification): void
    {
        $this->_em->persist($notification);
        $this->_em->flush();
    }
}
