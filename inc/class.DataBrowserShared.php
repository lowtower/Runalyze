<?php

/**
 * This file contains class::DataBrowserShared
 * @package Runalyze\DataBrowser
 */

use Runalyze\Util\LocalTime;

/**
 * Shared version of DataBrowser
 * @author Hannes Christiansen
 * @package Runalyze\DataBrowser
 */
class DataBrowserShared extends DataBrowser
{
    /**
     * Number of additional columns
     */
    #[\Override]
    protected int $AdditionalColumns = 3;

    /**
     * Boolean flag: show public link for trainings
     */
    protected bool $ShowPublicLink = true;

    protected bool $ShowEditLink = false;

    /**
     * @param array<string, mixed> $query
     */
    public function __construct(
        int $accountId,
        array $query,
        private readonly bool $showPrivateActivities,
        private readonly string $athleteUrl
    ) {
        parent::__construct($accountId, $query, $athleteUrl);
    }

    /**
     * Init pointer to DB/Error-object
     */
    protected function initInternalObjects()
    {
        parent::initInternalObjects();

        if (! $this->showPrivateActivities) {
            $this->DatasetQuery->showOnlyPublicActivities();
        }
    }

    /**
     * Init private timestamps from request
     */
    protected function initTimestamps()
    {
        $start = $this->query['start'] ?? null;
        $end = $this->query['end'] ?? null;
        $this->TimestampStart = is_numeric($start) ? (int) $start : LocalTime::fromString('first day of this month 00:00:00')->getTimestamp();
        $this->TimestampEnd = is_numeric($end) ? (int) $end : LocalTime::fromString('last day of this month 23:59:59')->getTimestamp();

        $this->DayCount = (int) round(($this->TimestampEnd - $this->TimestampStart) / 86400);
    }

    /**
     * Get ajax-link for choosing timestamps from calendar
     */
    protected function getCalenderLink(): string
    {
        return '';
    }

    /**
     * Icon links: only the sum plots, without the surrounding list of the
     * default data browser
     */
    #[\Override]
    public function iconLinks(): string
    {
        return $this->getMonthKmLink() . $this->getWeekKmLink();
    }

    #[\Override]
    public function hoverLinks(): string
    {
        return $this->getRefreshLink();
    }

    /**
     * Get ajax-link for showing month-kilometer
     */
    protected function getMonthKmLink(): string
    {
        return Ajax::window('<a href="' . $this->athleteUrl . '?type=month">' . Ajax::tooltip(Icon::$BARS_BIG, __('Activity per month')) . '</a>');
    }

    /**
     * Get ajax-link for showing week-kilometer
     */
    protected function getWeekKmLink(): string
    {
        return Ajax::window('<a href="' . $this->athleteUrl . '?type=week">' . Ajax::tooltip(Icon::$BARS_SMALL, __('Activity per week')) . '</a>');
    }

    /**
     * Additional columns that are shown next to date columns
     * @return string html string that must contain `$this->AdditionalColumns - 2` columns
     */
    #[\Override]
    public function codeForAdditionalColumnsForActivity(): string
    {
        if (! $this->Table instanceof \Runalyze\View\Dataset\Table || ! $this->Context instanceof \Runalyze\Dataset\Context) {
            return '';
        }

        return $this->Table->codeForPublicIcon($this->Context);
    }
}
