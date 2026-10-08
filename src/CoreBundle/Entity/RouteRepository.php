<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use League\Geotools\Coordinate\CoordinateInterface;
use League\Geotools\Geohash\Geohash;

/**
 * @extends EntityRepository<Route>
 */
class RouteRepository extends EntityRepository
{
    public function accountHasLockedRoutes(Account $account): bool
    {
        return $this->createQueryBuilder('r')
            ->select('r.id')
            ->setMaxResults(1)
            ->where('r.account = :accountid AND r.lock = 1')
            ->setParameter('accountid', $account->getId())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR) !== null;
    }

    public function getStartCoordinatesFor(int $routeId): ?CoordinateInterface
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.startpoint')
            ->setMaxResults(1)
            ->where('r.id = :routeid')
            ->setParameter('routeid', $routeId)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        if ($result !== null && $result['startpoint'] !== null) {
            /** @var Geohash $geohashObj */
            $geohashObj = new Geohash()->decode($result['startpoint']);
            return $geohashObj->getCoordinate();
        }

        return null;
    }

    public function save(Route $route): void
    {
        $this->getEntityManager()->persist($route);
        $this->getEntityManager()->flush();
    }
}
