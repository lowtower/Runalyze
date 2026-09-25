<?php

/**
 * This file contains the class of the RunalyzePluginStat "Trainingspartner".
 * @package Runalyze\Plugins\Stats
 */
$PLUGINKEY = 'RunalyzePluginStat_Trainingspartner';

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats
 */
class RunalyzePluginStat_Trainingspartner extends PluginStat
{
    protected array $Partner = [];

    protected int $TrainingsWithPartner = 0;

    protected int $TrainingsTotal = 0;

    /**
     * @codeCoverageIgnore
     */
    final public function name(): string
    {
        return __('Training partner');
    }

    /**
     * @codeCoverageIgnore
     */
    final public function description(): string
    {
        return __('How often did you train with whom?');
    }

    protected function prepareForDisplay()
    {
        $this->setSportsNavigation(true, true);
        $this->setYearsNavigation(true, true, true);

        $this->setHeaderWithSportAndYear();

        $this->initTrainingspartner();
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
        return __('All years');
    }

    /**
     * @see PluginStat::displayContent()
     */
    protected function displayContent(): void
    {
        echo '<table class="fullwidth zebra-style">';
        echo '<thead><tr><th colspan="2">' . __('All training partners') . '</th></tr></thead>';
        echo '<tbody>';

        if ($this->Partner === []) {
            echo '
				<tr>
					<td class="b">0x</td>
					<td><em>' . __('You have only trained on your own.') . '</em></td>
				</tr>';
        } else {
            echo $this->renderPartnerRows($this->Partner);
        }

        echo '</tbody>';
        echo '</table>';

        echo '<p class="text">';
        echo sprintf(__('You have trained <strong>%sx</strong> in total and out of that <strong>%sx</strong> with a training partner, '), $this->TrainingsTotal, $this->TrainingsWithPartner);
        echo sprintf(__('that are <strong>%s</strong> &#37;.'), $this->TrainingsTotal === 0 ? 0 : round(100 * $this->TrainingsWithPartner / $this->TrainingsTotal));
        echo '</p>';
    }

    /**
     * @param array<string, int> $partners [partner name => count], already
     *                                      sorted descending by count
     */
    private function renderPartnerRows(array $partners): string
    {
        $html = '';
        $rowCount = null;
        $rowIsOpen = false;

        foreach ($partners as $name => $nameCount) {
            if ($rowCount === $nameCount) {
                $html .= ', ';
            } else {
                if ($rowIsOpen) {
                    $html .= '</td></tr>';
                }

                $rowCount = $nameCount;
                $rowIsOpen = true;
                $html .= '<tr><td class="b">' . $rowCount . 'x</td><td>';
            }

            $html .= SearchLink::to('partner', $name, $name, 'like');
        }

        if ($rowIsOpen) {
            $html .= '</td></tr>';
        }

        return $html;
    }

    protected function initTrainingspartner()
    {
        $Query = 'SELECT `partner` FROM `' . PREFIX . 'training` WHERE `accountid`=' . SessionAccountHandler::getId() . ' AND `partner`!=""';
        $Query .= $this->getSportAndYearDependenceForQuery();

        $trainings = DB::getInstance()->query($Query)->fetchAll();

        $this->TrainingsWithPartner = count($trainings);
        $this->TrainingsTotal = DB::getInstance()->query('SELECT COUNT(*) FROM `' . PREFIX . 'training` WHERE 1' . $this->getSportAndYearDependenceForQuery() . ' AND accountid = ' . SessionAccountHandler::getId())->fetchColumn();

        if (empty($trainings)) {
            return;
        }

        foreach ($trainings as $training) {
            $trainingspartner = explode(',', $training['partner']);
            foreach ($trainingspartner as $name) {
                $name = trim($name);

                if (! isset($this->Partner[$name])) {
                    $this->Partner[$name] = 1;
                } else {
                    $this->Partner[$name]++;
                }
            }
        }

        array_multisort($this->Partner, SORT_DESC);
    }
}
