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

    protected function codeForAdditionalColumns(array $data): string
    {
        return '';
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
        foreach ($this->rowsTemplateData() as $row) {
            echo '<tr><td class="l"><span class="b">' . $row['head'] . '</span></td>';

            if ($row['has_data']) {
                echo '<td class="small">' . $row['num'] . 'x</td>';
                echo $row['additional_columns'];
                echo $row['columns'];
            } else {
                echo '<td colspan="' . $row['empty_colspan'] . '" class="c small"><em>' . __('No activities') . '</em></td>';
            }

            echo '</tr>';
        }
    }

    /**
     * Data for the twig template shared/athlete/summary_table.html.twig
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $this->prepare();
        $this->checkPreparation();

        return [
            'title' => $this->Title,
            'colspan' => $this->DatasetTable->numColumns() + 2 + $this->AdditionalColumns,
            'rows' => $this->rowsTemplateData(),
        ];
    }

    /**
     * Template data of all rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function rowsTemplateData(): array
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

        $rows = [];

        for ($index = 0; $index <= $maxIndex; ++$index) {
            if (isset($CompleteData[$index]) && ! empty($CompleteData[$index])) {
                $additionalColumns = $this->codeForAdditionalColumns($CompleteData[$index]);

                if ($this->CompareKilometers) {
                    $value = isset($CompleteData[$index + 1]) ? $CompleteData[$index + 1]['distance'] : 0;
                    $CompleteData[$index][Dataset\Keys\Distance::KEY_DISTANCE_COMPARISON] = $value;
                }

                $Context->setActivityData($CompleteData[$index]);

                $rows[] = [
                    'head' => $this->rowHead($index),
                    'has_data' => true,
                    'num' => $CompleteData[$index]['num'],
                    'additional_columns' => $additionalColumns,
                    'columns' => $this->DatasetTable->codeForColumns($Context, $hiddenKeys),
                    'empty_colspan' => $this->DatasetTable->numColumns() + 1 + $this->AdditionalColumns,
                ];
            } else {
                $rows[] = [
                    'head' => $this->rowHead($index),
                    'has_data' => false,
                    'num' => null,
                    'additional_columns' => '',
                    'columns' => '',
                    'empty_colspan' => $this->DatasetTable->numColumns() + 1 + $this->AdditionalColumns,
                ];
            }
        }

        return $rows;
    }

    protected function displayTableFooter()
    {
        echo '</tbody>';
        echo '</table>';
    }
}
