<?php

/**
 * This file contains the class Prognose_PrognosisWindow
 * @package Runalyze\Plugins\Panels
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Calculation\BasicEndurance;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Plugins\Prognose\PrognoseCalculatorFormInput;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelFactory;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPersonalBestProvider;
use Runalyze\Plugins\Prognose\PrognoseCalculatorResultTableRenderer;
use Runalyze\Sports\Running\Prognosis\Bock;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Sports\Running\VO2max\EffectiveVO2max;
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
        private readonly PrognoseCalculatorPersonalBestProvider $personalBestProvider,
        private readonly PrognoseCalculatorModelFactory $modelFactory,
        private readonly PrognoseCalculatorFormInput $formInput,
        private readonly PrognoseCalculatorResultTableRenderer $resultTableRenderer,
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

    /**
     * Init calculations
     */
    protected function runCalculations()
    {
        if (! $this->PrognosisObject->areValuesValid()) {
            return;
        }

        $personalBests = $this->personalBestProvider->findForDistances(
            $this->account,
            $this->runningSportId,
            $this->Distances
        );

        foreach ($this->Distances as $km) {
            $Prognosis = $this->PrognosisObject->getSeconds($km);
            $personalBest = $personalBests[sprintf('%.2f', (float) $km)] ?? null;
            $pbSeconds = $personalBest['seconds'] ?? 0.0;

            $VO2maxprognosis = new EffectiveVO2max();
            $VO2maxprognosis->fromPace($km, (int) $Prognosis);

            $VO2maxpb = new EffectiveVO2max();
            $VO2maxpb->fromPace($km, (int) $pbSeconds);

            $PacePrognosis = new Pace($Prognosis, $km, \Runalyze\Context::Factory()->sport(Configuration::General()->runningSport())->getLegacyPaceUnitEnum());
            $PacePB = new Pace($pbSeconds, $km, \Runalyze\Context::Factory()->sport(Configuration::General()->runningSport())->getLegacyPaceUnitEnum());

            $DateWithLink = $personalBest !== null
                ? Ajax::trainingLink($personalBest['activity_id'], new LocalTime($personalBest['timestamp'])->format('d.m.Y'), true)
                : '-';

            $this->Prognoses[] = [
                'distance' => new Distance($km)->stringAuto(),
                'prognosis' => $Prognosis > 0 ? Duration::format($Prognosis) : '-',
                'prognosis-pace' => $PacePrognosis->valueWithAppendix(),
                'prognosis-vo2max' => $Prognosis > 0 ? $VO2maxprognosis->uncorrectedValue() : '-',
                'diff' => $personalBest === null || $Prognosis == 0 ? '-' : ($pbSeconds > $Prognosis ? '+ ' : '- ') . Duration::format(abs(round($pbSeconds - $Prognosis))),
                'diff-class' => $pbSeconds > $Prognosis ? 'plus' : 'minus',
                'pb' => $pbSeconds > 0 ? Duration::format($pbSeconds) : '-',
                'pb-pace' => $pbSeconds > 0 ? $PacePB->valueWithAppendix() : '-',
                'pb-vo2max' => $pbSeconds > 0 ? $VO2maxpb->uncorrectedValue() : '-',
                'pb-date' => $DateWithLink,
            ];
        }
    }

    protected function fillResultTable(): void
    {
        $this->ResultTable = $this->resultTableRenderer->render(
            $this->Prognoses,
            $this->PrognosisObject->areValuesValid()
        );

        if ($this->Input['model'] === 'robert-bock' && $this->PrognosisObject instanceof Bock) {
            $this->addHintsForRobertBock($this->PrognosisObject);
        } elseif ($this->Input['model'] === 'vo2max' && $this->PrognosisObject instanceof VO2max) {
            $this->addHintsForEffectiveVO2max();
        }
    }

    protected function addHintsForRobertBock(Bock $prognosis)
    {
        $this->ResultTable .= HTML::info(sprintf(__('The results give the constants K = %f and e = %f.'), $prognosis->getK(), $prognosis->getE())) . '<br>';

        if (! $this->PrognosisObject->areValuesValid()) {
            $this->ResultTable .= HTML::warning(sprintf(
                __('K must be between %u and %u, e between %f and %f.'),
                Bock::K_LOWER_BOUND,
                Bock::K_UPPER_BOUND,
                Bock::E_LOWER_BOUND,
                Bock::E_UPPER_BOUND
            ));
        }
    }

    protected function addHintsForEffectiveVO2max()
    {
        if (! $this->PrognosisObject->areValuesValid()) {
            $this->ResultTable .= HTML::warning(sprintf(
                __('Effective VO2max must be between %u and %u.'),
                VO2max::REASONABLE_VO2MAX_MINIMUM,
                VO2max::REASONABLE_VO2MAX_MAXIMUM
            ));
        }
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
            "$('#prognosis-calculator .hide-on-model-change:not(.only-" . $this->Input['model'] . ")').closest('div').hide();" .
            "$('#prognosis-model').on('change',function(){" .
            "$('#prognosis-calculator .hide-on-model-change').closest('div').hide();" .
            "$('#prognosis-calculator .only-'+$(this).val()).closest('div').show();" .
            "});"
        );
    }
}
