<?php

/**
 * This file contains the class::SummaryTable
 * @package Runalyze\Plugins\Stats\RunalyzePluginStat_Statistiken
 */

use Runalyze\Dataset;

/**
 * Summary table for dataset/data browser
 *
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats\RunalyzePluginStat_Statistiken
 */
abstract class SummaryTable
{
    protected int $AccountID;

    protected \Runalyze\Dataset\Query $DatasetQuery;

    protected \Runalyze\View\Dataset\Table $DatasetTable;

    /**
     * Compare kilometers
     */
    protected bool $CompareKilometers = false;

    /**
     * Title
     */
    protected string $Title = '';

    /**
     * Timerange to group for one row
     */
    protected int $Timerange = 0;

    /**
     * First timestamp to consider
     */
    protected int $TimeStart = 0;

    /**
     * Last timestamp to consider
     */
    protected int $TimeEnd = 0;

    /**
     * Additional columns
     */
    protected int $AdditionalColumns = 0;

    /**
     * Construct summary table
     */
    public function __construct(
        Dataset\Configuration $datasetConfig, /**
     * Sport id
     * @var int sportid - if nothing is selected than -1
     */
        protected int $Sportid, /**
     * Year
     */
        protected int $Year
    ) {
        $this->AccountID = SessionAccountHandler::getId();
        $this->DatasetQuery = new Dataset\Query($datasetConfig, DB::getInstance(), $this->AccountID);
        $this->DatasetTable = new Runalyze\View\Dataset\Table($datasetConfig);
    }

    public function compareKilometers(bool $flag = true)
    {
        $this->CompareKilometers = $flag;
    }

    /**
     * Prepare summary
     */
    abstract protected function prepare();

    /**
     * Head for row
     */
    abstract protected function rowHead(int $index): string;

    protected function displayAdditionalColumns(array $data)
    {
    }

    /**
     * @throws Exception
     */
    private function checkPreparation()
    {
        if ($this->Timerange === 0 || ($this->TimeStart === 0 && $this->TimeEnd === 0)) {
            throw new Exception('Timerange, -start and -end have to be properly defined.');
        }
    }

    public function display()
    {
        $this->prepare();
        $this->checkPreparation();

        $this->displayTableHeader();
        $this->displayTableBody();
        $this->displayTableFooter();
    }

    protected function displayTableHeader()
    {
        echo '<table class="r fullwidth zebra-style">';
        echo '<thead><tr><th colspan="' . ($this->DatasetTable->numColumns() + 2 + $this->AdditionalColumns) . '">' . $this->Title . '</th></tr></thead>';
        echo '<tbody>';
    }

    protected function displayTableBody()
    {
        $maxIndex = ceil(($this->TimeEnd - $this->TimeStart) / $this->Timerange) - 1;
        $CompleteData = [];
        $CompleteResult = $this->DatasetQuery->fetchSummaryForTimerange($this->Sportid, $this->Timerange, $this->TimeStart, $this->TimeEnd);
        $Context = new Dataset\Context(new Runalyze\Model\Activity\Entity(), $this->AccountID);
        $hiddenKeys = [
            Dataset\Keys::SPORT,
        ];

        foreach ($CompleteResult as $Data) {
            $CompleteData[$Data['timerange']] = $Data;
        }

        for ($index = 0; $index <= $maxIndex; ++$index) {
            echo '<tr><td class="l"><span class="b">' . $this->rowHead($index) . '</span></td>';

            if (isset($CompleteData[$index]) && ! empty($CompleteData[$index])) {
                echo '<td class="small">' . $CompleteData[$index]['num'] . 'x</td>';

                $this->displayAdditionalColumns($CompleteData[$index]);

                if ($this->CompareKilometers) {
                    $value = isset($CompleteData[$index + 1]) ? $CompleteData[$index + 1]['distance'] : 0;
                    $CompleteData[$index][Dataset\Keys\Distance::KEY_DISTANCE_COMPARISON] = $value;
                }

                $Context->setActivityData($CompleteData[$index]);
                echo $this->DatasetTable->codeForColumns($Context, $hiddenKeys);
            } else {
                echo HTML::emptyTD($this->DatasetTable->numColumns() + 1 + $this->AdditionalColumns, '<em>' . __('No activities') . '</em>', 'c small');
            }

            echo '</tr>';
        }
    }

    protected function displayTableFooter()
    {
        echo '</tbody>';
        echo '</table>';
    }
}
