<?php

namespace Runalyze\Calculation\JD;

use Runalyze\Configuration\Category\VO2max;
use Runalyze\Util\LocalTime;

class Shape
{
    protected ?LegacyEffectiveVO2maxCorrector $Corrector = null;

    /**
     * @var ?float [ml/kg/min]
     */
    protected ?float $Value = null;

    public function __construct(
        protected \PDO $PDO,
        protected int $AccountID,
        protected int $RunningID,
        protected VO2max $Configuration
    ) {
    }

    public function setCorrector(LegacyEffectiveVO2maxCorrector $corrector): void
    {
        $this->Corrector = $corrector;
    }

    public function calculate(): void
    {
        $this->calculateAt(time());
    }

    /**
     * @param int $timestampInServerTimezone timestamp
     */
    public function calculateAt(int $timestampInServerTimezone): void
    {
        $time = LocalTime::fromServerTime($timestampInServerTimezone)->setTime(23, 59, 59)->getTimestamp();

        $data = $this->PDO->query(
            'SELECT
				SUM(' . self::mysqlVO2maxSumTime($this->Configuration->useElevationCorrection()) . ') as `ssum`,
				SUM(' . self::mysqlVO2maxSum($this->Configuration->useElevationCorrection()) . ') as `value`
			FROM `' . PREFIX . 'training`
			WHERE
				`sportid`=' . $this->RunningID . ' AND
				`time` BETWEEN ' . ($time - $this->Configuration->days() * DAY_IN_S) . ' AND ' . $time . ' AND
				`accountid`=' . $this->AccountID . '
			GROUP BY `sportid`
			LIMIT 1'
        )->fetch();

        if ($data !== false && $data['ssum'] > 0) {
            $this->Value = round($data['value'] / $data['ssum'], 5);
        } else {
            $this->Value = 0.0;
        }
    }

    /**
     * Get sum selector for VO2max for mysql
     *
     * Depends on configuration: `vo2max`*`s`*`use_vo2max` or `vo2max_with_elevation`*`s`*`use_vo2max`
     */
    public static function mysqlVO2maxSum(bool $withElevation = false): string
    {
        return '`' . self::getVO2maxColumn($withElevation) . '`*`s`*`use_vo2max`';
    }

    /**
     * Get sum selector for time for mysql
     *
     * `s`*`use_vo2max`
     */
    public static function mysqlVO2maxSumTime(bool $withElevation = false): string
    {
        return '`s`*`use_vo2max`*(`' . self::getVO2maxColumn($withElevation) . '` > 0)';
    }

    /**
     * @return string column name without backticks
     */
    public static function getVO2maxColumn(bool $withElevation = false): string
    {
        return $withElevation ? 'vo2max_with_elevation' : 'vo2max';
    }

    /**
     * VO2max shape
     *
     * This value is already corrected.
     * If no corrector was set, the global/static one is used.
     *
     * @return float [ml/kg/min]
     */
    public function value(): float
    {
        $this->Corrector ??= new LegacyEffectiveVO2maxCorrector();

        return $this->uncorrectedValue() * $this->Corrector->factor();
    }

    /**
     * Uncorrected shape
     *
     * @return ?float [ml/kg/min]
     * @throws \RuntimeException
     */
    public function uncorrectedValue(): ?float
    {
        if ($this->Value === null) {
            throw new \RuntimeException('The value has to be calculated first.');
        }

        return $this->Value;
    }
}
