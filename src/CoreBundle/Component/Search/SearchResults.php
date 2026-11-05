<?php

namespace Runalyze\Bundle\CoreBundle\Component\Search;

use Ajax;
use DB;
use FormularValueParser;
use Icon;
use PDOforRunalyze;
use Request;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Elevation;
use Runalyze\Activity\Energy;
use Runalyze\Activity\Pace;
use Runalyze\Activity\StrideLength;
use Runalyze\Activity\Temperature;
use Runalyze\Configuration;
use Runalyze\Data\Weather\WindSpeed;
use Runalyze\Dataset\Configuration as DatasetConfiguration;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\Query as DatasetQuery;
use Runalyze\Model\Activity\Entity;
use Runalyze\Util\LocalTime;
use Runalyze\View\Dataset\Table;
use Runalyze\View\Icon as DatasetIcon;
use SessionAccountHandler;

/**
 * Runs the activity search and prepares the results table data for
 * templates/my/search_results.html.twig, replacing the legacy
 * SearchResults class. Query construction (conditions, order, limit) is
 * kept as it was, rendering of the dataset cells is still delegated to the
 * legacy dataset view. Must only be used after the legacy environment has
 * been bootstrapped (i.e. after constructing a Frontend instance).
 */
class SearchResults
{
    public const int MAX_LIMIT_FOR_MULTI_EDITOR = 100;

    /**
     * Keys that are valid for 'sort by' and for any condition.
     * sportid and time are valid anyway, they don't need to be set here.
     * Keys are sorted as in structure.sql
     *
     * @var list<string>
     */
    private array $allowedKeys = [
        'typeid',
        'is_public',
        'is_track',
        'distance',
        's',
        'elapsed_time',
        'elevation',
        'climb_score',
        'percentage_hilly',
        'kcal',
        'pulse_avg',
        'pulse_max',
        'pulse_avg_active',
        'vo2max',
        'vo2max_with_elevation',
        'use_vo2max',
        'fit_vo2max_estimate',
        'fit_recovery_time',
        'fit_hrv_analysis',
        'fit_training_effect',
        'fit_anaerobic_training_effect',
        'fit_performance_condition',
        'rpe',
        'trimp',
        'cadence',
        'power',
        'total_strokes',
        'swolf',
        'stride_length',
        'groundcontact',
        'groundcontact_balance',
        'vertical_oscillation',
        'vertical_ratio',
        'avg_impact_gs_left',
        'avg_impact_gs_right',
        'avg_braking_gs_left',
        'avg_braking_gs_right',
        'avg_footstrike_type_left',
        'avg_footstrike_type_right',
        'avg_pronation_excursion_left',
        'avg_pronation_excursion_right',
        'temperature',
        'wind_speed',
        'wind_deg',
        'humidity',
        'pressure',
        'is_night',
        'weatherid',
        'route',
        'title',
        'partner',
        'notes',
        'avg_respiration_rate',
    ];

    /**
     * The following keys will add an IS NOT NULL condition if they have a condition (or order by)
     *
     * @var list<string>
     */
    private array $keysThatShouldIgnoreNull = [
        'avg_impact_gs_left',
        'avg_impact_gs_right',
        'avg_braking_gs_left',
        'avg_braking_gs_right',
        'avg_footstrike_type_left',
        'avg_footstrike_type_right',
        'avg_pronation_excursion_left',
        'avg_pronation_excursion_right',
        'climb_score',
        'percentage_hilly',
        'fit_training_effect',
        'fit_anaerobic_training_effect',
        'fit_performance_condition',
        'rpe',
        'temperature',
        'wind_speed',
        'wind_deg',
        'humidity',
        'pressure',
        'avg_respiration_rate',
    ];

    /**
     * The following keys will add a != 0 condition if they have a condition (or order by)
     *
     * @var list<string>
     */
    private array $keysThatShouldIgnoreZero = [
        'distance',
        'pulse_avg',
        'pulse_max',
        'pulse_avg_active',
        'vo2max',
        'fit_vo2max_estimate',
        'fit_recovery_time',
        'fit_hrv_analysis',
        'cadence',
        'power',
        'total_strokes',
        'swolf',
        'stride_length',
        'groundcontact',
        'groundcontact_balance',
        'vertical_oscillation',
        'vertical_ratio',
    ];

    /**
     * @var list<array<string, mixed>>
     */
    private array $trainings = [];

    private int $totalNumberOfTrainings = 0;

    private int $page = 0;

    private readonly int $resultsPerPage;

    private int $accountId;

    private PDOforRunalyze $db;

    private DatasetConfiguration $datasetConfig;

    private DatasetQuery $datasetQuery;

    public function __construct(
        private readonly bool $withResults = true
    ) {
        $this->resultsPerPage = (isset($_POST['resultsPerPage']) && is_numeric($_POST['resultsPerPage'])) ? $_POST['resultsPerPage'] : 20;

        if ($this->withResults) {
            $this->initDataset();
            $this->searchTrainings();
        }
    }

    private function initDataset(): void
    {
        $this->accountId = SessionAccountHandler::getId();
        $this->db = DB::getInstance();
        $this->datasetConfig = new DatasetConfiguration($this->db, $this->accountId);
        $this->datasetQuery = new DatasetQuery($this->datasetConfig, $this->db, $this->accountId);

        $this->datasetConfig->activateAllKeys();
    }

    private function searchTrainings(): void
    {
        $where = $this->getWhere();

        $this->totalNumberOfTrainings = DB::getInstance()->query('SELECT COUNT(*) FROM `' . PREFIX . 'training` AS `t` ' . $this->joinRaceResultIfRequired() . ' ' . $where . ' LIMIT 1')->fetchColumn();
        $this->page = (int) Request::param('page');

        if (($this->page - 1) * $this->resultsPerPage > $this->totalNumberOfTrainings) {
            $this->page--;
        }

        $this->datasetQuery->resetJoins();

        $this->trainings = DB::getInstance()->query(
            'SELECT DISTINCT
				`t`.`id`,
				`t`.`time`
				' . (
                $this->multiEditorRequested() ? '' : ', ' . $this->datasetQuery->queryToSelectAllKeys() . ' ' . $this->datasetQuery->queryToSelectJoinedFields()
            ) . '
				' . $this->columnFromRaceResultIfRequired() . '
			FROM `' . PREFIX . 'training` AS `t`
			' . $this->joinRaceResultIfRequired() . '
			' . $this->datasetQuery->queryToJoinTables() . '
			' . $where . ' '
            . $this->datasetQuery->queryToGroupByActivity()
            . $this->getOrder() . $this->getLimit()
        )->fetchAll();
    }

    private function columnFromRaceResultIfRequired(): string
    {
        if (isset($_POST['is_race']) && $_POST['is_race'] != '') {
            return ', `rr`.`activity_id`';
        }

        return '';
    }

    private function joinRaceResultIfRequired(): string
    {
        if (isset($_POST['is_race']) && $_POST['is_race'] != '') {
            return 'LEFT JOIN `' . PREFIX . 'raceresult` AS `rr` ON `t`.`id` = `rr`.`activity_id`';
        }

        return '';
    }

    private function getWhere(): string
    {
        $conditions = ['`t`.`accountid`="' . $this->accountId . '"'];

        $this->addSpecialConditions($conditions);

        if (isset($_POST['s']) && (string) $_POST['s'] !== '') {
            $duration = new Duration($_POST['s']);
            $_POST['s'] = $duration->seconds();
        }

        foreach ($this->allowedKeys as $key) {
            if (isset($_POST[$key])) {
                if (is_array($_POST[$key])) {
                    $this->addConditionForArray($key, $conditions);
                } elseif ((string) $_POST[$key] !== '') {
                    $this->addConditionFor($key, $conditions);
                }
            }
        }

        $this->addConditionsForOrder($conditions);

        return $this->getEquipmentCondition() . $this->getTagCondition() . ' WHERE ' . implode(' AND ', array_unique($conditions));
    }

    /**
     * @param string[] $conditions
     */
    private function addSpecialConditions(array &$conditions): void
    {
        if (isset($_POST['sportid'])) {
            $this->addSportCondition($conditions);
        }

        if (isset($_POST['date-from']) && isset($_POST['date-to'])) {
            $this->addTimeRangeCondition($conditions);
        }

        if (isset($_POST['pace']) && $_POST['pace'] != '') {
            $this->addPaceCondition($conditions);
        }

        if (isset($_POST['gradient']) && $_POST['gradient'] != '') {
            $this->addGradientCondition($conditions);
        }

        if (isset($_POST['is_race']) && $_POST['is_race'] != '') {
            $this->addIsRaceCondition($conditions);
        }
    }

    /**
     * @param string[] $conditions
     */
    private function addPaceCondition(array &$conditions): void
    {
        $pace = new Pace(0);
        $pace->fromMinPerKm($_POST['pace']);

        $value = $pace->secondsPerKm();
        $sign = $this->signForKey('pace');

        $conditions[] = '`t`.`distance` > 0';
        $conditions[] = ($sign === '=' ? 'ROUND(`t`.`s`/`t`.`distance`)' : '`t`.`s`/`t`.`distance`') . ' ' . $sign . ' ' . DB::getInstance()->escape($value);
    }

    /**
     * @param string[] $conditions
     */
    private function addGradientCondition(array &$conditions): void
    {
        $value = 10 * (float) str_replace(',', '.', $_POST['gradient']);

        $conditions[] = '`t`.`distance` > 0';
        $conditions[] = '`t`.`elevation` > 0';
        $conditions[] = '`t`.`elevation`/`t`.`distance` ' . $this->signForKey('gradient') . ' ' . DB::getInstance()->escape($value);
    }

    /**
     * @param string[] $conditions
     */
    private function addIsRaceCondition(array &$conditions): void
    {
        if ($_POST['is_race'] == 1) {
            $conditions[] = '`rr`.`activity_id` IS NOT NULL';
        } else {
            $conditions[] = '`rr`.`activity_id` IS NULL';
        }
    }

    /**
     * Add condition for array from select box
     *
     * @param string[] $conditions
     */
    private function addConditionForArray(string $key, array &$conditions): void
    {
        $array = array_map(
            fn ($value): int => (int) $value,
            $_POST[$key]
        );

        $conditions[] = '`t`.`' . $key . '` IN(' . implode(',', $array) . ')';
    }

    /**
     * Add condition for single value
     *
     * @param string[] $conditions
     */
    private function addConditionFor(string $key, array &$conditions): void
    {
        $sign = (isset($_POST['opt'][$key])) ? $this->signFor($_POST['opt'][$key]) : '=';

        if ($sign === ' LIKE ') {
            $conditions[] = '`t`.`' . $key . '` ' . $sign . ' "%' . DB::getInstance()->escape($_POST[$key], false) . '%"';
        } else {
            $value = $this->transformValueForDatabase($key, $_POST[$key]);

            $conditions[] = '`t`.`' . $key . '` ' . $sign . ' ' . DB::getInstance()->escape($value);

            if ($sign === '<' || $sign === '<=') {
                if (in_array($key, $this->keysThatShouldIgnoreZero)) {
                    $conditions[] = '`t`.`' . $key . '` != 0';
                } elseif (in_array($key, $this->keysThatShouldIgnoreNull)) {
                    $conditions[] = '`t`.`' . $key . '` IS NOT NULL';
                }
            }
        }
    }

    private function transformValueForDatabase(string $key, $value): float|int
    {
        if (in_array($key, ['distance', 'vertical_oscillation', 'vertical_ratio', 'stride_length', 'groundcontact_balance', 'fit_training_effect', 'fit_anaerobic_training_effect', 'kcal'], true)) {
            $value = (float) str_replace(',', '.', $value);
        }

        if ($key === 'elevation') {
            $value = new Elevation()->setInPreferredUnit($value)
                ->meter();
        } elseif ($key === 'distance') {
            $value = new Distance()->setInPreferredUnit($value)
                ->kilometer();
        } elseif ($key === 'percentage_hilly') {
            $value *= 0.01;
        } elseif ($key === 'vertical_oscillation' || $key === 'vertical_ratio') {
            $value *= 10;
        } elseif ($key === 'groundcontact_balance') {
            $value *= 100;
        } elseif ($key === 'stride_length') {
            $value = new StrideLength()->setInPreferredUnit($value)
                ->cm();
        } elseif ($key === 'temperature') {
            $value = new Temperature()->setInPreferredUnit($value)
                ->celsius();
        } elseif ($key === 'wind_speed') {
            $value = new WindSpeed()->setInPreferredUnit($value)
                ->value();
        } elseif (($key === 'vo2max' || $key === 'vo2max_with_elevation')) {
            $value /= Configuration::Data()->vo2maxCorrectionFactor();
        } elseif ($key === 'kcal') {
            $value = new Energy()->setInPreferredUnit($value)
                ->kcal();
        } elseif ($key === 'fit_recovery_time') {
            $value *= 60;
        }

        return $value;
    }

    private function signForKey(string $key): string
    {
        if (isset($_POST['opt'][$key])) {
            return $this->signFor($_POST['opt'][$key]);
        }

        return '=';
    }

    /**
     * Equality sign
     *
     * @param mixed $postSign from $_POST
     */
    private function signFor($postSign): string
    {
        return match ($postSign) {
            'is' => '=',
            'gt' => '>',
            'ge' => '>=',
            'le' => '<=',
            'lt' => '<',
            'ne' => '!=',
            'like' => ' LIKE ',
            default => '=',
        };
    }

    /**
     * @param string[] $conditions
     */
    private function addTimeRangeCondition(array &$conditions): void
    {
        if (
            FormularValueParser::validatePost('date-from', FormularValueParser::$PARSER_DATE)
            && FormularValueParser::validatePost('date-to', FormularValueParser::$PARSER_DATE)
            && $_POST['date-to'] > 0
        ) {
            $conditions[] = '`t`.`time` BETWEEN ' . LocalTime::fromServerTime($_POST['date-from'])->getTimestamp() . ' AND ' . (LocalTime::fromServerTime($_POST['date-to'])->getTimestamp() + DAY_IN_S);
        }
    }

    /**
     * @param string[] $conditions
     */
    private function addSportCondition(array &$conditions): void
    {
        if (is_array($_POST['sportid'])) {
            $array = array_map(
                fn ($value): int => (int) $value,
                $_POST['sportid']
            );

            $conditions[] = '`t`.`sportid` IN(' . implode(',', $array) . ')';
        } else {
            $conditions[] = '`t`.`sportid`="' . (int) $_POST['sportid'] . '"';
        }
    }

    private function getEquipmentCondition(): string
    {
        if (! isset($_POST['equipmentid'])) {
            return '';
        }

        if (is_array($_POST['equipmentid'])) {
            $array = array_map(
                fn ($value): int => (int) $value,
                $_POST['equipmentid']
            );

            return 'INNER JOIN (SELECT `ae`.`activityid` FROM `' . PREFIX . 'activity_equipment` AS `ae` WHERE `ae`.`equipmentid` IN(' . implode(',', $array) . ')) AS `suba` ON `suba`.`activityid` = `t`.`id`';
        }

        return 'INNER JOIN `' . PREFIX . 'activity_equipment` AS `ae` ON `ae`.`activityid` = `t`.`id` AND `ae`.`equipmentid`="' . (int) $_POST['equipmentid'] . '"';
    }

    private function getTagCondition(): string
    {
        if (! isset($_POST['tagid'])) {
            return '';
        }

        if (is_array($_POST['tagid'])) {
            $array = array_map(
                fn ($value): int => (int) $value,
                $_POST['tagid']
            );

            return 'INNER JOIN (SELECT `at`.`activityid` FROM `' . PREFIX . 'activity_tag` AS `at` WHERE `at`.`tagid` IN(' . implode(',', $array) . ')) AS `subb` ON `subb`.`activityid` = `t`.`id`';
        }

        return 'INNER JOIN `' . PREFIX . 'activity_tag` AS `at` ON `at`.`activityid` = `t`.`id` AND `at`.`tagid`="' . (int) $_POST['tagid'] . '"';
    }

    private function getOrder(): string
    {
        $order = (! isset($_POST['search-sort-order'])) ? 'DESC' : $this->db->escape($_POST['search-sort-order'], false);

        if (isset($_POST['search-sort-by'])) {
            if ($_POST['search-sort-by'] == 'vo2max' && Configuration::VO2max()->useElevationCorrection()) {
                return ' ORDER BY IF(`t`.`vo2max_with_elevation`>0, `t`.`vo2max_with_elevation`, `t`.`vo2max`) ' . $order;
            }

            if ($_POST['search-sort-by'] == 'pace') { // addConditionsForOrder() guarantees that distance > 0
                return ' ORDER BY `t`.`s`/`t`.`distance` ' . $order;
            }

            if ($_POST['search-sort-by'] == 'gradient') { // addConditionsForOrder() guarantees that distance > 0
                return ' ORDER BY `t`.`elevation`/`t`.`distance` ' . $order;
            }

            if ($_POST['search-sort-by'] == 'flight_time') {
                return ' ORDER BY (30000/`t`.`cadence` - `t`.`groundcontact`) ' . $order;
            }

            if ($_POST['search-sort-by'] == 'flight_ratio') {
                return ' ORDER BY (1 - `t`.`cadence` * `t`.`groundcontact` / 30000) ' . $order;
            }

            if (in_array($_POST['search-sort-by'], $this->allowedKeys)) {
                return ' ORDER BY `t`.' . $this->db->escape($_POST['search-sort-by'], false) . ' ' . $order;
            }
        }

        return ' ORDER BY `t`.`time` ' . $order;
    }

    /**
     * Add additional conditions for order
     *
     * @param string[] $conditions
     */
    private function addConditionsForOrder(array &$conditions): void
    {
        if (! isset($_POST['search-sort-by'])) {
            return;
        }

        if ($_POST['search-sort-by'] == 'pace') {
            $conditions[] = '`t`.`distance` > 0';
        } elseif ($_POST['search-sort-by'] == 'gradient') {
            $conditions[] = '`t`.`distance` > 0';
            $conditions[] = '`t`.`elevation` > 0';
        } elseif ($_POST['search-sort-by'] == 'flight_time' || $_POST['search-sort-by'] == 'flight_ratio') {
            $conditions[] = '`t`.`cadence` > 0';
            $conditions[] = '`t`.`groundcontact` > 0';
        } elseif (in_array($_POST['search-sort-by'], $this->keysThatShouldIgnoreZero)) {
            $conditions[] = '`t`.`' . $_POST['search-sort-by'] . '` != 0';
        } elseif (in_array($_POST['search-sort-by'], $this->keysThatShouldIgnoreNull)) {
            $conditions[] = '`t`.`' . $_POST['search-sort-by'] . '` IS NOT NULL';
        }
    }

    private function getLimit(): string
    {
        if ($this->multiEditorRequested()) {
            return ' LIMIT ' . self::MAX_LIMIT_FOR_MULTI_EDITOR;
        }

        if ($this->page <= 0) {
            $this->page = 1;
        }

        $limit = ($this->page - 1) * $this->resultsPerPage;

        return ' LIMIT ' . $limit . ',' . $this->resultsPerPage;
    }

    public function multiEditorRequested(): bool
    {
        return isset($_POST['send-to-multi-editor']);
    }

    /**
     * @return list<int>
     */
    public function getIdsForMultiEditor(): array
    {
        return array_map(fn (array $data): int => (int) $data['id'], $this->trainings);
    }

    /**
     * @return array<string, mixed> data for templates/my/search_results.html.twig
     */
    public function templateData(): array
    {
        if (! $this->withResults) {
            return [
                'withResults' => false,
            ];
        }

        $table = new Table($this->datasetConfig);

        return [
            'withResults' => true,
            'page' => $this->page,
            'showBack' => $this->page !== 1,
            'showNext' => $this->page * $this->resultsPerPage < $this->totalNumberOfTrainings,
            'backIcon' => Icon::$BACK,
            'nextIcon' => Icon::$NEXT,
            'foundText' => sprintf(__('Found %s activities'), $this->totalNumberOfTrainings),
            'columnLabels' => $table->codeForColumnLabels(new DatasetIcon(DatasetIcon::INFO)->code()),
            'rows' => $this->rowsForTemplate($table),
            'paginationJs' => $this->paginationJs(),
        ];
    }

    /**
     * @return list<array{dateLink: string, cells: string}>
     */
    private function rowsForTemplate(Table $table): array
    {
        $rows = [];
        $context = new Context(new Entity(), $this->accountId);

        foreach ($this->trainings as $training) {
            $context->setActivityData($training);

            $rows[] = [
                'dateLink' => Ajax::trainingLink($training['id'], new LocalTime($training['time'])->format('d.m.Y'), true),
                'cells' => $table->codeForColumns($context),
            ];
        }

        return $rows;
    }

    private function paginationJs(): string
    {
        return Ajax::wrapJSforDocumentReady(
            '$("#search-back").click(function(){'
                . 'var $i = $("#search input[name=\'page\']");'
                . '$i.val( parseInt($i.val()) - 1 );'
                . '$("#search").submit();'
            . '});'
            . '$("#search-next").click(function(){'
                . 'var $i = $("#search input[name=\'page\']");'
                . '$i.val( parseInt($i.val()) + 1 );'
                . '$("#search").submit();'
            . '});'
        );
    }
}
