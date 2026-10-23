<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup;

use Runalyze\Calculation;

class ElevationsRecalculator
{
    protected int $NumberOfRoutes = 0;

    /**
     * @var array calculated elevations
     */
    protected array $Results = [];

    public function __construct(
        protected \PDO $PDO,
        protected int $AccountID,
        protected string $DatabasePrefix
    ) {}

    public function run(): void
    {
        $Query = $this->getQuery();
        $Update = $this->prepareUpdate();

        while ($Data = $Query->fetch()) {
            $Elevation = \Runalyze\Model\Entity::explode($Data['elevations']);
            $Calculator = new Calculation\Elevation\Calculator($Elevation);
            $Calculator->calculate();

            if ($Calculator->totalElevation() != $Data['elevation'] || $Calculator->elevationUp() != $Data['elevation_up']) {
                $Update->bindValue(':id', $Data['id']);
                $Update->bindValue(':elevation', $Calculator->totalElevation());
                $Update->bindValue(':elevation_up', $Calculator->elevationUp());
                $Update->bindValue(':elevation_down', $Calculator->elevationDown());
                $Update->execute();

                $this->NumberOfRoutes++;
            }

            $this->Results[$Data['id']] = [$Calculator->totalElevation(), $Calculator->elevationUp(), $Calculator->elevationDown()];
        }
    }

    protected function prepareUpdate(): \PDOStatement|false
    {
        $Set = [
            '`elevation` = :elevation',
            '`elevation_up` = :elevation_up',
            '`elevation_down` = :elevation_down',
        ];

        $Query = 'UPDATE `' . $this->DatabasePrefix . 'route` SET ' . implode(',', $Set) . ' WHERE `id`=:id AND `accountid`="' . $this->AccountID . '"';

        return $this->PDO->prepare($Query);
    }

    protected function getQuery(): \PDOStatement|false
    {
        return $this->PDO->query(
            'SELECT
                `id`,
                `elevation`,
                `elevation_up`,
                (CASE
                    WHEN  `elevations_corrected` !=  ""
                    THEN  `elevations_corrected`
                    ELSE  `elevations_original`
                END) AS  `elevations`
            FROM `' . $this->DatabasePrefix . 'route`
            WHERE
                `accountid`="' . $this->AccountID . '" AND
                (`elevations_original`!="" OR `elevations_corrected`!="")'
        );
    }

    public function numberOfRoutes(): int
    {
        return $this->NumberOfRoutes;
    }

    /**
     * @return array 'activityid' => array('total', 'up', 'down')
     */
    public function results(): array
    {
        return $this->Results;
    }
}
