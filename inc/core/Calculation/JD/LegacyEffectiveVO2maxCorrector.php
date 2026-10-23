<?php

namespace Runalyze\Calculation\JD;

use PDO;
use Runalyze\Model\Activity;

/**
 * @deprecated since v3.3|4.0
 */
class LegacyEffectiveVO2maxCorrector
{
    /**
     * @var int Number of (best) races to look at
     */
    public const int DB_LOOKUP_LIMIT = 3;

    private static float $GlobalFactor = 1.0;

    protected float $Factor;

    public static function setGlobalFactor(float $factor): void
    {
        self::$GlobalFactor = $factor;
    }

    /**
     * @param float $factor static factor is used by default
     */
    public function __construct(?float $factor = null)
    {
        $this->Factor = $factor ?: self::$GlobalFactor;
    }

    public function factor(): float
    {
        return $this->Factor;
    }

    /**
     * Calculate factor from database
     *
     * Simply looks for the best ratio of vo2max by time and by heart rate.
     * This method does not regard any other correction (e.g. elevation, ...).
     */
    public function fromDatabase(PDO $database, int $accountid, int $sportid): float
    {
        $factor = $database->query(
            'SELECT MAX(`factor`) AS `factor`
			FROM (
				SELECT `vo2max_by_time`*1.0/`vo2max` AS `factor` 
				FROM `' . PREFIX . 'raceresult` r
				LEFT JOIN `' . PREFIX . 'training` tr ON r.activity_id = tr.id
				    WHERE
					tr.`sportid` = ' . $sportid . ' AND
					tr.`vo2max` > 0 AND
					tr.`use_vo2max` = 1 AND
					r.`accountid` = ' . $accountid . '
				ORDER BY  tr.`vo2max_by_time` DESC 
				LIMIT ' . self::DB_LOOKUP_LIMIT . '
			) AS T
			LIMIT 1'
        )->fetchColumn();

        if ($factor > 0.0) {
            $this->Factor = $factor;
        } else {
            $this->Factor = 1.0;
        }

        return $this->Factor;
    }

    /**
     * Calculate factor from activity
     *
     * Simply calculates the ratio of vo2max by time and by heart rate.
     * This method does not regard any other correction (e.g. elevation, ...).
     */
    public function fromActivity(Activity\Entity $activity): float
    {
        if ($activity->vo2maxByHeartRate() > 0.0) {
            $this->Factor = $activity->vo2maxByTime() / $activity->vo2maxByHeartRate();
        } else {
            $this->Factor = 1.0;
        }

        return $this->Factor;
    }
}
