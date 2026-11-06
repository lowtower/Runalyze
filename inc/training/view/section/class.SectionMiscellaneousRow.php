<?php

/**
 * This file contains class::SectionMiscellaneousRow
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity as runActivity;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Activity\Temperature;
use Runalyze\AgeGrade\Lookup;
use Runalyze\AgeGrade\Table\FemaleTable;
use Runalyze\AgeGrade\Table\MaleTable;
use Runalyze\Model\Trackdata;
use Runalyze\Model\Trackdata\Pauses;
use Runalyze\Profile\Athlete\Gender;
use Runalyze\View;
use Runalyze\View\Activity;
use Runalyze\View\Activity\Box;

/**
 * Row: Miscellaneous
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionMiscellaneousRow extends TrainingViewSectionRowTabbedPlot
{
    /**
     * @param bool $showCadence
     */
    public function __construct(
        ?Activity\Context $Context = null,
        protected $showCadence = true
    ) {
        parent::__construct($Context);
    }

    protected function setContent()
    {
        $this->setBoxedValues();
    }

    /**
     * Set content right
     */
    protected function setRightContent()
    {
        $this->addRightContent('notes', __('Additional notes'), $this->notesTemplateData());

        $this->addRightContent('fitdetails', __('FIT details'), $this->fitDetailsTemplateData());

        if ($this->showCadence && $this->Context->trackdata()->has(Trackdata\Entity::CADENCE)) {
            $Plot = new Activity\Plot\Cadence($this->Context);
            $this->addRightContent('cadence', __('Cadence plot'), $Plot);
        }

        if ($this->Context->trackdata()->has(Trackdata\Entity::POWER)) {
            $Plot = new Activity\Plot\Power($this->Context);
            $this->addRightContent('power', __('Power plot'), $Plot);
        }

        if ($this->Context->trackdata()->has(Trackdata\Entity::TEMPERATURE)) {
            $Plot = new Activity\Plot\Temperature($this->Context);
            $this->addRightContent('temperature', __('Temperature plot'), $Plot);
        }

        if ($this->Context->trackdata()->has(Trackdata\Entity::PERFORMANCE_CONDITION)) {
            $Plot = new Activity\Plot\PerformanceCondition($this->Context);
            $this->addRightContent('performancecondition', __('Leistungszustand'), $Plot);
        }

        if ($this->Context->trackdata()->has(Trackdata\Entity::SMO2_0)
            || $this->Context->trackdata()
                ->has(Trackdata\Entity::THB_0)) {
            $Plot = new Activity\Plot\Smo2AndThb($this->Context);
            $this->addRightContent('smo2AndThb', __('SmO2') . ' / ' . __('THb'), $Plot);
        }

        // #TSC pauses
        if ($this->Context->trackdata()->has(Trackdata\Entity::PAUSES)) {
            $Table = new TablePauses($this->Context);
            $this->addRightContent('pauses', __('Pausen'), $Table->templateData());
        }
    }

    protected function setBoxedValues()
    {
        $this->addDateAndTime();
        $this->addRPE();
        $this->addCadenceAndPower();
        $this->addStrokeandSwolf();
        $this->addWeather();
        $this->addTags();
        $this->addEquipment();
        $this->addTrainingPartner();
        $this->addSmo2AndThb();
        $this->addTotalCycles();
    }

    protected function addDateAndTime()
    {
        $Date = new BoxedValue($this->Context->dataview()->date(), '', __('Date'));

        if ($this->Context->dataview()->daytime() !== '') {
            $Daytime = new BoxedValue($this->Context->dataview()->daytime(), '', __('Time of day'));
            $Daytime->defineAsFloatingBlock('w50');
            $Date->defineAsFloatingBlock('w50');

            $this->BoxedValues[] = $Date;
            $this->BoxedValues[] = $Daytime;
        } else {
            $Date->defineAsFloatingBlock('w100');
            $this->BoxedValues[] = $Date;
        }
    }

    protected function addCadenceAndPower()
    {
        if ($this->showCadence && $this->Context->activity()->cadence() > 0) {
            $Cadence = new BoxedValue(Helper::Unknown($this->Context->dataview()->cadence()->value(), '-'), $this->Context->dataview()->cadence()->unitAsString(), $this->Context->dataview()->cadence()->label());
            $Cadence->defineAsFloatingBlock('w50');

            $TotalCadence = new Box\TotalCadence($this->Context);
            $TotalCadence->defineAsFloatingBlock('w50');

            $this->BoxedValues[] = $Cadence;
            $this->BoxedValues[] = $TotalCadence;

            if ($this->Context->activity()->strideLength() > 0) {
                $StrideLength = new Activity\Box\StrideLength($this->Context);
                $StrideLength->defineAsFloatingBlock('w50');

                $this->BoxedValues[] = $StrideLength;
            }
        }

        if ($this->Context->activity()->power() > 0) {
            $icon = $this->Context->activity()
                ->isPowerCalculated() ? '<i rel="tooltip" class="unimportant fa fa-fw fa-bolt atRight" title="' . __('This value has been calculated.') . '"></i>' : '';
            $Power = new BoxedValue((string) $this->Context->activity()->power(), 'W', '&#216; ' . __('Power'), $icon);
            $Power->defineAsFloatingBlock('w50');

            $this->BoxedValues[] = $Power;
        }
    }

    /*
     * Add swolf and total strokes
     */
    protected function addStrokeandSwolf()
    {
        if ($this->Context->hasSwimdata() && ($this->Context->activity()->totalStrokes() > 0 || $this->Context->activity()->swolf() > 0)) {
            if ($this->Context->activity()->totalStrokes() > 0) {
                $Strokes = new BoxedValue((string) $this->Context->activity()->totalStrokes(), '', __('Strokes'));
                $Strokes->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $Strokes;
            }

            if ($this->Context->activity()->swolf() > 0) {
                $Swolf = new BoxedValue((string) $this->Context->activity()->swolf(), '', __('Swolf'));
                $Swolf->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $Swolf;
            }

            if ($this->Context->swimdata()->poollength() > 0) {
                $PoolLength = new Box\PoolLength($this->Context);
                $PoolLength->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $PoolLength;
            }
        }
    }

    protected function addRunningDynamics()
    {
        if ($this->Context->activity()->groundcontact() > 0 || $this->Context->activity()->verticalOscillation() > 0) {
            $Contact = new BoxedValue(Helper::Unknown($this->Context->activity()->groundcontact(), '-'), 'ms', __('Ground contact'));
            $Contact->defineAsFloatingBlock('w50');

            $Oscillation = new BoxedValue(Helper::Unknown(round($this->Context->activity()->verticalOscillation() / 10, 1), '-'), 'cm', __('Vertical oscillation'));
            $Oscillation->defineAsFloatingBlock('w50');

            $this->BoxedValues[] = $Contact;
            $this->BoxedValues[] = $Oscillation;
        }
    }

    protected function addWeather()
    {
        $WeatherObject = $this->Context->activity()
            ->weather();

        if (! $WeatherObject->isEmpty()) {
            $WeatherIcon = $WeatherObject->condition()
                ->icon();

            if ($this->Context->activity()->isNight()) {
                $WeatherIcon->setAsNight();
            }

            $Temperature = new Temperature($WeatherObject->temperature()->value());
            $Weather = new BoxedValue($WeatherObject->condition()->string(), '', __('Weather condition'), $WeatherIcon->code());
            $Weather->defineAsFloatingBlock('w50');

            $Temp = new BoxedValue($Temperature->string(false, false), $Temperature->unit(), __('Temperature'));
            $Temp->defineAsFloatingBlock('w50');

            $this->BoxedValues[] = $Weather;
            $this->BoxedValues[] = $Temp;

            if (! $WeatherObject->windSpeed()->isUnknown()) {
                $WindSpeed = new Box\WeatherWindSpeed($this->Context);
                $WindSpeed->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $WindSpeed;
            }

            if (! $WeatherObject->windDegree()->isUnknown()) {
                $WindDegree = new Box\WeatherWindDegree($this->Context);
                $WindDegree->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $WindDegree;
            }

            if (! $WeatherObject->humidity()->isUnknown()) {
                $Humidity = new Box\WeatherHumidity($this->Context);
                $Humidity->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $Humidity;
            }

            if (! $WeatherObject->pressure()->isUnknown()) {
                $Pressure = new Box\WeatherPressure($this->Context);
                $Pressure->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $Pressure;
            }

            if (! $WeatherObject->windSpeed()->isUnknown() && ! $WeatherObject->temperature()->isUnknown() && $this->Context->activity()->distance() > 0 && $this->Context->activity()->duration() > 0) {
                $WindChill = new Box\WeatherWindChillFactor($this->Context);
                $WindChill->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $WindChill;
            }

            if (! $WeatherObject->temperature()->isUnknown() && ! $WeatherObject->humidity()->isUnknown()) {
                $heatIndexObject = $this->Context->dataview()
                    ->heatIndex();
                $HeatIndex = new BoxedValue(Helper::Unknown(round($heatIndexObject->value(), 1), '-'), $heatIndexObject->unit(), $heatIndexObject->label(), $heatIndexObject->getIcon());
                $HeatIndex->defineAsFloatingBlock('w50');
                $this->BoxedValues[] = $HeatIndex;
            }
        }
    }

    protected function addEquipment()
    {
        $Types = [];
        $Factory = new \Runalyze\Model\Factory(SessionAccountHandler::getId());
        $Equipment = $Factory->equipmentForActivity($this->Context->activity()->id());

        foreach ($Equipment as $Object) {
            $Link = Request::isOnSharedPage() ? $Object->name() : SearchLink::to('equipmentid', $Object->id(), $Object->name());

            if (isset($Types[$Object->typeid()])) {
                $Types[$Object->typeid()][] = $Link;
            } else {
                $Types[$Object->typeid()] = [$Link];
            }
        }

        foreach ($Types as $typeid => $links) {
            $Type = $Factory->equipmentType($typeid);

            $Value = new BoxedValue(implode(', ', $links), '', $Type->name());
            $Value->defineAsFloatingBlock('w100 flexible-height');

            $this->BoxedValues[] = $Value;
        }
    }

    protected function addTags()
    {
        $Links = [];
        $Factory = new \Runalyze\Model\Factory(SessionAccountHandler::getId());
        $SelectedTags = $Factory->tagForActivity($this->Context->activity()->id());

        foreach ($SelectedTags as $Object) {
            $Links[] = Request::isOnSharedPage() ? '#' . $Object->tag() : SearchLink::to('tagid', $Object->id(), '#' . $Object->tag());
        }

        if ($Links !== []) {
            $Value = new BoxedValue(implode(', ', $Links), '', __('Tags'));
            $Value->defineAsFloatingBlock('w100 flexible-height');

            $this->BoxedValues[] = $Value;
        }
    }

    protected function addTrainingPartner()
    {
        if (! $this->Context->activity()->partner()->isEmpty()) {
            $TrainingPartner = new BoxedValue($this->Context->dataview()->partnerAsLinks(), '', __('Training partner'));
            $TrainingPartner->defineAsFloatingBlock('w100 flexible-height');

            $this->BoxedValues[] = $TrainingPartner;
        }
    }

    protected function addSmo2AndThb()
    {
        if ($this->Context->trackdata()->has(Trackdata\Entity::SMO2_0)) {
            $Smo2_0 = new Box\Smo2($this->Context);
            $Smo2_0->defineAsFloatingBlock('w50');
            $this->BoxedValues[] = $Smo2_0;
        }

        if ($this->Context->trackdata()->has(Trackdata\Entity::SMO2_1)) {
            $Smo2_1 = new Box\Smo2($this->Context, 1);
            $Smo2_1->defineAsFloatingBlock('w50');
            $this->BoxedValues[] = $Smo2_1;
        }

        if ($this->Context->trackdata()->has(Trackdata\Entity::THB_0)) {
            $Thb_0 = new Box\Thb($this->Context);
            $Thb_0->defineAsFloatingBlock('w50');
            $this->BoxedValues[] = $Thb_0;
        }

        if ($this->Context->trackdata()->has(Trackdata\Entity::THB_1)) {
            $Thb_1 = new Box\Thb($this->Context, 1);
            $Thb_1->defineAsFloatingBlock('w50');
            $this->BoxedValues[] = $Thb_1;
        }
    }

    /**
     * Notes tab, template data for activity/training_view/notes.html.twig
     *
     * @return array<string, mixed>
     */
    protected function notesTemplateData(): array
    {
        $creationAndModification = $this->creationAndModificationTemplateData();

        return [
            'template' => 'activity/training_view/notes.html.twig',
            'race_result' => $this->raceResultTemplateData(),
            'notes_label' => __('Notes') . ':',
            'notes' => $this->Context->activity()->notes() != '' ? $this->Context->dataview()->notes() : null,
            'weather_source' => $this->Context->activity()->weather()->sourceIsKnown()
                ? sprintf(__('Source of weather data: %s'), $this->Context->activity()->weather()->sourceAsString())
                : null,
            'created' => $creationAndModification['created'],
            'modified' => $creationAndModification['modified'],
        ];
    }

    /**
     * Race result block for the notes tab
     *
     * @return array<string, mixed>|null
     */
    protected function raceResultTemplateData(): ?array
    {
        if (! $this->Context->hasRaceResult()) {
            return null;
        }

        $raceResult = $this->Context->raceResult();
        $athlete = \Runalyze\Context::Athlete();
        $ageGradeItem = null;

        if ($this->Context->sport()->getInternalProfileEnum() === \Runalyze\Profile\Sport\SportProfile::RUNNING && $athlete->knowsGender() && $athlete->knowsAge()) {
            $table = $athlete->gender() === Gender::FEMALE ? new FemaleTable() : new MaleTable();
            $lookup = new Lookup($table, $athlete->age());

            if ($this->Context->raceResult()->officialDistance() >= $lookup->getMinimalDistance()) {
                $ageGrade = $lookup->getAgeGrade($raceResult->officialDistance(), (int) $raceResult->officialTime(), date('Y') - date('Y', $this->Context->activity()->timestamp()));
                $ageGradeIcon = new View\Icon('fa-info-circle');
                $ageGradeIcon->setTooltip(
                    __('Age standard') . ': ' . Duration::format($ageGrade->getAgeStandard()) . ', '
                    . __('Open standard') . ': ' . Duration::format($ageGrade->getOpenStandard()) . '<br>'
                    . '<small><em>' . sprintf(__('via tables by %s'), 'Alan Jones / WMA / USATF') . '</em></small>'
                );

                $ageGradeItem = [
                    'label' => __('Age grade'),
                    'content' => number_format(100 * $ageGrade->getPerformance(), 2) . ' &#37; ' . $ageGradeIcon->code(),
                ];
            }
        }

        $RaceResultView = new View\RaceResult\Dataview($raceResult);

        $items = [];

        if ($raceResult->officialDistance()) {
            $items[] = [
                'label' => __('Official distance'),
                'content' => $RaceResultView->officialDistance(),
            ];
        }

        if ($raceResult->officialTime()) {
            $items[] = [
                'label' => __('Official time'),
                'content' => $RaceResultView->officialTime()->string(Duration::FORMAT_COMPETITION),
            ];
        }

        if ($ageGradeItem !== null) {
            $items[] = $ageGradeItem;
        }

        if ($raceResult->placeTotal()) {
            $items[] = [
                'label' => __('Place overall'),
                'content' => $RaceResultView->placementTotalWithParticipants(),
            ];
        }

        if ($raceResult->placeAgeclass()) {
            $items[] = [
                'label' => __('Place age group'),
                'content' => $RaceResultView->placementAgeClassWithParticipants(),
            ];
        }

        if ($raceResult->placeGender()) {
            $items[] = [
                'label' => __('Place gender'),
                'content' => $RaceResultView->placementGenderWithParticipants(),
            ];
        }

        return [
            'title' => __('Race Result') . ':',
            'items' => $items,
        ];
    }

    /**
     * Creation and modification times for the notes tab
     *
     * @return array{created: ?string, modified: ?string}
     */
    protected function creationAndModificationTemplateData(): array
    {
        if (Request::isOnSharedPage()) {
            return [
                'created' => null,
                'modified' => null,
            ];
        }

        $created = $this->Context->activity()
            ->get(\Runalyze\Model\Activity\Entity::TIMESTAMP_CREATED);
        $edited = $this->Context->activity()
            ->get(\Runalyze\Model\Activity\Entity::TIMESTAMP_EDITED);

        if ($created == 0 && $edited == 0) {
            return [
                'created' => null,
                'modified' => null,
            ];
        }

        $createdDate = new \DateTime();
        $createdDate->setTimestamp($created);

        $createdText = ($created == 0) ? null : sprintf(
            __('You created this training on <strong>%s</strong> at <strong>%s</strong>.'),
            $createdDate->format('d.m.Y'),
            $createdDate->format('H:i')
        );

        $editedDate = new \DateTime();
        $editedDate->setTimestamp($edited);

        $editedText = ($edited == 0) ? null : sprintf(
            __('Last modification on <strong>%s</strong> at <strong>%s</strong>.'),
            $editedDate->format('d.m.Y'),
            $editedDate->format('H:i')
        );

        return [
            'created' => $createdText,
            'modified' => $editedText,
        ];
    }

    /**
     * FIT details tab, template data for activity/training_view/fitdetails.html.twig
     * #TSC add FIT file data as a new panel/sheet
     *
     * @return array<string, mixed>
     */
    protected function fitDetailsTemplateData(): array
    {
        $activity = $this->Context->activity();

        // #TSC lactate threshold
        if ($activity->fitLactateThresholdHR() == null && $activity->fitLactateThresholdPace() == null) {
            $lt = '-';
        } else {
            $lthr = $activity->fitLactateThresholdHR() != null ? $activity->fitLactateThresholdHR() : '-';
            if ($activity->fitLactateThresholdPace() != null) {
                $ltp = new Pace($activity->fitLactateThresholdPace(), 1.0, $this->Context->sport()->getLegacyPaceUnitEnum());
                // the previous Pace instancing will not work: for 3.22 m/s the result is a pace of 5:22/km; because of the first parameter time is a int!
                // so use this: example for 3.22m/s: 3,22*3,6=11,592km/h -> 1/11,592km/h=0,0862h/km -> 0,0862×60=5,17min/km -> function needs second/float -> 5,17min/km*60=316,56sec/km
                $ltp->fromMinPerKm((string) (1 / ($activity->fitLactateThresholdPace() * 3.6) * 60 * 60));
                $ltp = $ltp->valueWithAppendix();
            } else {
                $ltp = '-';
            }

            $lt = $lthr . ' | ' . $ltp;
        }

        return [
            'template' => 'activity/training_view/fitdetails.html.twig',
            'header' => __('FIT data from the file') . ':',
            'rows' => [
                [
                    'desc' => 'Total ascent (m):',
                    'value' => $activity->fitTotalAscent() != null ? $activity->fitTotalAscent() : '-',
                ],
                [
                    'desc' => 'Total desent (m):',
                    'value' => $activity->fitTotalDescent() != null ? $activity->fitTotalDescent() : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('VO2MAX avg:', 'vo2max'),
                    'value' => $activity->fitVO2maxEstimate() != null ? $activity->fitVO2maxEstimate() : '-',
                ],
                [
                    'desc' => 'Recovery time (h):',
                    'value' => $activity->fitRecoveryTime() != null ? round($activity->fitRecoveryTime() / 60, 1) : '-',
                ],
                [
                    'desc' => 'New lactate treshhold (bpm | pace):',
                    'value' => $lt,
                ],
                [
                    'desc' => $this->getGlossaryLink('HVR Heart-Rate Variability:', 'hrv'),
                    'value' => $activity->fitHRVscore() != null ? $activity->fitHRVscore() : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('Performance condition start:', 'performance-condition'),
                    'value' => $activity->fitPerformanceCondition() != null ? sprintf('%+d', $activity->fitPerformanceCondition() - 100) : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('Performance cond. end:', 'performance-condition'),
                    'value' => $activity->fitPerformanceConditionEnd() != null ? sprintf('%+d', $activity->fitPerformanceConditionEnd() - 100) : '-',
                ],
                [
                    'desc' => 'Max HR (bpm):',
                    'value' => $activity->maxHrUser() != null ? $activity->maxHrUser() : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink(__('Training Effect') . ' benefit:', 'training-effect'),
                    'value' => $activity->fitTrainingEffectBenefit() != null ? runActivity\FitTrainingEffectBenefit::descriptionFromNum($activity->fitTrainingEffectBenefit()) : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('Aerob training effect:', 'training-effect'),
                    'value' => $activity->fitTrainingEffect() != null ? $activity->fitTrainingEffect() : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('Anaerob training effect:', 'training-effect'),
                    'value' => $activity->fitAnaerobicTrainingEffect() != null ? $activity->fitAnaerobicTrainingEffect() : '-',
                ],
                [
                    'desc' => 'Creator:',
                    'value' => $activity->creator() != null ? $activity->creator() : '-',
                ],
                [
                    'desc' => 'Creator details:',
                    'value' => $activity->creatorDetails() != null ? $activity->creatorDetails() : '-',
                ],
                [
                    'desc' => 'Avg / max respiration rate (brpm):',
                    'value' => $activity->avgRespirationRate() != null
                                        ? $activity->avgRespirationRate() . ' / ' . $activity->maxRespirationRate() : '- / -',
                ],
                [
                    'desc' => $this->getGlossaryLink('Anstrengung (1-10):', 'self-evaluation'),
                    'value' => $activity->fitSelfEvaluationPerceivedEffort() != null ? $activity->fitSelfEvaluationPerceivedEffort() : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('Gefühl (1-5):', 'self-evaluation'),
                    'value' => $activity->fitSelfEvaluationFeeling() != null ? runActivity\SelfEvaluationFeeling::descriptionFromNum($activity->fitSelfEvaluationFeeling()) : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('Trainingsbelastung:', 'training_load_peak'),
                    'value' => $activity->fitLoadPeak() != null ? $activity->fitLoadPeak() : '-',
                ],
                [
                    'desc' => $this->getGlossaryLink('Laufzeit:', 'detection_run_walk'),
                    'value' => $this->getFormattedSeconds($activity->fitRunTime()),
                ],
                [
                    'desc' => $this->getGlossaryLink('Gehzeit:', 'detection_run_walk'),
                    'value' => $this->getFormattedSeconds($activity->fitWalkTime()),
                ],
                [
                    'desc' => $this->getGlossaryLink('Zeit Inaktivität:', 'detection_run_walk'),
                    'value' => $this->getFormattedSeconds($activity->fitStandTime()),
                ],
                [
                    'desc' => 'Sweat loss (ml):',
                    'value' => $activity->fitSweatLoss() != null ? $activity->fitSweatLoss() : '-',
                ],
                [
                    'desc' => __('Energy') . ' ' . __('Resting') . ' (kcal):',
                    'value' => $activity->energyInRest() != null ? $activity->energyInRest() : '-',
                ],
            ],
        ];
    }

    protected function getGlossaryLink(string $text, string $glossary): string
    {
        return '<a class="window left" href="glossary/' . $glossary . '"><i class="fa far fa-question-circle"></i></a>&nbsp;' . $text;
    }

    protected function getFormattedSeconds(?int $seconds): string
    {
        if (isset($seconds)) {
            return Duration::format($seconds) . 's';
        }

        return '-';
    }

    /**
     * Add: RPE
     */
    protected function addRPE()
    {
        if ($this->Context->activity()->rpe()) {
            $this->BoxedValues[] = new Box\RPE($this->Context);
        }
    }

    /**
     * Add: Cycles #TSC
     */
    protected function addTotalCycles()
    {
        if ($this->Context->activity()->totalCycles()) {
            $box = new Box\TotalCycles($this->Context);
            $box->defineAsFloatingBlock('w50');
            $this->BoxedValues[] = $box;
        }
    }
}
