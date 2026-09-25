<?php

/**
 * This file contains the class::RunalyzePluginStat_Statistiken
 * @package Runalyze\Plugins\Stats
 */

use Runalyze\Configuration;
use Runalyze\Util\Time;

$PLUGINKEY = 'RunalyzePluginStat_Statistiken';

/**
 * General statistics
 *
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats
 */
class RunalyzePluginStat_Statistiken extends PluginStat
{
    protected array $Sport = [];

    protected \Runalyze\Dataset\Configuration $DatasetConfig;

    /**
     * @codeCoverageIgnore
     */
    final public function name(): string
    {
        return __('Statistics');
    }

    /**
     * @codeCoverageIgnore
     */
    final public function description(): string
    {
        return __('Monthly and weekly summaries for all sports');
    }

    protected function setOwnNavigation()
    {
        $LinkList = '<li class="with-submenu"><span class="link">' . $this->getAnalysisType() . '</span><ul class="submenu">';
        $LinkList .= '<li' . ($this->dat === '' ? ' class="active"' : '') . '>' . $this->getInnerLink(__('General overview'), $this->sportid, $this->year, '') . '</li>';
        $LinkList .= '<li' . ($this->dat === 'allWeeks' ? ' class="active"' : '') . '>' . $this->getInnerLink(__('All training weeks'), $this->sportid, (! $this->showsSpecificYear()) ? date('Y') : $this->year, 'allWeeks') . '</li>';

        $LinkList .= '</ul></li>';

        $this->setToolbarNavigationLinks([$LinkList]);
    }

    private function getAnalysisType(): string
    {
        $types = [
            '' => __('General overview'),
            'allWeeks' => __('All training weeks'),
        ];

        return $types[$this->dat] ?? $types[''];
    }

    private function initVariables(): void
    {
        $this->Sport = SportFactory::DataFor($this->sportid);
        $this->DatasetConfig = new \Runalyze\Dataset\Configuration(DB::getInstance(), SessionAccountHandler::getId());

        require_once __DIR__ . '/class.SummaryTable.php';
        require_once __DIR__ . '/class.SummaryTable10Weeks.php';
        require_once __DIR__ . '/class.SummaryTableAllWeeks.php';
        require_once __DIR__ . '/class.SummaryTableAllYears.php';
        require_once __DIR__ . '/class.SummaryTableMonths.php';
    }

    protected function defaultYear(): int
    {
        return 6;
    }

    protected function prepareForDisplay()
    {
        if (! $this->showsSpecificYear() && $this->dat === 'allWeeks') {
            $this->dat = '';
        }

        $this->initVariables();

        $this->setSportsNavigation(true, true);
        $this->setYearsNavigation(true, true, true);
        $this->setOwnNavigation();

        $this->setHeaderWithSportAndYear();
    }

    /**
     * @codeCoverageIgnore
     */
    final protected function displayLongDescription(): void
    {
        echo HTML::p(
            __('This plugin shows summaries for all weeks, months or years to compare your overall training.')
        );
    }

    protected function initConfiguration()
    {
        $Configuration = new PluginConfiguration($this->id());
        $Configuration->addValue(new PluginConfigurationValueBool('compare_weeks', __('Compare kilometers per week'), '', true));
        $Configuration->addValue(new PluginConfigurationValueBool('show_streak', __('Show streak'), '', true));
        $Configuration->addValue(new PluginConfigurationValueInt('show_streak_days', __('Minimum number of days to show a streak (0 for always)'), '', 10));

        $this->setConfiguration($Configuration);
    }

    /**
     * @see PluginStat::displayContent()
     */
    protected function displayContent(): void
    {
        if ($this->wantToShowAllWeeks()) {
            $this->displayWeekTable(true);
        } else {
            $this->displayYearTable();

            if ($this->Configuration()->value('show_streak')) {
                $this->displayStreak();
            }

            $this->displayWeekTable();
        }
    }

    private function wantToShowAllWeeks(): bool
    {
        return $this->dat === 'allWeeks';
    }

    private function displayYearTable(): void
    {
        if ($this->year === -1) {
            $SummaryTable = new SummaryTableAllYears($this->DatasetConfig, $this->sportid, $this->year);
        } else {
            $SummaryTable = new SummaryTableMonths($this->DatasetConfig, $this->sportid, $this->year);

            if ($this->year === 6) {
                $SummaryTable->setMode(SummaryTableMonths::MODE_LAST_6);
            } elseif ($this->year === 12) {
                $SummaryTable->setMode(SummaryTableMonths::MODE_LAST_12);
            } else {
                $SummaryTable->setMode(SummaryTableMonths::MODE_YEAR);
            }
        }

        $SummaryTable->compareKilometers($this->Configuration()->value('compare_weeks'));
        $SummaryTable->display();
    }

    private function displayWeekTable(bool $showAllWeeks = false): void
    {
        if ($this->skipWeekTable($showAllWeeks)) {
            return;
        }

        if ($showAllWeeks) {
            $SummaryTable = new SummaryTableAllWeeks($this->DatasetConfig, $this->sportid, $this->year);
        } else {
            $SummaryTable = new SummaryTable10Weeks($this->DatasetConfig, $this->sportid, $this->year);
        }

        $SummaryTable->compareKilometers($this->Configuration()->value('compare_weeks'));
        $SummaryTable->display();
    }

    private function skipWeekTable(bool $showAllWeeks): bool
    {
        return ($this->showsAllYears() || ($this->showsSpecificYear() && $this->year != date('Y'))) && ! $showAllWeeks;
    }

    private function displayStreak(): void
    {
        $Query = '
			SELECT
				`time`,
				DATE(FROM_UNIXTIME(`time`)) as `day`
			FROM `' . PREFIX . 'training`
			WHERE `sportid`=' . Configuration::General()->runningSport() . ' AND `accountid`=' . SessionAccountHandler::getId() . '
			GROUP BY DATE(FROM_UNIXTIME(`time`))
			ORDER BY `day` DESC';

        $Request = DB::getInstance()->query($Query);

        $IsStreak = true;
        $FirstDay = true;
        $NumDays = 0;
        $LastTime = time();
        $LastDay = date('Y-m-d');
        $Text = '';

        while ($IsStreak) {
            $Day = $Request->fetch();
            if (! $Day) {
                break;
            }

            if ($FirstDay) {
                if ($Day['day'] != $LastDay) {
                    if (Time::diffOfDates($Day['day'], $LastDay) === 1) {
                        $Text = __('If you run today: ');
                        $NumDays++;
                    } else {
                        $IsStreak = false;
                    }
                }

                $FirstDay = false;
            }

            if ($IsStreak) {
                if (Time::diffOfDates($Day['day'], $LastDay) <= 1) {
                    $NumDays++;
                    $LastDay = $Day['day'];
                    $LastTime = $Day['time'];
                } else {
                    $IsStreak = false;
                }
            }
        }

        if ($NumDays >= $this->Configuration()->value('show_streak_days')) {
            if ($NumDays === 0) {
                $Text .= __("You don't have a streak. Go out and start one!");
                $LastTraining = DB::getInstance()->query('SELECT time FROM `' . PREFIX . 'training` WHERE `sportid`=' . Configuration::General()->runningSport() . ' AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `time` DESC LIMIT 1')->fetch();

                if (isset($LastTraining['time'])) {
                    $Text .= ' ' . sprintf(__('Your last run was on %s'), \Runalyze\Util\LocalTime::date('d.m.Y', $LastTraining['time']));
                }
            } else {
                $Text .= sprintf(_n('%d day of running since %s', '%d days of running since %s', $NumDays), $NumDays, \Runalyze\Util\LocalTime::date('d.m.Y', $LastTime));
            }

            echo '<p class="text c"><em>' . $Text . '</em></p>';
        }
    }
}
