<?php

/**
 * This file contains class::DuplicateFinder
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use PDO;

/**
 * @deprecated since v4.3
 * @see \Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder
 */
class DuplicateFinder
{
    /**
     * @var string
     */
    public const COLUMN_WITH_ID = 'activity_id';

    protected \PDO $PDO;

    protected int $AccountId;

    public function __construct(PDO $pdo, int $accountId)
    {
        $this->PDO = $pdo;
        $this->AccountId = $accountId;
    }

    /**
     * Find duplicate by original activityID (Timestamp)
     */
    public function checkForDuplicate(?int $activityId): bool
    {
        if ($activityId === null) {
            return false;
        }

        return $this->PDO->query('SELECT 1 FROM `' . PREFIX . 'training` WHERE `' . self::COLUMN_WITH_ID . '` = "' . $activityId . '" AND `accountid` = "' . $this->AccountId . '" LIMIT 1')->fetchColumn() == 1;
    }

    /**
     * @param array $activityIds null or int
     * @return bool[] array(id => true|false)
     * @throws \InvalidArgumentException
     */
    public function checkForDuplicates(array $activityIds): array
    {
        $result = [];

        if (in_array(null, $activityIds)) {
            $result[''] = false;
            $activityIds = array_filter($activityIds, 'strlen');
        }

        if ($activityIds !== []) {
            if (array_filter($activityIds, 'is_numeric') !== $activityIds) {
                throw new \InvalidArgumentException('All activity ids must be numerical.');
            }

            $existingIds = $this->PDO->query('SELECT `' . self::COLUMN_WITH_ID . '` FROM `' . PREFIX . 'training` WHERE `' . self::COLUMN_WITH_ID . '` IN (' . implode(', ', $activityIds) . ') AND `accountid` = "' . $this->AccountId . '"')->fetchAll(PDO::FETCH_COLUMN);

            foreach ($activityIds as $id) {
                $result[$id] = in_array($id, $existingIds);
            }
        }

        return $result;
    }
}
