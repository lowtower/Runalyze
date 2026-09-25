<?php

/**
 * This file contains the class of the RunalyzePluginPanel "Ziele".
 * @package Runalyze\Plugins\Panels
 */

use Runalyze\Activity\Distance;
use Runalyze\Configuration;
use Runalyze\Util\LocalTime;
use Runalyze\Util\Time;

$PLUGINKEY = 'RunalyzePluginPanel_Ziele';

/**
 * @author Ulrich Kiermayr <ulrich@kiermayr.at>
 * License: GPL
 * @package Runalyze\Plugins\Panels
 */
class RunalyzePluginPanel_Ziele extends PluginPanel
{
    /**
     * @var array<mixed, array<string, mixed>>
     */
    private array $Lines = [];

    /**
     * @codeCoverageIgnore
     */
    final public function name(): string
    {
        return __('Goals');
    }

    /**
     * @codeCoverageIgnore
     */
    final public function description(): string
    {
        return __('Set your own goals for different time ranges and compare your current performance with them.');
    }

    protected function displayLongDescription(): void
    {
        echo HTML::p(__('This plugin tracks your distances for chosen time ranges.'));
        echo HTML::p(__('You can set a goal (value &gt; 0) for every time range. If it is set, you will see how much you have to run to reach it.'));
        echo HTML::p(__('A virtual Pace Bunny &lt;em&gt;reaches&lt;/em&gt; the goal with a steady performance - you can see how far you are ahead or behind.'));
        echo HTML::p(__('Note: &quot;Saison&quot; refers to the current season in the german &quot;kmspiel&quot;.'));
    }

    protected function initConfiguration(): void
    {
        $Configuration = new PluginConfiguration($this->id());

        foreach ($this->getTimeset() as $i => $timeset) {
            $ShowHint = sprintf(__('Show a prognosis for the current %s'), $timeset['name']);
            $GoalHint = sprintf(__('Current time range: %s to %s'), $timeset['start']->format('d.m.Y'), $timeset['end']->format('d.m.Y'));

            if (isset($timeset['note'])) {
                $ShowHint .= '<br>' . $timeset['note'];
                $GoalHint .= '<br>' . $timeset['note'];
            }

            $ShowGoal = new PluginConfigurationValueBool('ziel_show_' . $i, sprintf(__('%s: show'), $timeset['name']), $ShowHint, true);
            $Goal = new PluginConfigurationValueInt('ziel_' . $i, sprintf(__('%s: goal'), $timeset['name']), $GoalHint, 0);

            $Configuration->addValue($ShowGoal);
            $Configuration->addValue($Goal);
        }

        $showDetails = new PluginConfigurationValueBool('details', __('Show details'), '', true);
        $Configuration->addValue($showDetails);
        $this->setConfiguration($Configuration);
    }

    /**
     * @see PluginPanel::getRightSymbol()
     */
    protected function getRightSymbol(): string
    {
        $isFirst = true;
        $Code = '<ul class="submenu">';
        foreach ($this->getTimeset() as $i => $timeset) {
            if (! $this->Configuration()->value('ziel_show_' . $i)) {
                continue;
            }

            $Code .= '<li>' . Ajax::change($timeset['name'], 'bunny', '#bunny_' . $i) . '</li>';
            if ($isFirst) {
                $Code = '<li class="with-submenu"><span class="link">' . $timeset['name'] . '</span>' . $Code;
                $isFirst = false;
            }
        }

        $Code .= '</ul>';
        $Code .= '</li>';

        return '<ul>' . $Code . '</ul>';
    }

    /**
     * @see PluginPanel::displayContent()
     */
    protected function displayContent(): void
    {
        echo '<div id="bunny">';

        $isFirst = true;
        foreach ($this->getTimeset() as $i => $timeset) {
            if (! $this->Configuration()->value('ziel_show_' . $i)) {
                continue;
            }

            echo '<div id="bunny_' . $i . '" class="change"' . ($isFirst ? '' : ' style="display:none;"') . '>';
            $this->showTimeset($timeset, $this->Configuration()->value('ziel_' . $i));
            echo HTML::clearBreak();
            echo '</div>';

            $isFirst = false;
        }

        echo '</div>';
    }

    /**
     * @param array<string, mixed> $Timeset
     */
    private function showTimeset(array &$Timeset, int|float $goal): void
    {
        $this->clearLines();

        $showDetails = $this->Configuration()
            ->value('details');

        // Some Numbers we need
        $start = $Timeset['start'];
        $now = new DateTime(date('Y-m-d'));
        $end = $Timeset['end'];
        $days = 1 + (int) date_diff($start, $now)
            ->format('%a');
        $dauer = 1 + (int) date_diff($start, $end)
            ->format('%a');
        $rest = $dauer - $days;

        $dat = $this->fetchDataSince($start->getTimestamp());
        if ($dat['distanz_sum'] > 0) {
            $this->addHeadline(__('Current'), $dat['distanz_sum'], $dat['anzahl'], true);
            if ($showDetails) {
                $this->addLine(__('avg.') . ' ' . __('Day'), $days > 0 ? $dat['distanz_sum'] / $days : 0);

                if ($days > 7) {
                    $this->addLine(__('avg.') . ' ' . __('Week'), 7 * $dat['distanz_sum'] / $days, $dat['anzahl'] / $days * 7);
                }

                if ($days > 0) {
                    $this->addHeadline(__('Prognosis'), $dat['distanz_sum'] / $days * $dauer, $dat['anzahl'] / $days * $dauer);
                }
            }
        }

        if ($goal > 0) {
            $this->addHeadline(__('Goal'), $goal);
            if ($dat['distanz_sum'] < $goal) {
                $togo = $goal - $dat['distanz_sum'];
                $this->addLine(sprintf(__('%d days left'), $rest), $togo);
                if ($showDetails) {

                    $this->addLine(__('avg.') . ' ' . __('Day'), $rest > 0 ? $togo / $rest : 0);

                    if ($rest > 7) {
                        $this->addLine(__('avg.') . ' ' . __('Week'), 7 * $togo / $rest);
                    }
                }
            } else {
                $this->addLine(__('Goal reached.'));
            }

            $diff = $goal / $dauer * $days - $dat['distanz_sum'];

            if ($showDetails) {

                $this->addHeadline(__('Pace Bunny'), $goal / $dauer * $days);
                $this->addLine(__('avg.') . ' ' . __('Day'), $goal / $dauer);

                if ($diff > 0) {
                    $this->addLine(__('Behind'), abs($diff));
                } else {
                    $this->addLine(__('Ahead'), abs($diff));
                }

                $this->addHeadline(__('Head 2 Head'));
            }

            $this->addBar(__('Me'), $dat['distanz_sum'], $goal, $diff > 0 ? '#f99' : '#9f9');
            $this->addBar(__('Pace&nbsp;Bunny'), $goal / $dauer * ($days - 1), $goal, '#ccf', $goal / $dauer, '#ddf');
        }

        foreach ($this->Lines as $Line) {
            $this->showLineOrBar($Line);
        }

        echo '<small class="right">' . sprintf(__('%s to %s (%d days)'), $start->format('d.m.Y'), $end->format('d.m.Y'), $dauer) . '</small>';
    }

    private function clearLines(): void
    {
        $this->Lines = [];
    }

    private function addHeadline(string $name, int|float $distance = 0, int|float $num = 0, bool $noTopBorder = false): void
    {
        $this->Lines[] = [
            'name' => $name,
            'lvl' => 1,
            'sep' => ! $noTopBorder,
            'km' => $distance,
            'anz' => $num,
        ];
    }

    private function addLine(string $name, int|float $distance = 0, int|float $num = 0): void
    {
        $this->Lines[] = [
            'name' => $name,
            'lvl' => 2,
            'km' => $distance,
            'anz' => $num,
        ];
    }

    private function addBar(string $name, int|float $val, int|float $max, string $color, int|float|null $add = null, ?string $addcolor = null): void
    {
        $this->Lines[] = [
            'name' => $name,
            'type' => 'bar',
            'val' => $val,
            'max' => $max,
            'color' => $color,
            'add' => $add,
            'addcolor' => $addcolor,
        ];
    }

    /**
     * @param array<string, mixed> $Array
     */
    private function showLineOrBar(array &$Array): void
    {
        if (isset($Array['type']) && $Array['type'] == 'bar') {
            $this->showBar($Array);
        } else {
            $this->showLine($Array);
        }
    }

    /**
     * @param array<string, mixed> $Bar
     */
    private function showBar(array &$Bar): void
    {
        $percentage = min(100, 100 * $Bar['val'] / $Bar['max']);
        $addPercentage = $Bar['add'] ? 100 * $Bar['add'] / $Bar['max'] : 0;
        $sumPercentage = $percentage + $addPercentage;

        echo '<div style="padding-left:5px; padding-right:7px; padding-bottom:2px">' .
            '<div style="border: 1px solid black; padding: 1px; background-color: transparent; width: 100%; height: 15px">' .
            '<span style="position: absolute; right: 15px">' . round($sumPercentage, 1) . '%&nbsp;</span>' .
            '<span style="position: absolute; left: 15px">' . $Bar['name'] . '</span>' .
            '<div class="progress_bar" style="float: left; background-color:' . $Bar['color'] . '; width:' . round($percentage) . '%;">&nbsp;</div>' .

            ($Bar['add'] ? '<div id="progress_bar_add" style="float: left; background-color:' . $Bar['addcolor'] . '; width:' . round($addPercentage) . '%;">&nbsp;</div>' : '') .
            '</div>' .
            '</div>';
    }

    /**
     * @param array<string, mixed> $Line
     */
    private function showLine(array &$Line): void
    {
        // Fixed: default to an empty string instead of the integer 0.
        // Previously, a $Line without a 'lvl' key produced $span_format = 0,
        // which concatenated into '<span0>' below - malformed HTML. This
        // path was not reachable through addHeadline()/addLine() (both
        // always set 'lvl'), but the guard was missing regardless.
        $span_format = isset($Line['lvl']) ? $this->getLevelStyle($Line['lvl']) : '';
        $p_format = isset($Line['sep']) && $Line['sep'] ? ' style="border-top:1px solid #ccc;"' : '';

        $NumberOfActivities = isset($Line['anz']) && $Line['anz'] > 0 ? '<small><small>(' . round($Line['anz'], 1) . 'x)</small></small>' : '';
        // Fixed: removed the extra literal space before $span_format ('<span '
        // became '<span'), since $span_format itself already starts with a
        // leading space whenever it is non-empty (see getLevelStyle()).
        // This previously produced a double space, e.g. '<span  style="...">'.
        $Distance = isset($Line['km']) && $Line['km'] > 0 ? '<span' . ($Line['lvl'] == 1 ? $span_format : '') . '>' . Distance::format($Line['km'], true, 1) . '</span>' : '';

        echo '<p' . $p_format . '>' .
            '<span class="right">' .
            $NumberOfActivities . ' ' . $Distance .
            '</span>' .
            '<span' . $span_format . '>' . $Line['name'] . '</span>' .
            '</p>';
    }

    private function getLevelStyle(int $level): string
    {
        if ($level === 1) {
            return ' style="font-weight:bold;"';
        }

        if ($level === 2) {
            return ' style="padding-left: 10px;"';
        }

        return '';
    }

    private function fetchDataSince(int $timestamp): array
    {
        $Data = DB::getInstance()->query('
			SELECT
				`sportid`,
				COUNT(`id`) as `anzahl`,
				SUM(`distance`) as `distanz_sum`,
				SUM(`s`) as `dauer_sum`
			FROM `' . PREFIX . 'training`
			WHERE
				`sportid`=' . Configuration::General()->runningSport() . ' AND
				`time` >= ' . LocalTime::fromServerTime($timestamp)->getTimestamp() . ' AND
				`accountid`=' . SessionAccountHandler::getId() . '
			GROUP BY `sportid`
			ORDER BY `distanz_sum` DESC, `dauer_sum` DESC
		')->fetch();
        if (! is_array($Data)) {
            return [
                'anzahl' => 0,
                'distanz_sum' => 0,
                'dauer_sum' => 0,
            ];
        }

        return $Data;
    }

    private function getTimeset(): array
    {
        $timeset = [];

        // km-Spiel Saisonen
        $kmstart = new DateTime();
        $kmstart->setTime(0, 0, 0);

        $kmend = new DateTime();
        $kmend->setTime(0, 0, 0);

        $now = new DateTime('now');
        $kmstart->setISODate((int) date('Y'), 27, 1);

        if ($kmstart > $now) {
            $kmstart->setISODate((int) date('Y'), 1, 1);
            $kmend->setISODate((int) date('Y'), 26, 7);
        } else {
            $weeks = date('W', strtotime(date('Y') . '-12-31'));
            $kmend->setISODate((int) date('Y'), $weeks == 53 ? 53 : 52, 7);
        }

        $timeset['woche'] = [
            'name' => __('Week'),
            'start' => (new DateTime())->setTimestamp(Time::weekstart(time())),
            'end' => (new DateTime())->setTimestamp(Time::weekend(time())),
        ];
        $timeset['mon'] = [
            'name' => __('Month'),
            'start' => new DateTime(date('Y-m-01')),
            'end' => new DateTime(date('Y-m-t')),
        ];
        $timeset['hj'] = [
            'name' => __('Half-Year'),
            'start' => new DateTime(date('m') < 7 ? date('Y-01-01') : date('Y-07-01')),
            'end' => new DateTime(date('m') < 7 ? date('Y-06-30') : date('Y-12-31')),
        ];
        $timeset['saison'] = [
            'name' => __('Season'),
            'start' => $kmstart,
            'end' => $kmend,
            'note' => __('Note: Season means the current season in the german &quot;kmspiel&quot;'),
        ];
        $timeset['jahr'] = [
            'name' => __('Year'),
            'start' => new DateTime(date('Y-01-01')),
            'end' => new DateTime(date('Y-12-31')),
        ];

        return $timeset;
    }
}
