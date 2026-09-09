<?php

/**
 * This file contains class::Query
 * @package Runalyze
 */

namespace Runalyze\Dataset;

use Runalyze\Util\LocalTime;

/**
 * Build query to fetch dataset values from `runalyze_training`
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset
 */
class Query
{
    /**
     * @var int
     */
    public const YEAR_TIMERANGE = 31622400; // 366 * 86400

    /**
     * @var int
     */
    public const MONTH_TIMERANGE = 2678400; // 31 * 86400

    /**
     * @var Configuration
     */
    protected $Configuration;

    protected \PDO $PDO;

    protected int $AccountID;

    protected bool $ShowOnlyPublicActivites = false;

    protected array $AdditionalColumns = [];

    /**
     * @var array array('table' => array('field' => ..., 'join' => ...))
     */
    protected array $JoinTables = [];

    public function __construct(Configuration $configuration, \PDO $pdo, int $accountID)
    {
        $this->Configuration = $configuration;
        $this->PDO = $pdo;
        $this->AccountID = $accountID;
    }

    public function showOnlyPublicActivities(bool $flag = true)
    {
        $this->ShowOnlyPublicActivites = $flag;
    }

    public function statementToFetchActivities(int $timeStart, int $timeEnd, ?int $sportId = null, bool $allKeys = false): \PDOStatement
    {
        $this->resetJoins();

        $whereSport = $sportId === null ? '' : '`t`.`sportid` = ' . $sportId . ' AND ';

        return $this->PDO->query(
            'SELECT
				`t`.`id`,
				`t`.`time`,
				`t`.`s` as `' . Keys\Pace::DURATION_SUM_WITH_DISTANCE_KEY . '`,
				DATE(FROM_UNIXTIME(`t`.`time`)) as `date`,
				IF(r.activity_id IS NULL, 0, 1) as `is_race`,
				' . ($allKeys ? $this->queryToSelectAllKeys() : $this->queryToSelectActiveKeys()) . '
				' . $this->queryToSelectJoinedFields() . '
			FROM `' . PREFIX . 'training` AS `t`
			LEFT JOIN `' . PREFIX . 'raceresult` AS `r` ON `t`.`id` = `r`.`activity_id`
			' . $this->queryToJoinTables() . '
			WHERE
				' . $this->whereTimeIsBetween($timeStart, $timeEnd) . ' AND
				`t`.`accountid` = ' . $this->AccountID . ' AND
				' . $whereSport . '
				' . $this->wherePrivacyIsOkay() . '
			' . $this->queryToGroupByActivity() . '
			ORDER BY `time` ASC'
        );
    }

    /**
     * Gets the sports for the timerange where activities exists. List is limited so not all sports are available.
     * @param null|int $sportIdSelected set if user has selected a sport; means this must be included in the result
     */
    public function statementToFetchActivitySports(int $timeStart, int $timeEnd, ?int $sportIdSelected): \PDOStatement
    {
        // this is needed to ensure, that the current selected sportId is included
        $addSelected = $sportIdSelected === null ? '' : 'UNION SELECT s.id, s.name, s.img FROM runalyze_sport s WHERE s.id = ' . $sportIdSelected;

        // the query counts the activities group by sport and than limit the result; so the x most recent sports are selected
        return $this->PDO->query(
            'SELECT id, name, img FROM
				(SELECT s.id, s.name, s.img, count(*) AS c
				FROM ' . PREFIX . 'sport AS s
				JOIN ' . PREFIX . 'training AS t ON s.id = t.sportid
				WHERE
					' . $this->whereTimeIsBetween($timeStart, $timeEnd) . ' AND
					t.accountid = ' . $this->AccountID . ' AND
					' . $this->wherePrivacyIsOkay() . '
				GROUP BY s.id, s.name, s.img ORDER BY c DESC LIMIT 7) AS sub
			' . $addSelected . '
			ORDER BY id ASC'
        );
    }

    public function resetJoins()
    {
        $this->JoinTables = [];
    }

    public function queryToSelectJoinedFields(): string
    {
        $query = '';

        foreach ($this->JoinTables as $joinData) {
            $query .= ', ' . $joinData['field'];
        }

        return $query;
    }

    public function queryToJoinTables(): string
    {
        $query = '';

        foreach ($this->JoinTables as $joinData) {
            $query .= $joinData['join'] . ' ';
        }

        return $query;
    }

    public function queryToGroupByActivity(): string
    {
        if ($this->JoinTables !== []) {
            return 'GROUP BY `t`.`id`';
        }

        return '';
    }

    /**
     * @param int $timeStart default 0
     * @param int|bool $timeEnd   default time()
     * @return array array with summary for given sportid and timerange
     */
    public function fetchSummaryForSport(int $sportid, int $timeStart = 0, $timeEnd = false)
    {
        return $this->PDO->query(
            'SELECT
				`time`,
				`sportid`,
				SUM(IF(`distance`>0,`s`,0)) as `' . Keys\Pace::DURATION_SUM_WITH_DISTANCE_KEY . '`,
				SUM(1) as `num`,
				' . $this->queryToSummarizeActiveKeys() . '
			FROM `' . PREFIX . 'training` AS `t`
			WHERE
				`sportid` = ' . $sportid . ' AND
				`accountid` = ' . $this->AccountID . ' AND
				' . $this->whereTimeIsBetween($timeStart, $timeEnd) . ' AND
				' . $this->wherePrivacyIsOkay() . '
			GROUP BY `sportid`
			LIMIT 1'
        )->fetch();
    }

    /**
     * @param int $timeStart default 0
     * @param int|false $timeEnd   default time()
     * @return array array with summary for each sportid and given timerange
     */
    public function fetchSummaryForAllSport(int $timeStart = 0, $timeEnd = false, ?int $sportId = null)
    {
        $whereSport = $sportId === null ? '' : '`t`.`sportid` = ' . $sportId . ' AND ';

        return $this->PDO->query(
            'SELECT
				`time`,
				`sportid`,
				SUM(IF(`distance`>0,`s`,0)) as `' . Keys\Pace::DURATION_SUM_WITH_DISTANCE_KEY . '`,
				SUM(1) as `num`,
				' . $this->queryToSummarizeActiveKeys() . '
			FROM `' . PREFIX . 'training` AS `t`
			WHERE
				`accountid` = ' . $this->AccountID . ' AND
				' . $this->whereTimeIsBetween($timeStart, $timeEnd) . ' AND
				' . $whereSport . '
				' . $this->wherePrivacyIsOkay() . '
			GROUP BY `sportid`'
        )->fetchAll();
    }

    /**
     * Get summary for a given timerange
     * @param int $timerange default 7*24*60*60
     * @param int $timeStart default 0
     * @param int|bool $timeEnd   default time()
     * @return array array of summaries for different timeranges
     */
    public function fetchSummaryForTimerange(int $sportid, int $timerange = 604800, int $timeStart = 0, $timeEnd = false): array
    {
        $Query = '
		        SELECT
				' . (($sportid > 0) ? '`sportid`' : '0') . ',
				SUM(IF(`distance`>0,`s`,0)) as `' . Keys\Pace::DURATION_SUM_WITH_DISTANCE_KEY . '`,
				SUM(1) as `num`,
				' . $this->queryToSummarizeActiveKeys() . ',
				' . $this->queryToSelectTimerange($timerange, $timeEnd, 'timerange') . '
			FROM `' . PREFIX . 'training` AS `t`
			WHERE
				' . (($sportid > 0) ? '`sportid` = ' . $sportid . ' AND' : '') . '
				`accountid` = ' . $this->AccountID . ' AND
				' . $this->whereTimeIsBetween($timeStart, $timeEnd) . ' AND
				' . $this->wherePrivacyIsOkay() . '
				GROUP BY `timerange`
				' . (($sportid > 0) ? ', `sportid`' : '') . '
				ORDER BY `timerange` ASC';

        return $this->PDO->query($Query)
            ->fetchAll();
    }

    protected function queryToSummarizeActiveKeys(): string
    {
        $columnSelects = [];

        foreach ($this->Configuration->activeKeys() as $key) {
            $KeyObject = Keys::get($key);

            if ($KeyObject->isInDatabase() && $KeyObject->isShownInSummary()) {
                $columnSelects[$key] = SummaryMode::query(
                    $KeyObject->summaryMode(),
                    $KeyObject->column()
                );
            }
        }

        return $columnSelects === [] ? '1' : implode(', ', $columnSelects);
    }

    /**
     * Hint: You must use "... FROM `'.PREFIX.'training` AS `t` ..."
     */
    public function queryToSelectAllKeys(): string
    {
        return $this->queryToSelectKeys(
            $this->collectColumnsForKeys($this->Configuration->allKeys())
        );
    }

    /**
     * Hint: You must use "... FROM `'.PREFIX.'training` AS `t` ..."
     */
    public function queryToSelectActiveKeys(): string
    {
        return $this->queryToSelectKeys(
            $this->collectColumnsForKeys($this->Configuration->activeKeys())
        );
    }

    protected function queryToSelectKeys(array $arrayOfKeys): string
    {
        return $arrayOfKeys === [] ? '1' : '`t`.`' . implode('`, `t`.`', $arrayOfKeys) . '`';
    }

    protected function collectColumnsForKeys(array $keys): array
    {
        $columns = $this->defaultColumns();

        foreach ($keys as $key) {
            $KeyObject = Keys::get($key);

            if ($KeyObject->isInDatabase()) {
                if ($KeyObject->requiresJoin()) {
                    $joinDefinition = $KeyObject->joinDefinition();

                    if (! array_key_exists($joinDefinition['column'], $this->JoinTables)) {
                        $this->JoinTables[$joinDefinition['column']] = $joinDefinition;
                    }
                } else {
                    $appendix = $KeyObject->column();

                    if (is_array($appendix)) {
                        $columns = array_merge($columns, $appendix);
                    } else {
                        $columns[] = $appendix;
                    }
                }
            }
        }

        return array_unique($columns);
    }

    protected function defaultColumns(): array
    {
        return array_merge(['sportid', 'time', 'use_vo2max', 'is_track', 'typeid'], $this->AdditionalColumns);
    }

    public function setAdditionalColumns(array $columns)
    {
        $this->AdditionalColumns = $columns;
    }

    /**
     * @param int $timerange length of time range, typically 366* or 31*DAY_IN_S
     * @param int|false $timeEnd last timestamp to consider
     * @param string $asColumn optional column key that is used in returned data row
     */
    protected function queryToSelectTimerange(int $timerange, $timeEnd = false, string $asColumn = 'timerange'): string
    {
        $timeEnd = $timeEnd ?: LocalTime::now();

        if ($timerange === self::YEAR_TIMERANGE) {
            return LocalTime::date('Y', $timeEnd) . ' - YEAR(FROM_UNIXTIME(`t`.`time`)) as `' . $asColumn . '`';
        }

        if ($timerange === self::MONTH_TIMERANGE) {
            return LocalTime::date('m', $timeEnd) . ' - MONTH(FROM_UNIXTIME(`t`.`time`)) + 12*(' . LocalTime::date('Y', $timeEnd) . ' - YEAR(FROM_UNIXTIME(`t`.`time`))) as `' . $asColumn . '`';
        }

        return 'FLOOR((' . $timeEnd . '-`t`.`time`)/(' . $timerange . ')) as `' . $asColumn . '`';
    }

    /**
     * @param int $timeStart default: 0
     * @param int|bool $timeEnd default: LocalTime::now()
     */
    protected function whereTimeIsBetween(int $timeStart = 0, $timeEnd = false): string
    {
        $timeEnd = $timeEnd ?: LocalTime::now();

        return '`t`.`time` BETWEEN ' . ($timeStart - 10) . ' AND ' . ($timeEnd - 10);
    }

    /**
     * Query to respect activity's privacy
     */
    protected function wherePrivacyIsOkay(): string
    {
        if ($this->ShowOnlyPublicActivites) {
            return '`t`.`is_public`=1';
        }

        return '1';
    }
}
