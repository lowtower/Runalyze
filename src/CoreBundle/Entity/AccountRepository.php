<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends EntityRepository<Account>
 */
class AccountRepository extends EntityRepository implements UserLoaderInterface
{
    public function findForUsername(string $username): ?Account
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|string[] $language language key(s)
     * @param bool $excluded if set, all but given languages will be selected
     * @return array account ids
     */
    public function findAllByLanguage(string|array $language, bool $excluded = false): array
    {
        return $this->findAllByLanguageQueryBuilder($language, $excluded)
            ->getQuery()
            ->getResult('COLUMN_HYDRATOR');
    }

    /**
     * @param string|string[] $language language key(s)
     * @param bool $excluded if set, all but given languages will be selected
     */
    public function findAllByLanguageQueryBuilder(string|array $language, bool $excluded = false): QueryBuilder
    {
        if (! is_array($language)) {
            $language = [$language];
        }

        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u.id');

        if ($language !== []) {
            $queryBuilder
                ->where('u.language ' . ($excluded ? 'NOT' : '') . ' IN (:lang)')
                ->setParameter('lang', $language, Connection::PARAM_STR_ARRAY);
        }

        return $queryBuilder;
    }

    /**
     * #TSC: because of the UserLoaderInterface and the configuration in security.yml/provider this method is call while authentication.
     *
     * @param string $username username or mail
     */
    public function loadUserByUsername(string $username): ?Account
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * @param string $identifier username or mail
     */
    public function loadUserByIdentifier(string $identifier): ?Account
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.mail = :mail')
            ->setParameter('username', $identifier)
            ->setParameter('mail', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getAmountOfActivatedUsers(bool $cache = true): bool|float|int|string|null
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.activationHash is NULL')
            ->getQuery()
            ->useResultCache($cache, 320)
            ->getSingleScalarResult();
    }

    public function deleteNotActivatedAccounts(int $days = 7): array
    {
        $minimumAge = time() - $days * 86400;

        $deletedAccounts = $this->createQueryBuilder('u')
            ->select('u.id')
            ->where('u.activationHash IS NOT NULL AND u.registerdate < :minimumAge')
            ->setParameter('minimumAge', $minimumAge)
            ->getQuery()
            ->getResult('COLUMN_HYDRATOR');

        $this->createQueryBuilder('u')
            ->delete()
            ->where('u.activationHash IS NOT NULL AND u.registerdate < :minimumAge')
            ->setParameter('minimumAge', $minimumAge)
            ->getQuery()
            ->execute();

        return $deletedAccounts;
    }

    public function existsOneWith(array $criteria): bool
    {
        return $this->findOneBy($criteria) !== null;
    }

    /**
     * @return bool true on success
     */
    public function deleteByHash(string $deletionHash): bool
    {
        $account = $this->findOneBy([
            'deletionHash' => $deletionHash,
        ]);

        if ($account !== null) {
            $this->_em->remove($account);
            $this->_em->flush();

            return true;
        }

        return false;
    }

    /**
     * @return bool true on success
     */
    public function activateByHash(string $activationHash): bool
    {
        $account = $this->findOneBy([
            'activationHash' => $activationHash,
        ]);

        if ($account !== null) {
            $this->save($account->removeActivationHash());

            return true;
        }

        return false;
    }

    public function save(Account $account): void
    {
        $this->_em->persist($account);
        $this->_em->flush();
    }
}
