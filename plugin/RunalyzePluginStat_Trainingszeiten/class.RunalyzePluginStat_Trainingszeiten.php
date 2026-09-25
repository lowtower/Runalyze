<?php

/**
 * This file contains the class::RunalyzePluginStat_Trainingszeiten
 * @package Runalyze\Plugins\Stats
 */
$PLUGINKEY = 'RunalyzePluginStat_Trainingszeiten';

use Runalyze\Model\Activity;
use Runalyze\View\Activity\Dataview;
use Runalyze\View\Activity\Linker;

/**
 * Plugin "Trainingszeiten"
 *
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats
 */
class RunalyzePluginStat_Trainingszeiten extends PluginStat
{
    protected bool $dataIsMissing = false;

    /**
     * @codeCoverageIgnore
     */
    final public function name(): string
    {
        return __('Training times');
    }

    /**
     * @codeCoverageIgnore
     */
    final public function description(): string
    {
        return __('Plot all your activity times by daytime/weekday and list your nightly activities.');
    }

    protected function initConfiguration()
    {
        $Configuration = new PluginConfiguration($this->id());
        $Configuration->addValue(new PluginConfigurationValueBool('show_extreme_times', __('Show nightly activities'), '', true));

        $this->setConfiguration($Configuration);
    }

    protected function prepareForDisplay()
    {
        $this->setYearsNavigation(true, true, true);
        $this->setSportsNavigation(true, true);

        $this->setHeaderWithSportAndYear();
    }

    /**
     * @codeCoverageIgnore
     */
    protected function defaultSport(): int
    {
        return -1;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function titleForAllYears(): string
    {
        return __('Total');
    }

    /**
     * @see PluginStat::displayContent()
     */
    protected function displayContent(): void
    {
        if (! $this->dataIsMissing) {
            $this->displayImages();
        } else {
            echo HTML::em(__('No data available.'));
        }

        if ($this->Configuration()->value('show_extreme_times')) {
            $this->displayTable();
        }
    }

    private function displayTable(): void
    {
        if ($this->sportid > 0) {
            $sports_not_short = $this->sportid . ',';
        } else {
            $sports_not_short = '';
            $sports = DB::getInstance()->query('SELECT `id` FROM `' . PREFIX . 'sport` WHERE `accountid`=' . SessionAccountHandler::getId() . ' AND `short`=0')->fetchAll();
            foreach ($sports as $sport) {
                $sports_not_short .= $sport['id'] . ',';
            }
        }

        $sportsIdList = $sports_not_short === '' ? '0' : substr($sports_not_short, 0, -1);

        $nights = DB::getInstance()->query('SELECT * FROM (
            SELECT
                id,
                time,
                s,
                sportid,
                distance,
                is_track,
                HOUR(FROM_UNIXTIME(`time`)) as `H`,
                MINUTE(FROM_UNIXTIME(`time`)) as `MIN`
            FROM `' . PREFIX . 'training`
            WHERE
                `sportid` IN(' . $sportsIdList . ') AND
                                `accountid` = ' . SessionAccountHandler::getId() . ' AND
                (HOUR(FROM_UNIXTIME(`time`))!=0 OR MINUTE(FROM_UNIXTIME(`time`))!=0) 
                ' . $this->getYearDependenceForQuery() . '
            ORDER BY
                ABS(12-(`H`+10)%24-`MIN`/60) ASC,
                `MIN` DESC LIMIT 20
        ) t
        ORDER BY
            (`H`+12)%24 ASC,
            `MIN` ASC')->fetchAll();

        if (empty($nights)) {
            $this->dataIsMissing = true;
            return;
        }

        echo '<table class="fullwidth zebra-style">';
        echo '<thead><tr class="b c"><th colspan="8">' . __('Nightly activities') . '</th></tr></thead>';
        echo '<tbody>';

        $totalCount = count($nights);

        foreach ($nights as $i => $data) {
            $Activity = new Activity\Entity($data);
            $Linker = new Linker($Activity);
            $View = new Dataview($Activity);

            if ($this->shouldOpenRow($i)) {
                echo '<tr>';
            }

            echo '<td class="b">' . $View->daytime() . '</td>
                <td>' . $Linker->linkWithSportIcon() . '</td>
                <td>' . $View->distanceOrDuration() . ' ' . SportFactory::name($Activity->sportid()) . '</td>
                <td>' . $Linker->weekLink() . '</td>';

            if ($this->shouldCloseRow($i, $totalCount)) {
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        // TODO: Find a better description.
        echo '<p class="text">';
        echo __('2 a.m. is considered as <em>most extreme</em> time for a training. ');
        echo __('The 20 trainings being nearest to that time are listed.');
        echo '</p>';
    }

    private function shouldOpenRow(int $index): bool
    {
        return $index % 2 === 0;
    }

    private function shouldCloseRow(int $index, int $totalCount): bool
    {
        return $index % 2 === 1 || $index === $totalCount - 1;
    }

    private function displayImages(): void
    {
        echo '<div style="max-width:750px;margin:0 auto;">';
        echo '<span class="right">';
        echo Plot::getDivFor('weekday', 350, 190);
        echo '</span>';
        echo '<span class="left">';
        echo Plot::getDivFor('daytime', 350, 190);
        echo '</span>';
        echo HTML::clearBreak();
        echo '</div>';

        include FRONTEND_PATH . '../plugin/' . $this->key() . '/Plot.Daytime.php';
        include FRONTEND_PATH . '../plugin/' . $this->key() . '/Plot.Weekday.php';
    }
}
