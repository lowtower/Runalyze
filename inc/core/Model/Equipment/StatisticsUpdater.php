<?php

declare(strict_types=1);

namespace Runalyze\Model\Equipment;

class StatisticsUpdater
{
    public function __construct(
        protected \PDO $PDO,
        protected int $AccountId,
        protected string $DatabasePrefix
    ) {}

    /**
     * Update statistics
     * @return mixed false on failure, number of affected rows otherwise
     */
    public function run()
    {
        $result = $this->PDO->exec(
            'UPDATE `' . $this->DatabasePrefix . 'equipment`
            CROSS JOIN(
                SELECT
                    `eqp`.`id` AS `eqpid`,
                    SUM(`tr`.`distance`) AS `km`,
                    SUM(`tr`.`s`) AS `s`
                FROM `' . $this->DatabasePrefix . 'equipment` AS `eqp`
                LEFT JOIN `' . $this->DatabasePrefix . 'activity_equipment` AS `aeqp` ON `eqp`.`id` = `aeqp`.`equipmentid`
                LEFT JOIN `' . $this->DatabasePrefix . 'training` AS `tr` ON `aeqp`.`activityid` = `tr`.`id`
                WHERE `eqp`.`accountid` = ' . $this->AccountId . '
                GROUP BY `eqp`.`id`
            ) AS `new`
            SET
                `distance` = IFNULL(`new`.`km`, 0),
                `time` = IFNULL(`new`.`s`, 0)
            WHERE `id` = `new`.`eqpid`'
        );

        if ($result !== false) {
            $Factory = new \Runalyze\Model\Factory($this->AccountId);
            $Factory->clearCache('equipment');
        }

        return $result;
    }
}
