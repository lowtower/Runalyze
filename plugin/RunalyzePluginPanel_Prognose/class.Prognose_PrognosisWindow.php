<?php

/**
 * This file contains the class Prognose_PrognosisWindow
 * @package Runalyze\Plugins\Panels
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Calculation\BasicEndurance;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Plugins\Prognose\PrognoseCalculatorFormInput;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelFactory;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelHintRenderer;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPredictionRows;
use Runalyze\Plugins\Prognose\PrognoseCalculatorResultTableRenderer;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;
use Runalyze\Util\LocalTime;
use Twig\Environment;

/**
 * Prognosis calculator window
 *
 * Additional window for calculating special prognoses.
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Panels
 */
class Prognose_PrognosisWindow
{
    public const int DISTANCE_PRECISION = 2;

    protected PrognosisInterface $PrognosisObject;

    protected array $Distances = [];

    /**
     * @var array<mixed, array<string, string>>
     */
    protected array $Prognoses = [];

    protected string $ResultTable = '';

    protected array $InfoLines = [];

    protected DistanceUnitSystem $UnitSystem;

    /**
     * @var array<string, mixed>
     */
    protected array $Input = [];

    /**
     * @param array<int, array<string, int|float|null>> $topResults rows as returned by TrainingRepository::findTopResults()
     * @param array<int, mixed> $distances
     */
    public function __construct(
        private readonly array $topResults,
        private readonly array $distances,
        private readonly Account $account,
        private readonly int $runningSportId,
        private readonly PrognoseCalculatorPredictionRows $predictionRows,
        private readonly PrognoseCalculatorModelFactory $modelFactory,
        private readonly PrognoseCalculatorFormInput $formInput,
        private readonly PrognoseCalculatorResultTableRenderer $resultTableRenderer,
        private readonly PrognoseCalculatorModelHintRenderer $modelHintRenderer,
        private readonly Environment $twig,
        private readonly array $submittedInput
    ) {
        $this->setDefaultValues();
        $this->readPostData();
        $this->runCalculations();
        $this->fillResultTable();
    }

    protected function setDefaultValues()
    {
        $this->UnitSystem = Configuration::General()->distanceUnitSystem();

        $TopResults = $this->topResults;
        $CurrentShape = Configuration::Data()->vo2maxShape();

        $this->Input = $this->formInput->prepare(
            $this->submittedInput,
            $TopResults,
            $this->distances,
            $CurrentShape,
            (int) BasicEndurance::getConst(),
            $this->UnitSystem
        );

        $this->InfoLines['vo2max'] = __('Your current effective VO2max:') . ' ' . $CurrentShape . '. ';
        $this->InfoLines['vo2max'] .= __('Your current marathon shape:') . ' ' . BasicEndurance::getConst() . ' %.';

        if (count($TopResults) >= 2) {
            $ResultLine = sprintf(
                __('%s in %s <small>(%s)</small> and %s in %s <small>(%s)</small>'),
                Distance::format($TopResults[0]['distance']),
                Duration::format($TopResults[0]['s']),
                new LocalTime($TopResults[0]['time'])->format('d.m.Y'),
                Distance::format($TopResults[1]['distance']),
                Duration::format($TopResults[1]['s']),
                new LocalTime($TopResults[1]['time'])->format('d.m.Y')
            );
        } elseif (count($TopResults) === 1) {
            $ResultLine = sprintf(
                __('%s in %s <small>(%s)</small>'),
                Distance::format($TopResults[0]['distance']),
                Duration::format($TopResults[0]['s']),
                new LocalTime($TopResults[0]['time'])->format('d.m.Y')
            );
        } else {
            $ResultLine = __('none');
        }

        $this->InfoLines['robert-bock'] = __('Your two best results:') . ' ' . $ResultLine;

        $this->Input['best-result-km'] = Distance::format($this->Input['best-result-km'], false, self::DISTANCE_PRECISION);
        $this->Input['second-best-result-km'] = Distance::format($this->Input['second-best-result-km'], false, self::DISTANCE_PRECISION);
    }

    protected function readPostData(): void
    {
        $this->Input['model'] = $this->modelFactory->normalizeModel((string) ($this->Input['model'] ?? ''));
        $this->PrognosisObject = $this->modelFactory->create($this->Input);

        $this->Distances = $this->distanceValuesToKm(Helper::arrayTrim(explode(',', (string) ($this->Input['distances'] ?? ''))));
    }

    /**
     * @param string[]|array<int, string[]> $values
     */
    protected function distanceValuesToKm(array $values): array
    {
        if ($this->UnitSystem->isImperial()) {
            return array_map(fn (string|array $val): float => round((float) $val / DistanceUnitSystem::MILE_MULTIPLIER, self::DISTANCE_PRECISION), $values);
        }

        return $values;
    }

    protected function distanceValuesToMiles(array $values): array
    {
        if ($this->UnitSystem->isImperial()) {
            return array_map(fn ($val): float => round($val * DistanceUnitSystem::MILE_MULTIPLIER, self::DISTANCE_PRECISION), $values);
        }

        return $values;
    }

    protected function runCalculations(): void
    {
        if (! $this->PrognosisObject->areValuesValid()) {
            return;
        }

        $paceUnit = \Runalyze\Context::Factory()
            ->sport(Configuration::General()->runningSport())
            ->getLegacyPaceUnitEnum();

        $this->Prognoses = $this->predictionRows->calculate(
            $this->account,
            $this->runningSportId,
            $this->Distances,
            $this->PrognosisObject,
            $paceUnit,
            $this->UnitSystem
        );
    }

    protected function fillResultTable(): void
    {
        $this->ResultTable = $this->resultTableRenderer->render(
            $this->Prognoses,
            $this->PrognosisObject->areValuesValid()
        );

        $this->ResultTable .= $this->modelHintRenderer->render(
            $this->Input['model'],
            $this->PrognosisObject
        );
    }

    public function display(): void
    {
        echo $this->twig->render('@Prognose/calculator_form.html.twig', [
            'input' => $this->Input,
            'distance_unit' => $this->UnitSystem->distanceUnit(),
            'info_lines' => $this->InfoLines,
            'result_table' => $this->ResultTable,
        ]);

        echo Ajax::wrapJSasFunction(
            "$('#prognosis-calculator .hide-on-model-change:not(.only-" . $this->Input['model'] . ")').closest('div').hide();"
            . "$('#prognosis-model').on('change',function(){"
            . "$('#prognosis-calculator .hide-on-model-change').closest('div').hide();"
            . "$('#prognosis-calculator .only-'+$(this).val()).closest('div').show();"
            . '});'
        );
    }
}
