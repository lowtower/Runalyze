<?php

/**
 * This file contains the class of the RunalyzePluginPanel "Ziele".
 * @package Runalyze\Plugins\Panels
 */

use Runalyze\Activity\Distance;
use Runalyze\Configuration;
use Runalyze\Plugins\Goals\GoalsProgressCalculator;
use Runalyze\Plugins\Goals\GoalsTimeRangeProvider;
use Runalyze\Util\LocalTime;

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
        $start = $Timeset['start'];
        $end = $Timeset['end'];
        $today = new DateTime(date('Y-m-d'));
        $totals = $this->fetchDataSince($start->getTimestamp());

        $this->Lines = new GoalsProgressCalculator()->calculate(
            $start,
            $end,
            $today,
            (float) $totals['distanz_sum'],
            (int) $totals['anzahl'],
            (float) $goal,
            (bool) $this->Configuration()->value('details')
        );

        $dauer = 1 + (int) date_diff($start, $end)->format('%a');

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

        echo '<div style="padding-left:5px; padding-right:7px; padding-bottom:2px">'
            . '<div style="border: 1px solid black; padding: 1px; background-color: transparent; width: 100%; height: 15px">'
            . '<span style="position: absolute; right: 15px">' . round($sumPercentage, 1) . '%&nbsp;</span>'
            . '<span style="position: absolute; left: 15px">' . $Bar['name'] . '</span>'
            . '<div class="progress_bar" style="float: left; background-color:' . $Bar['color'] . '; width:' . round($percentage) . '%;">&nbsp;</div>'

            . ($Bar['add'] ? '<div id="progress_bar_add" style="float: left; background-color:' . $Bar['addcolor'] . '; width:' . round($addPercentage) . '%;">&nbsp;</div>' : '')
            . '</div>'
            . '</div>';
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

        echo '<p' . $p_format . '>'
            . '<span class="right">'
            . $NumberOfActivities . ' ' . $Distance
            . '</span>'
            . '<span' . $span_format . '>' . $Line['name'] . '</span>'
            . '</p>';
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
        return new GoalsTimeRangeProvider()->getRanges(
            new DateTimeImmutable(),
            Configuration::General()->weekStart()->isMonday()
        );
    }
}
