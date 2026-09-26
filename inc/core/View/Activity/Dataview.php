<?php

namespace Runalyze\View\Activity;

use Ajax;
use HTML;
use Runalyze\Activity\AnaerobicTrainingEffect;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Elevation;
use Runalyze\Activity\Energy;
use Runalyze\Activity\FitTrainingEffectBenefit;
use Runalyze\Activity\GroundcontactBalance;
use Runalyze\Activity\HeartRate;
use Runalyze\Activity\Pace;
use Runalyze\Activity\PerformanceCondition;
use Runalyze\Activity\StrideLength;
use Runalyze\Activity\Temperature;
use Runalyze\Activity\TrainingEffect;
use Runalyze\Activity\VerticalRatio;
use Runalyze\Calculation\Activity\GradeAdjustedPaceCalculator;
use Runalyze\Calculation\JD\LegacyEffectiveVO2max;
use Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector;
use Runalyze\Configuration;
use Runalyze\Context as GeneralContext;
use Runalyze\Data\Cadence;
use Runalyze\Data\Weather\HeatIndex;
use Runalyze\Data\Weather\WindChillFactor;
use Runalyze\Model\Activity;
use Runalyze\Model\Factory;
use Runalyze\Util\LocalTime;
use Runalyze\Util\Time;
use Runalyze\View\Icon\EffectiveVO2maxIcon;
use Runalyze\View\RpeColor;
use Runalyze\View\Stresscolor;
use SearchLink;
use SessionAccountHandler;
use SportFactory;

class Dataview
{
    public ?Pace $GAPace = null;

    protected ?Duration $Duration = null;

    protected ?Energy $Energy = null;

    protected ?HeartRate $HRmax = null;

    protected ?HeartRate $HRavg = null;

    protected ?HeartRate $HRavgActive = null;

    protected ?Pace $Pace = null;

    protected ?LegacyEffectiveVO2max $VO2max = null;

    protected ?Cadence\AbstractCadence $Cadence = null;

    protected ?StrideLength $StrideLength = null;

    protected ?Elevation $Elevation = null;

    protected ?WindChillFactor $WindChillFactor = null;

    protected ?HeatIndex $HeatIndex = null;

    public function __construct(
        protected Activity\Entity $Activity
    ) {
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function object(&$value, \Closure $constructor)
    {
        $value ??= $constructor($this->Activity);

        return $value;
    }

    public function titleByTypeOrSport(): string
    {
        $Factory = new Factory(SessionAccountHandler::getId());

        if ($this->Activity->typeid() != 0) {
            return $Factory->type($this->Activity->typeid())
                ->name();
        }

        return $Factory->sport($this->Activity->sportid())
            ->name();
    }

    public function titleWithComment(): string
    {
        if ($this->Activity->title() !== '') {
            return $this->titleByTypeOrSport() . ': ' . $this->Activity->title();
        }

        return $this->titleByTypeOrSport();
    }

    /**
     * @param string $format [optional]
     */
    public function date(string $format = 'd.m.Y'): string
    {
        return (new LocalTime($this->Activity->timestamp()))->format($format);
    }

    public function daytime(): string
    {
        $time = (new LocalTime($this->Activity->timestamp()))->format('H:i');

        if ($time != '00:00') {
            return $time;
        }

        return '';
    }

    public function dateAndDaytime(): string
    {
        return $this->date() . ' ' . $this->daytime();
    }

    public function weekday(): string
    {
        return Time::weekday((new LocalTime($this->Activity->timestamp()))->format('w'));
    }

    public function duration(): Duration
    {
        return $this->object($this->Duration, fn (Activity\Entity $Activity): Duration => new Duration($Activity->duration()));
    }

    public function elapsedTime(): string
    {
        if ($this->Activity->elapsedTime() < $this->Activity->duration()) {
            return '-:--:--';
        }

        return Duration::format($this->Activity->elapsedTime());
    }

    public function distance(?int $decimals = null): string
    {
        $decimals ??= Configuration::ActivityView()->decimals();

        if ($this->Activity->distance() > 0) {
            if ($this->Activity->isTrack()) {
                return (new Distance($this->Activity->distance()))->stringMeter();
            }

            return Distance::format($this->Activity->distance(), true, $decimals);
        }

        return '';
    }

    public function distanceWithoutEmptyDecimals(): string
    {
        $distance = $this->Activity->distance();
        $decimals = ($distance == floor($distance)) ? 0 : null;

        return $this->distance($decimals);
    }

    public function distanceOrDuration(): string
    {
        if ($this->Activity->distance() > 0) {
            return $this->distance();
        }

        return $this->duration()
            ->string();
    }

    /**
     * Get a string for the speed depending on sportid
     */
    public function pace(): Pace
    {
        return $this->object($this->Pace, fn (Activity\Entity $Activity): Pace => new Pace($Activity->duration(), $Activity->distance(), SportFactory::getSpeedUnitFor($Activity->sportid())));
    }

    /**
     * Get the grade adjusted pace
     */
    public function GApace(\Runalyze\View\Activity\Context $context): Pace
    {
        // calc the pace based on the continous data time&distance&evaluation
        $GaPaceCalculator = new GradeAdjustedPaceCalculator($context);
        $adjTime = $GaPaceCalculator->getAdjustedTime();

        return $this->object($this->GAPace, function (Activity\Entity $Activity) use (&$adjTime): Pace {
            return new Pace($adjTime, $Activity->distance(), SportFactory::getSpeedUnitFor($Activity->sportid()));
        });
    }

    public function cadence(): \Runalyze\Data\Cadence\AbstractCadence
    {
        return $this->object($this->Cadence, function (Activity\Entity $Activity): \Runalyze\Data\Cadence\Running|\Runalyze\Data\Cadence\General {
            if ($Activity->sportid() == Configuration::General()->runningSport()) {
                return new Cadence\Running($Activity->cadence());
            }

            return new Cadence\General($Activity->cadence());
        });
    }

    public function strideLength(): StrideLength
    {
        return $this->object($this->StrideLength, fn (Activity\Entity $Activity): StrideLength => new StrideLength($Activity->strideLength()));
    }

    public function trimp(): string
    {
        $Stress = new Stresscolor($this->Activity->trimp());

        return $Stress->string();
    }

    public function rpe(bool $valueOnly = false): int|null|string
    {
        if ($valueOnly) {
            return $this->Activity->rpe();
        }

        return (new RpeColor($this->Activity->rpe()))->string();
    }

    public function fitVO2maxEstimate(): string
    {
        if ($this->Activity->fitVO2maxEstimate() > 0) {
            return number_format($this->Activity->fitVO2maxEstimate(), 2);
        }

        return '';
    }

    public function fitRecoveryTime(): string
    {
        if ($this->Activity->fitRecoveryTime() > 0) {
            $hours = $this->Activity->fitRecoveryTime() / 60;

            if ($hours > 72) {
                return round($hours / 24) . 'd';
            }

            return round($hours) . 'h';
        }

        return '';
    }

    public function fitHRVscore(): string
    {
        if ($this->Activity->fitHRVscore() > 0) {
            $hue = 128 - 64 * ($this->Activity->fitHRVscore() / 1000);
            $tooltip = Ajax::tooltip('', __('HRV score') . ': ' . round($this->Activity->fitHRVscore()), false, true);

            return '<i class="fa far fa-fw fa-dot-circle" style="color:hsl(' . min(128, max(0, round($hue))) . ',74%,44%);" ' . $tooltip . '></i>';
        }

        return '';
    }

    /**
     * #TSC The Hauptnutzen of the training
     */
    public function fitTrainingEffectBenefit(): string
    {
        if ($this->Activity->fitTrainingEffectBenefit()) {
            return FitTrainingEffectBenefit::descriptionFromNum($this->Activity->fitTrainingEffectBenefit());
        }

        return '';
    }

    public function fitTrainingEffect(): string
    {
        return TrainingEffect::format($this->Activity->fitTrainingEffect());
    }

    /**
     * #TSC: This method returns the anaerobic value to display in the dataview (main)-table from the loaded activity.
     */
    public function fitAnaerobicTrainingEffect(): string
    {
        return AnaerobicTrainingEffect::format($this->Activity->fitAnaerobicTrainingEffect());
    }

    public function fitPerformanceCondition(): string
    {
        $start = $this->Activity->fitPerformanceCondition() !== null ? $this->fitPerformanceConditionStart() : '0';
        $end = $this->Activity->fitPerformanceConditionEnd() !== null ? $this->fitPerformanceConditionEnd() : '0';

        if ($start !== '0' || $end !== '0') {
            return $this->formatTwoPartValue($start, $end);
        }

        return '';
    }

    public function fitPerformanceConditionStart(): string
    {
        return PerformanceCondition::format($this->Activity->fitPerformanceCondition());
    }

    public function fitPerformanceConditionEnd(): string
    {
        return PerformanceCondition::format($this->Activity->fitPerformanceConditionEnd());
    }

    /**
     * @return string power with unit
     */
    public function power(): string
    {
        if ($this->Activity->power() > 0) {
            return round($this->Activity->power()) . '&nbsp;W';
        }

        return '';
    }

    /**
     * @return string ground contact time with unit
     */
    public function groundcontact(): string
    {
        if ($this->Activity->groundcontact() > 0) {
            return round($this->Activity->groundcontact()) . '&nbsp;ms';
        }

        return '';
    }

    /**
     * @return string ground contact balance with unit
     */
    public function groundcontactBalance(): string
    {
        if ($this->Activity->groundContactBalance() > 0) {
            return GroundcontactBalance::format($this->Activity->groundContactBalance());
        }

        return '';
    }

    /**
     * @return string vertical oscillation with unit
     */
    public function verticalOscillation(): string
    {
        if ($this->Activity->verticalOscillation() > 0) {
            return number_format($this->Activity->verticalOscillation() / 10, 1) . '&nbsp;cm';
        }

        return '';
    }

    public function verticalRatio(): string
    {
        if ($this->Activity->verticalRatio() > 0) {
            return VerticalRatio::format($this->Activity->verticalRatio());
        }

        return '';
    }

    public function flightTime(): string
    {
        $flightTime = $this->Activity->flightTime();

        if ($flightTime !== null) {
            return round($flightTime) . '&nbsp;ms';
        }

        return '';
    }

    public function flightRatio(): string
    {
        $flightRatio = $this->Activity->flightRatio();

        if ($flightRatio !== null) {
            return number_format(100 * $flightRatio, 1, '.', '') . '&nbsp;%';
        }

        return '';
    }

    public function impactGs(): string
    {
        return $this->formatRunScribeValues($this->Activity->impactGsLeft(), $this->Activity->impactGsRight());
    }

    public function brakingGs(): string
    {
        return $this->formatRunScribeValues($this->Activity->brakingGsLeft(), $this->Activity->brakingGsRight());
    }

    public function footstrikeType(): string
    {
        return $this->formatRunScribeValues($this->Activity->footstrikeTypeLeft(), $this->Activity->footstrikeTypeRight(), 0);
    }

    public function pronationExcursion(): string
    {
        return $this->formatRunScribeValues($this->Activity->pronationExcursionLeft(), $this->Activity->pronationExcursionRight());
    }

    /**
     * @param null|float|int $left
     * @param null|float|int $right
     * @param int $precision
     */
    protected function formatRunScribeValues($left, $right, $precision = 1): string
    {
        return $this->formatTwoPartValue(
            $left !== null ? number_format($left, $precision) : '-',
            $right !== null ? number_format($right, $precision) : '-'
        );
    }

    public function energy(): Energy
    {
        return $this->object($this->Energy, fn (Activity\Entity $Activity): Energy => new Energy($Activity->energy()));
    }

    public function hrMax(): HeartRate
    {
        return $this->object($this->HRmax, fn (Activity\Entity $Activity): HeartRate => new HeartRate($Activity->hrMax(), GeneralContext::Athlete()));
    }

    public function hrAvg(): HeartRate
    {
        return $this->object($this->HRavg, fn (Activity\Entity $Activity): HeartRate => new HeartRate($Activity->hrAvg(), GeneralContext::Athlete()));
    }

    public function hrAvgActive(): HeartRate
    {
        return $this->object($this->HRavgActive, fn (Activity\Entity $Activity): HeartRate => new HeartRate($Activity->hrAvgActive(), GeneralContext::Athlete()));
    }

    public function elevation(): Elevation
    {
        return $this->object($this->Elevation, fn (Activity\Entity $Activity): Elevation => new Elevation($Activity->elevation()));
    }

    /**
     * @return string gradient in percent with percent sign
     */
    public function gradientInPercent(): string
    {
        if ($this->Activity->distance() == 0) {
            return '-';
        }

        return round($this->Activity->elevation() / $this->Activity->distance() / 10, 2) . '&nbsp;&#37;';
    }

    public function windChillFactor(): WindChillFactor
    {
        return $this->object($this->WindChillFactor, fn (Activity\Entity $Activity): \Runalyze\Data\Weather\WindChillFactor => new WindChillFactor(
            $Activity->weather()
                ->windSpeed(),
            new Temperature($Activity->weather()->temperature()->value()),
            new Pace($Activity->duration(), $Activity->distance())
        ));
    }

    public function heatIndex(): HeatIndex
    {
        return $this->object($this->HeatIndex, fn (Activity\Entity $Activity): \Runalyze\Data\Weather\HeatIndex => new HeatIndex(
            new Temperature($Activity->weather()->temperature()->value()),
            $Activity->weather()
                ->humidity()
        ));
    }

    public function partner(): string
    {
        return HTML::encodeTags($this->Activity->partner()->asString());
    }

    public function partnerAsLinks(): string
    {
        if (\Request::isOnSharedPage()) {
            return $this->partner();
        }

        $links = [];

        foreach ($this->Activity->partner()->asArray() as $partner) {
            $links[] = SearchLink::to('partner', $partner, $partner, 'like');
        }

        return implode(', ', $links);
    }

    public function notes(): string
    {
        return nl2br(HTML::encodeTags($this->Activity->notes()));
    }

    public function vo2max(): \Runalyze\Calculation\JD\LegacyEffectiveVO2max
    {
        $self = $this;

        return $this->object($this->VO2max, fn (Activity\Entity $Activity): \Runalyze\Calculation\JD\LegacyEffectiveVO2max => new LegacyEffectiveVO2max($self->usedVO2maxValue(), new LegacyEffectiveVO2maxCorrector()));
    }

    /**
     * Value of used VO2max (uncorrected)
     */
    public function usedVO2maxValue(): ?float
    {
        if (Configuration::VO2max()->useElevationCorrection()) {
            if ($this->Activity->vo2maxWithElevation() > 0) {
                return $this->Activity->vo2maxWithElevation();
            }
        }

        return $this->Activity->vo2maxByHeartRate();
    }

    public function effectiveVO2maxIcon(): string
    {
        $value = $this->usedVO2maxValue() * Configuration::Data()->vo2maxCorrectionFactor();

        if ($value > 0) {
            $Icon = new EffectiveVO2maxIcon($value);

            if (! $this->Activity->usesVO2max()) {
                $Icon->setTransparent();
            }

            return $Icon->code();
        }

        return '';
    }

    public function climbScore(): string
    {
        if ($this->Activity->climbScore() !== null) {
            return number_format($this->Activity->climbScore(), 1);
        }

        return '';
    }

    public function percentageHilly(): string
    {
        if ($this->Activity->percentageHilly() !== null) {
            return (100 * $this->Activity->percentageHilly()) . '&nbsp;&#37;';
        }

        return '';
    }

    protected function formatTwoPartValue(string $leftString, string $rightString, string $empty = '-'): string
    {
        if ($empty === $leftString && $empty === $rightString) {
            return '';
        }

        return $leftString . '/' . $rightString;
    }
}
