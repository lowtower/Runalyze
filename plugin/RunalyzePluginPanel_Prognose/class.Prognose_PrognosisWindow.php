<?php

/**
 * This file contains the class Prognose_PrognosisWindow
 * @package Runalyze\Plugins\Panels
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Model\Prognosis\PrognosisModel;
use Runalyze\Calculation\BasicEndurance;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPersonalBestProvider;
use Runalyze\Sports\Running\Prognosis\Bock;
use Runalyze\Sports\Running\Prognosis\PrognosisInterface;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Sports\Running\VO2max\EffectiveVO2max;
use Runalyze\Util\LocalTime;

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

    protected Formular $Formular;

    protected FormularFieldset $FieldsetInput;

    protected FormularFieldset $FieldsetResult;

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
     * @param array<int, array<string, int|float|null>> $topResults rows as returned by TrainingRepository::findTopResults()
     * @param array<int, mixed> $distances
     */
    public function __construct(
        private readonly array $topResults,
        private readonly array $distances,
        private readonly Account $account,
        private readonly int $runningSportId,
        private readonly PrognoseCalculatorPersonalBestProvider $personalBestProvider
    ) {
        $this->setDefaultValues();
        $this->readPostData();
        $this->runCalculations();
        $this->fillResultTable();
        $this->initFieldsets();
        $this->initFormular();
    }

    protected function setDefaultValues()
    {
        $this->UnitSystem = Configuration::General()->distanceUnitSystem();

        $TopResults = $this->topResults;
        $CurrentShape = Configuration::Data()->vo2maxShape();

        if (empty($_POST)) {
            $_POST['model'] = 'vo2max';
            $_POST['distances'] = implode(', ', $this->distanceValuesToMiles($this->distances));

            $_POST['vo2max'] = $CurrentShape;
            $_POST['endurance'] = true;
            $_POST['endurance-value'] = BasicEndurance::getConst();

            $hasFirstTopResult = count($TopResults) >= 1;
            $hasSecondTopResult = count($TopResults) >= 2;
            $_POST['best-result-km'] = $hasFirstTopResult ? $TopResults[0]['distance'] : '5.0';
            $_POST['best-result-time'] = $hasFirstTopResult ? Duration::format($TopResults[0]['s']) : '0:26:00';
            $_POST['second-best-result-km'] = $hasSecondTopResult ? $TopResults[1]['distance'] : '10.0';
            $_POST['second-best-result-time'] = $hasSecondTopResult ? Duration::format($TopResults[1]['s']) : '1:00:00';
        } else {
            [$_POST['best-result-km'], $_POST['second-best-result-km']] = $this->distanceValuesToKm([
                str_replace(',', '.', $_POST['best-result-km']),
                str_replace(',', '.', $_POST['second-best-result-km']),
            ]);
        }

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

        $_POST['best-result-km'] = Distance::format($_POST['best-result-km'], false, self::DISTANCE_PRECISION);
        $_POST['second-best-result-km'] = Distance::format($_POST['second-best-result-km'], false, self::DISTANCE_PRECISION);
    }

    protected function readPostData(): void
    {
        $_POST['model'] = match ($_POST['model'] ?? null) {
            'robert-bock', 'herbert-steffny', 'david-cameron' => $_POST['model'],
            default => 'vo2max',
        };

        $model = match ($_POST['model']) {
            'robert-bock' => 'cpp',
            'herbert-steffny' => 'steffny',
            'david-cameron' => 'cameron',
            default => 'vo2max',
        };

        $results = [];

        if ($model !== 'vo2max') {
            $results[] = [
                'distance' => (float) Helper::CommaToPoint($_POST['best-result-km']),
                's' => new Duration($_POST['best-result-time'])->seconds(),
            ];

            if ($model === 'cpp') {
                $results[] = [
                    'distance' => (float) Helper::CommaToPoint($_POST['second-best-result-km']),
                    's' => new Duration($_POST['second-best-result-time'])->seconds(),
                ];
            }
        }

        $this->PrognosisObject = new PrognosisModel(
            $model,
            $results,
            (float) Helper::CommaToPoint($_POST['vo2max'] ?? 0),
            (int) ($_POST['endurance-value'] ?? 0),
            isset($_POST['endurance'])
        )->getPrognosisObject();

        $this->Distances = $this->distanceValuesToKm(Helper::arrayTrim(explode(',', $_POST['distances'])));
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

    protected function fillResultTable()
    {
        $this->startResultTable();

        if ($this->PrognosisObject->areValuesValid()) {
            $this->fillResultTableWithResults();
        } else {
            $this->fillResultTableWithInvalidMessage();
        }

        $this->finishResultTable();
    }

    protected function startResultTable()
    {
        $this->ResultTable = '<table class="fullwidth zebra-style"><thead><tr>
                    <th>' . __('Distance') . '</th>
                    <th>' . __('Prognosis') . '</th>
                    <th class="small">' . __('Pace') . '</th>
                    <th class="small">VO2max</th>
                    <th>' . __('Difference') . '</th>
                    <th>' . __('Personal best') . '</th>
                    <th class="small">' . __('Pace') . '</th>
                    <th class="small">VO2max</th>
                    <th class="small">' . __('Date') . '</th>
                </tr></thead><tbody>';
    }

    protected function fillResultTableWithResults()
    {
        foreach ($this->Prognoses as $Prognosis) {
            $this->ResultTable .= '
                <tr class="r">
                    <td class="c">' . $Prognosis['distance'] . '</td>
                    <td class="b">' . $Prognosis['prognosis'] . '</td>
                    <td class="small">' . $Prognosis['prognosis-pace'] . '</td>
                    <td class="small">' . $Prognosis['prognosis-vo2max'] . '</td>
                    <td class="small ' . $Prognosis['diff-class'] . '">' . $Prognosis['diff'] . '</td>
                    <td class="b">' . $Prognosis['pb'] . '</td>
                    <td class="small">' . $Prognosis['pb-pace'] . '</td>
                    <td class="small">' . $Prognosis['pb-vo2max'] . '</td>
                    <td class="small">' . $Prognosis['pb-date'] . '</td>
                </tr>';
        }
    }

    /**
     * Set invalid message to table
     */
    protected function fillResultTableWithInvalidMessage()
    {
        $this->ResultTable .= '<tr class="c"><td colspan="9"><em>' . __('Your input is invalid') . '</em></td></tr>';
    }

    protected function finishResultTable()
    {
        $this->ResultTable .= '</tbody></table>';

        if ($_POST['model'] == 'robert-bock' && $this->PrognosisObject instanceof Bock) {
            $this->addHintsForRobertBock($this->PrognosisObject);
        } elseif ($_POST['model'] == 'vo2max' && $this->PrognosisObject instanceof VO2max) {
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

    /**
     * Init fields
     */
    protected function initFieldsets()
    {
        $this->initFieldsetForInputData();
        $this->initFieldsetForResults();
    }

    protected function initFieldsetForInputData()
    {
        $this->FieldsetInput = new FormularFieldset(__('Input'));

        foreach ($this->InfoLines as $InfoMessage) {
            $this->FieldsetInput->addInfo($InfoMessage);
        }

        $FieldModel = new FormularSelectBox('model', __('Model'));
        $FieldModel->addOption('vo2max', 'Effective VO2max');
        $FieldModel->addOption('robert-bock', 'Robert Bock (CPP)');
        $FieldModel->addOption('herbert-steffny', 'Herbert Steffny');
        $FieldModel->addOption('david-cameron', 'David Cameron');
        $FieldModel->addAttribute('onchange', '$(\'#prognosis-calculator .only-\'+$(this).val()).closest(\'div\').show();$(\'#prognosis-calculator .hide-on-model-change:not(.only-\'+$(this).val()+\')\').closest(\'div\').hide();');
        $FieldModel->setLayout(FormularFieldset::$LAYOUT_FIELD_W50_AS_W100);

        $FieldDistances = new FormularInput('distances', __('Distances'));
        $FieldDistances->setLayout(FormularFieldset::$LAYOUT_FIELD_W50_AS_W100);
        $FieldDistances->setSize(FormularInput::$SIZE_FULL_INLINE);
        $FieldDistances->setUnit($this->UnitSystem->distanceUnit());

        $this->FieldsetInput->addField($FieldModel);
        $this->FieldsetInput->addField($FieldDistances);

        $this->addFieldsForEffectiveVO2max();
        $this->addFieldsForBockAndSteffny();
    }

    protected function addFieldsForEffectiveVO2max()
    {
        $FieldVO2max = new FormularInput('vo2max', __('Effective VO2max'));
        $FieldVO2max->setLayout(FormularFieldset::$LAYOUT_FIELD_W50_AS_W100);
        $FieldVO2max->addCSSclass('hide-on-model-change');
        $FieldVO2max->addCSSclass('only-vo2max');

        $FieldEndurance = new FormularCheckbox('endurance', __('Use Marathon Shape'));
        $FieldEndurance->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $FieldEndurance->addCSSclass('hide-on-model-change');
        $FieldEndurance->addCSSclass('only-vo2max');

        $FieldEnduranceValue = new FormularInput('endurance-value', __('Marathon Shape'));
        $FieldEnduranceValue->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $FieldEnduranceValue->addCSSclass('hide-on-model-change');
        $FieldEnduranceValue->addCSSclass('only-vo2max');
        $FieldEnduranceValue->setUnit(FormularUnit::$PERCENT);

        $this->FieldsetInput->addField($FieldVO2max);
        $this->FieldsetInput->addField($FieldEnduranceValue);
        $this->FieldsetInput->addField($FieldEndurance);
    }

    /**
     * Add fields for robert bock and herbert steffny
     */
    protected function addFieldsForBockAndSteffny()
    {
        $BestResult = new FormularInput('best-result-km', __('Best result'));
        $BestResult->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $BestResult->addCSSclass('hide-on-model-change');
        $BestResult->addCSSclass('only-robert-bock');
        $BestResult->addCSSclass('only-herbert-steffny');
        $BestResult->addCSSclass('only-david-cameron');
        $BestResult->setUnit($this->UnitSystem->distanceUnit());

        $BestResultTime = new FormularInput('best-result-time', __('in'));
        $BestResultTime->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $BestResultTime->addCSSclass('hide-on-model-change');
        $BestResultTime->addCSSclass('only-robert-bock');
        $BestResultTime->addCSSclass('only-herbert-steffny');
        $BestResultTime->addCSSclass('only-david-cameron');

        $SecondBestResult = new FormularInput('second-best-result-km', __('Second best result'));
        $SecondBestResult->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $SecondBestResult->addCSSclass('hide-on-model-change');
        $SecondBestResult->addCSSclass('only-robert-bock');
        $SecondBestResult->setUnit($this->UnitSystem->distanceUnit());

        $SecondBestResultTime = new FormularInput('second-best-result-time', __('in'));
        $SecondBestResultTime->setLayout(FormularFieldset::$LAYOUT_FIELD_W50);
        $SecondBestResultTime->addCSSclass('hide-on-model-change');
        $SecondBestResultTime->addCSSclass('only-robert-bock');

        $this->FieldsetInput->addField($BestResult);
        $this->FieldsetInput->addField($BestResultTime);
        $this->FieldsetInput->addField($SecondBestResult);
        $this->FieldsetInput->addField($SecondBestResultTime);
    }

    protected function initFieldsetForResults()
    {
        $this->FieldsetResult = new FormularFieldset(__('Prognosis'));
        $this->FieldsetResult->addBlock($this->ResultTable);
    }

    protected function initFormular()
    {
        $this->Formular = new Formular();
        $this->Formular->setId('prognosis-calculator');
        $this->Formular->addCSSclass('ajax');
        $this->Formular->addCSSclass('no-automatic-reload');
        $this->Formular->addFieldset($this->FieldsetInput);
        $this->Formular->addFieldset($this->FieldsetResult);
        $this->Formular->addSubmitButton(__('Show prognosis'));
    }

    public function display(): void
    {
        echo '<div class="panel-heading">';
        $this->displayHeading();
        echo '</div>';
        echo '<div class="panel-content">';
        $this->displayFormular();
        echo '</div>';
    }

    protected function displayHeading()
    {
        echo HTML::h1(__('Prognosis calculator'));
    }

    protected function displayFormular()
    {
        $this->Formular->display();

        echo Ajax::wrapJSasFunction('$(\'#prognosis-calculator .hide-on-model-change:not(.only-' . $_POST['model'] . ")').closest('div').hide();");
    }
}
