<?php

/**
 * This file contains class::DataBrowser
 * @package Runalyze\DataBrowser
 */

use Runalyze\Configuration;
use Runalyze\Dataset;
use Runalyze\Model\Factory;
use Runalyze\Util\LocalTime;
use Runalyze\Util\Time;
use Runalyze\View;

/**
 * @author Hannes Christiansen
 * @package Runalyze\DataBrowser
 */
class DataBrowser
{
    /**
     * CSS-ID for calendar-widget
     */
    public const string CALENDAR_ID = 'data-browser-calendar';

    /**
     * CSS-ID for refresh button
     */
    public const string REFRESH_BUTTON_ID = 'refreshDataBrowser';

    /**
     * URL for the add-activity window
     */
    public const string ADD_URL = 'activity/add';

    /**
     * URL for the add-activity form window
     */
    public const string ADD_URL_FORM = 'activity/new';

    /**
     * Timestamp for first day to be displayed
     * @var int [timestamp without any timezone]
     */
    protected int $TimestampStart;

    /**
     * Timestamp for last day to be displayed
     * @var int [timestamp without any timezone]
     */
    protected int $TimestampEnd;

    /**
     * sportid to be displayed
     */
    protected ?int $SportId = null;

    /**
     * Number of days to be displayed
     */
    protected int $DayCount;

    /**
     * Days to be displayed
     * @var array<int, array{date: int, shorts: list<array<string, mixed>>, trainings: list<array<string, mixed>>}>
     */
    protected $Days;

    /**
     * @var array sport ids that are explicitly set as 'only icon'
     */
    protected $SportsShort = [];

    /**
     * @var array type ids that are explicitly set as 'only icon'
     */
    protected $TypesShort = [];

    /**
     * @var array type ids that are explicitly set as 'complete row'
     */
    protected $TypesNotShort = [];

    /**
     * @var array<int, array{name: string, icon: string}> distinct sports of the activities
     */
    protected $Sports = [];

    protected int $AccountID;

    protected \PDOforRunalyze $DB;

    protected \Runalyze\Dataset\Configuration $DatasetConfig;

    protected \Runalyze\Dataset\Query $DatasetQuery;

    protected \Runalyze\Model\Factory $Factory;

    /**
     * Dataset table used to render the additional columns, set by templateData()
     */
    protected ?View\Dataset\Table $Table = null;

    protected ?Dataset\Context $Context = null;

    /**
     * Number of additional columns
     */
    protected int $AdditionalColumns = 2;

    /**
     * Boolean flag: has current view no activities
     */
    protected bool $AllDaysEmpty = true;

    public function __construct()
    {
        $this->initInternalObjects();
        $this->initTimestamps();
        $this->initSports(); // #TSC
        $this->initDays();
    }

    /**
     * Init pointer to DB/Error-object
     */
    protected function initInternalObjects()
    {
        $this->AccountID = SessionAccountHandler::getId();
        $this->DB = DB::getInstance();
        $this->DatasetConfig = new Dataset\Configuration($this->DB, $this->AccountID);
        $this->DatasetQuery = new Dataset\Query($this->DatasetConfig, $this->DB, $this->AccountID);
        $this->DatasetQuery->setAdditionalColumns(['is_public']);

        $this->Factory = new Factory($this->AccountID);
    }

    /**
     * Init private timestamps from request
     */
    protected function initTimestamps()
    {
        if (! isset($_GET['start']) || ! isset($_GET['end']) || ! is_numeric($_GET['start']) || ! is_numeric($_GET['end'])) {
            $Mode = Configuration::DataBrowser()->mode();

            if ($Mode->showMonth()) {
                $this->TimestampStart = LocalTime::fromString('first day of this month 00:00:00')->getTimestamp();
                $this->TimestampEnd = LocalTime::fromString('last day of this month 23:59:59')->getTimestamp();
            } else {
                $this->TimestampStart = new LocalTime()->weekstart();
                $this->TimestampEnd = new LocalTime()->weekend();
            }
        } else {
            $this->TimestampStart = $_GET['start'];
            $this->TimestampEnd = $_GET['end'];
        }

        $this->DayCount = (int) round(($this->TimestampEnd - $this->TimestampStart) / 86400);
    }

    /**
     * Init sports where activity exists.
     * #TSC
     */
    protected function initSports()
    {
        // set the sport from the request to this model
        if (! isset($_GET['sport']) || ! is_numeric($_GET['sport'])) {
            $this->SportId = null;
        } else {
            $this->SportId = $_GET['sport'];
        }

        // get sports where activities exists
        $Statement = $this->DatasetQuery->statementToFetchActivitySports($this->TimestampStart, $this->TimestampEnd, $this->SportId);

        while ($sport = $Statement->fetch()) {
            $this->Sports[$sport['id']] = [
                'name' => $sport['name'],
                'icon' => $sport['img'],
            ];
        }
    }

    /**
     * Init all days for being displayed
     */
    protected function initDays()
    {
        $this->initShortModes();
        $this->initEmptyDays();

        $Statement = $this->DatasetQuery->statementToFetchActivities($this->TimestampStart, $this->TimestampEnd, $this->SportId);

        while ($Training = $Statement->fetch()) {
            $w = (int) Time::diffInDays($Training['time'], $this->TimestampStart);

            if (
                in_array($Training['typeid'], $this->TypesShort)
                || (! in_array($Training['typeid'], $this->TypesNotShort) && in_array($Training['sportid'], $this->SportsShort))
            ) {
                $this->Days[$w]['shorts'][] = $Training;
            } else {
                $this->Days[$w]['trainings'][] = $Training;
            }

            $this->AllDaysEmpty = false;
        }

        if (\Runalyze\Configuration::DataBrowser()->reverseMode()) {
            $this->Days = array_reverse($this->Days);
        }
    }

    /**
     * Init array with empty days
     */
    protected function initEmptyDays()
    {
        $this->Days = [];
        $date = new LocalTime($this->TimestampStart);
        $date->setTime(0, 0, 0);

        for ($w = 0; $w <= ($this->DayCount - 1); $w++) {
            $this->Days[] = [
                'date' => $date->getTimestamp(),
                'shorts' => [],
                'trainings' => [],
            ];

            $date->add(new \DateInterval('P1D'));
        }
    }

    /**
     * Init $this->sports_short
     */
    protected function initShortModes()
    {
        $this->SportsShort = $this->DB->query('SELECT `id` FROM `' . PREFIX . 'sport` WHERE `short`=1 AND accountid = ' . $this->AccountID)->fetchAll(PDO::FETCH_COLUMN);
        $this->TypesShort = $this->DB->query('SELECT `id` FROM `' . PREFIX . 'type` WHERE `short`=1 AND accountid = ' . $this->AccountID)->fetchAll(PDO::FETCH_COLUMN);
        $this->TypesNotShort = $this->DB->query('SELECT `id` FROM `' . PREFIX . 'type` WHERE `short`=0 AND accountid = ' . $this->AccountID)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Data for templates/my/data_browser.html.twig, which replaces the legacy
     * tpl/tpl.DataBrowser.php. The dataset cells are still rendered through the
     * legacy dataset table view.
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $showActiveDaysOnly = Configuration::DataBrowser()->showActiveDaysOnly();

        $Table = new View\Dataset\Table($this->DatasetConfig);
        $Context = new Dataset\Context(new \Runalyze\Model\Activity\Entity(), $this->AccountID);

        $this->Table = $Table;
        $this->Context = $Context;

        if ($this->DayCount < 25) {
            $weekSeparator = ' top-separated';
            $monthSeparator = ' top-separated-light';
        } else {
            $weekSeparator = ' top-separated-light';
            $monthSeparator = ' top-separated';
        }

        $days = [];
        $currentWeek = null;
        $currentMonth = null;

        foreach ($this->Days as $i => $day) {
            $trClass = '';
            $week = Configuration::General()->weekStart()->phpWeek($day['date'], true);
            $month = (int) LocalTime::date('n', $day['date']);

            if ($i > 0 && $week != $currentWeek) {
                $trClass = $weekSeparator;
            }

            if ($i > 0 && $month != $currentMonth) {
                $trClass = ($trClass === '') ? $monthSeparator : ' top-separated';
            }

            if (! empty($day['trainings'])) {
                $shortLinksHtml = $this->shortLinksHtml($day['shorts'], $Table, $Context);

                foreach ($day['trainings'] as $t => $Training) {
                    $Context->setActivityData($Training);

                    $id = (int) $Training['id'];
                    $wkClass = (bool) ($Training['is_race'] ?? false) ? ' wk' : '';
                    $rowTrClass = ($t == 0) ? $trClass : '';

                    $days[] = [
                        'tr_class' => $rowTrClass,
                        'row_class' => ' training' . $wkClass,
                        'row_attributes' => (Request::isOnSharedPage() && ! ($Training['is_public'] ?? false))
                            ? ''
                            : ' id="training_' . $id . '" ' . Ajax::trainingLinkAsOnclick($id),
                        'show_date_cells' => ($t == 0),
                        'short_links_html' => ($t == 0) ? $shortLinksHtml : '',
                        'date_html' => $this->dateString($day['date']),
                        'additional_columns_html' => $this->codeForAdditionalColumnsForActivity(),
                        'cells_html' => $Table->codeForColumns($Context),
                        'is_empty' => false,
                        'empty_colspan' => 0,
                    ];
                }

                $currentWeek = $week;
                $currentMonth = $month;
            } elseif (! $showActiveDaysOnly || ! empty($day['shorts'])) {
                $days[] = [
                    'tr_class' => $trClass,
                    'row_class' => '',
                    'row_attributes' => '',
                    'show_date_cells' => true,
                    'short_links_html' => $this->shortLinksHtml($day['shorts'], $Table, $Context),
                    'date_html' => $this->dateString($day['date']),
                    'additional_columns_html' => '',
                    'cells_html' => '',
                    'is_empty' => true,
                    'empty_colspan' => $Table->numColumns() + $this->AdditionalColumns - 2,
                ];

                $currentWeek = $week;
                $currentMonth = $month;
            }
        }

        $summaryRows = [];

        foreach ($this->DatasetQuery->fetchSummaryForAllSport($this->TimestampStart, $this->TimestampEnd, $this->SportId) as $data) {
            $Sport = $this->Factory->sport((int) $data['sportid']);
            $Context->setActivityData($data);
            $num = (int) ($data['num'] ?? 0);

            $summaryRows[] = [
                'label_html' => '<small>' . $num . 'x</small> ' . $Sport->name(),
                'cells_html' => $Table->codeForColumns($Context, [Dataset\Keys::SPORT]),
            ];
        }

        $sportLinks = [];

        foreach ($this->Sports as $id => $sport) {
            $sportLinks[] = $this->getSportLink((int) $id, $sport['name'], $sport['icon'], $this->SportId == $id);
        }

        return [
            'calendar_id' => self::CALENDAR_ID,
            'navigation_links' => $this->navigationLinks(),
            'title' => $this->title(),
            'icon_links' => $this->iconLinks(),
            'hover_links' => $this->hoverLinks(),
            'sport_links' => $sportLinks,
            'timestamp_start' => $this->TimestampStart,
            'timestamp_end' => $this->TimestampEnd,
            'locale_json' => $this->calendarLocaleJson(),
            'year_links' => $this->yearLinksData(),
            'show_labels' => Configuration::DataBrowser()->showLabels(),
            'column_labels_html' => $Table->codeForColumnLabels(),
            'additional_columns' => $this->AdditionalColumns,
            'days' => $days,
            'all_days_empty' => $this->AllDaysEmpty,
            'show_active_days_only' => $showActiveDaysOnly,
            'message_colspan' => $Table->numColumns() + $this->AdditionalColumns,
            'summary_rows' => $summaryRows,
        ];
    }

    /**
     * @param list<array<string, mixed>> $shorts
     */
    private function shortLinksHtml(array $shorts, View\Dataset\Table $table, Dataset\Context $context): string
    {
        $html = '';

        foreach ($shorts as $short) {
            $context->setActivityData($short);

            if (Request::isOnSharedPage() && ! $context->activity()->isPublic()) {
                $html .= $table->codeForShortLink($context);
            } else {
                $html .= '<span class="link" ' . Ajax::trainingLinkAsOnclick($context->activity()->id()) . '>' . $table->codeForShortLink($context) . '</span>';
            }
        }

        return $html;
    }

    private function calendarLocaleJson(): string
    {
        return (string) json_encode([
            'days' => [__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday')],
            'daysMin' => [__('Su'), __('Mo'), __('Tu'), __('We'), __('Th'), __('Fr'), __('Sa')],
            'daysShort' => [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')],
            'months' => [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')],
            'monthsShort' => [__('Jan'), __('Feb'), __('Mar'), __('Apr'), __('May'), __('Jun'), __('Jul'), __('Aug'), __('Sep'), __('Oct'), __('Nov'), __('Dec')],
            'weekMin' => _('wk'),
        ]);
    }

    /**
     * Links to navigate in calendar
     */
    public function navigationLinks(): string
    {
        // #TSC more space between for better usablility on small devices
        // #TSC use fa-lg for larger icons in the navigation (better for tablets)
        $span = '<span class="fa-lg" style="width: 25px; text-align: center; display: inline-block;">';

        return $span . $this->getCalenderLink() . '</span>'
            . $span . $this->getPrevLink() . '</span>'
            . $span . $this->getNextLink() . '</span>'
            . $span . $this->getCurrentLink() . '</span>';
    }

    /**
     * Display links to navigate in calendar
     */
    public function displayNavigationLinks(): void
    {
        echo $this->navigationLinks();
    }

    public function title(): string
    {
        $now = new LocalTime()->getTimestamp();
        $timestampForLinks = ($this->TimestampStart < $now && $now < $this->TimestampEnd) ? $now : $this->TimestampStart;
        $timeForLinks = new LocalTime($timestampForLinks);

        return DataBrowserLinker::monthLink(Time::month($timeForLinks->format('m')), $timestampForLinks, $this->SportId) . ', '
            . DataBrowserLinker::yearLink($timeForLinks->format('Y'), $timestampForLinks, $this->SportId) . ', '
            . DataBrowserLinker::weekLink(Configuration::General()->weekStart()->phpWeek($timestampForLinks, true) . '. ' . __('week'), $timestampForLinks, false, $this->SportId);
    }

    public function displayTitle(): void
    {
        echo $this->title();
    }

    /**
     * Specific icon-links
     */
    public function iconLinks(): string
    {
        return '<ul>'
            . '<li>' . $this->getSharedListLink() . '</li>'
            . '<li>' . $this->getMonthKmLink() . '</li>'
            . '<li>' . $this->getWeekKmLink() . '</li>'
            . '<li>' . $this->getAddLink() . '</li>'
            . '</ul>';
    }

    /**
     * Display specific icon-links
     */
    public function displayIconLinks(): void
    {
        echo $this->iconLinks();
    }

    public function hoverLinks(): string
    {
        return $this->getConfigLink() . $this->getRefreshLink();
    }

    public function displayHoverLinks(): void
    {
        echo $this->hoverLinks();
    }

    public function getAccountId(): int
    {
        return $this->AccountID;
    }

    public function getAdditionalColumns(): int
    {
        return $this->AdditionalColumns;
    }

    public function getAllDaysEmpty(): bool
    {
        return $this->AllDaysEmpty;
    }

    public function getDatasetConfig(): Dataset\Configuration
    {
        return $this->DatasetConfig;
    }

    public function getDatasetQuery(): Dataset\Query
    {
        return $this->DatasetQuery;
    }

    public function getDayCount(): int
    {
        return $this->DayCount;
    }

    /**
     * @return array<int, array{date: int, shorts: list<array<string, mixed>>, trainings: list<array<string, mixed>>}>
     */
    public function getDays(): array
    {
        return $this->Days;
    }

    public function getFactory(): Factory
    {
        return $this->Factory;
    }

    public function getSportId(): ?int
    {
        return $this->SportId;
    }

    /**
     * @return array<int, array{name: string, icon: string}>
     */
    public function getSports(): array
    {
        return $this->Sports;
    }

    public function getTimestampEnd(): int
    {
        return $this->TimestampEnd;
    }

    public function getTimestampStart(): int
    {
        return $this->TimestampStart;
    }

    /**
     * Display config link
     */
    protected function getConfigLink(): string
    {
        return Ajax::window('<a class="tab" href="settings/dataset">' . Icon::$CONF . '</a>');
    }

    /**
     * Get link to navigation back
     */
    protected function getPrevLink(): string
    {
        $timestamp_array = DataBrowserLinker::prevTimestamps($this->TimestampStart, $this->TimestampEnd);

        return DataBrowserLinker::link(Icon::$BACK, $timestamp_array['start'], $timestamp_array['end'], __('back'), '', $this->SportId);
    }

    /**
     * Get link to navigation forward
     */
    protected function getNextLink(): string
    {
        $timestamp_array = DataBrowserLinker::nextTimestamps($this->TimestampStart, $this->TimestampEnd);

        return DataBrowserLinker::link(Icon::$NEXT, $timestamp_array['start'], $timestamp_array['end'], __('next'), '', $this->SportId);
    }

    /**
     * Get link to jump to today
     */
    protected function getCurrentLink(): string
    {
        return DataBrowserLinker::link('<i class="fa fa-lg fa-fw fa-circle"></i>', 0, 0, __('today'), '', $this->SportId);
    }

    /**
     * Get ajax-link for reload this DataBrowser
     */
    protected function getRefreshLink(): string
    {
        $Link = DataBrowserLinker::link(Icon::$REFRESH, $this->TimestampStart, $this->TimestampEnd, '', '', $this->SportId);

        return str_replace('<a ', '<a id="' . self::REFRESH_BUTTON_ID . '" ' . Ajax::tooltip('', __('Reload current datasheet'), false, true) . ' ', $Link);
    }

    /**
     * Get link including the selection of one sport.
     * @param $active the link was clicked and is active
     */
    public function getSportLink(int $sportId, string $name, string $icon, bool $active): string
    {
        $style = 'padding-left: 2px; padding-right: 2px;';
        if ($active) {
            // if this sport is currently selected/active - the link is used for de-selection
            return DataBrowserLinker::link(
                '<i class="fa fa-fw ' . $icon . '" style="' . $style . ' color: black; font-weight:bolder; font-size: 16px; vertical-align: -2px;"></i>',
                $this->TimestampStart,
                $this->TimestampEnd,
                __($name),
                ''
            );
        }

        return DataBrowserLinker::link(
            '<i class="fa fa-lg fa-fw ' . $icon . '" style="' . $style . '"></i>',
            $this->TimestampStart,
            $this->TimestampEnd,
            __($name),
            '',
            $sportId
        );
    }

    /**
     * Get ajax-link for choosing timestamps from calendar
     */
    protected function getCalenderLink(): string
    {
        return '<span id="calendar-link" class="link" title="' . __('Calendar') . '">' . Icon::$CALENDAR . '</span>';
    }

    /**
     * Links to the previous 20 years, for the direct timerange selection
     * #TSC
     *
     * @return list<array{link_html: string, selected: bool}>
     */
    public function yearLinksData(): array
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $oneYear = new DateInterval('P1Y');
        $selectedYear = new LocalTime($this->TimestampStart)->format('Y');

        $links = [];

        for ($i = 0; $i < 20; $i++) {
            $year = $now->format('Y');

            $links[] = [
                'link_html' => DataBrowserLinker::yearLink($year, $now->getTimestamp(), $this->SportId),
                'selected' => $year === $selectedYear,
            ];

            $now = $now->sub($oneYear);
        }

        return $links;
    }

    /**
     * Get ajax-link for showing month-kilometer
     */
    protected function getMonthKmLink(): string
    {
        return Ajax::window('<a href="' . PlotSumData::$URL . '?type=month" ' . Ajax::tooltip('', __('Activity per month'), false, true) . '>' . Icon::$BARS_BIG . '</a>');
    }

    /**
     * Get ajax-link for showing week-kilometer
     */
    protected function getWeekKmLink(): string
    {
        return Ajax::window('<a href="' . PlotSumData::$URL . '?type=week" ' . Ajax::tooltip('', __('Activity per week'), false, true) . '>' . Icon::$BARS_SMALL . '</a>');
    }

    /**
     * Get list to shared list
     */
    protected function getSharedListLink(): string
    {
        return SharedLinker::getListLinkForCurrentUser();
    }

    /**
     * Get ajax-link for searching
     */
    protected function getNaviSearchLink(): string
    {
        return Ajax::window('<a href="' . SearchLink::$WINDOW_URL . '" ' . Ajax::tooltip('', __('Search for an activity'), false, true) . '>' . Icon::$SEARCH . '</a>', 'big');
    }

    /**
     * Get ajax-link for adding a training
     */
    protected function getAddLink(): string
    {
        return Ajax::window('<a href="' . self::ADD_URL . '">' . Icon::$ADD . ' ' . __('Add workout') . '</a>', 'small');
    }

    /**
     * Get ajax-link for adding a training at a given date
     * @param int $timestampInServerTime
     */
    protected function getAddLinkForDate(int $timestampInServerTime): string
    {
        if ($timestampInServerTime > time()) {
            return '<span style="opacity:.25;">' . Icon::$ADD_SMALL . '</span>';
        }

        $date = date('d.m.Y', $timestampInServerTime);

        return Ajax::window('<a href="' . self::ADD_URL_FORM . '?date=' . $date . '">' . Icon::$ADD_SMALL . '</a>', 'small');
    }

    /**
     * Get date string for given timestamp
     * @param int $timestampInNoTimezone
     */
    public function dateString($timestampInNoTimezone): string
    {
        $localTime = new LocalTime($timestampInNoTimezone);
        $addLink = '';
        $weekDay = Time::weekday($localTime->format('w'), true);

        if (Configuration::DataBrowser()->showCreateLink() && ! Request::isOnSharedPage()) {
            $addLink = $this->getAddLinkForDate($localTime->toServerTimestamp());
        }

        if ($localTime->isToday()) {
            $weekDay = '<strong>' . $weekDay . '</strong>';
        }

        return $localTime->format('d.m.') . ' ' . $addLink . ' ' . $weekDay;
    }

    /**
     * Additional columns that are shown next to date columns
     * The dataset table and the current context are provided as $this->Table/$this->Context by templateData().
     * @return string html string that must contain `$this->AdditionalColumns - 2` columns
     */
    public function codeForAdditionalColumnsForActivity(): string
    {
        return '';
    }
}
