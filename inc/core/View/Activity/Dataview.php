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
    public $GAPace;

    /**
     * @var null|\Runalyze\Model\Activity\Entity
     */
    protected $Activity;

    /**
     * @var null|\Runalyze\Activity\Duration
     */
    protected $Duration;

    /**
     * @var null|\Runalyze\Activity\Energy
     */
    protected $Energy;

    /**
     * @var null|\Runalyze\Activity\HeartRate
     */
    protected $HRmax;

    /**
     * @var null|\Runalyze\Activity\HeartRate
     */
    protected $HRavg;

    /**
     * @var null|\Runalyze\Activity\HeartRate
     */
    protected $HRavgActive;

    /**
     * @var null|\Runalyze\Activity\Pace
     */
    protected $Pace;

    /**
     * @var null|\Runalyze\Calculation\JD\LegacyEffectiveVO2max
     */
    protected $VO2max;

    /**
     * @var null|\Runalyze\Data\Cadence\AbstractCadence
     */
    protected $Cadence;

    /**
     * @var null|\Runalyze\Activity\StrideLength
     */
    protected $StrideLength;

    /**
     * @var null|\Runalyze\Activity\Elevation
     */
    protected $Elevation;

    /**
     * @var null|\Runalyze\Data\Weather\WindChillFactor
     */
    protected $WindChillFactor;

    /**
     * @var null|HeatIndex
     */
    protected $HeatIndex;

    public function __construct(Activity\Entity $activity)
    {
        $this->Activity = $activity;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function object(&$value, \Closure $constructor)
    {
        if ($value === null) {
            $value = $constructor($this->Activity);
        }

        return $value;
    }

    /**
     * @return string
     */
    public function titleByTypeOrSport()
    {
        $Factory = new Factory(SessionAccountHandler::getId());

        if ($this->Activity->typeid() != 0) {
            return $Factory->type($this->Activity->typeid())
                ->name();
        }

        return $Factory->sport((int) $this->Activity->sportid())
            ->name();
    }

    /**
     * @return string
     */
    public function titleWithComment()
    {
        if ($this->Activity->title() != '') {
            return $this->titleByTypeOrSport() . ': ' . $this->Activity->title();
        }

        return $this->titleByTypeOrSport();
    }

    /**
     * @param string $format [optional]
     */
    public function date($format = 'd.m.Y'): string
    {
        if (! is_numeric($this->Activity->timestamp())) {
            return '';
        }

        return (new LocalTime($this->Activity->timestamp()))->format($format);
    }

    /**
     * @return string
     */
    public function daytime()
    {
        if (is_numeric($this->Activity->timestamp())) {
            $time = (new LocalTime($this->Activity->timestamp()))->format('H:i');

            if ($time != '00:00') {
                return $time;
            }
        }

        return '';
    }

    public function dateAndDaytime(): string
    {
        return $this->date() . ' ' . $this->daytime();
    }

    public function weekday(): string
    {
        if (! is_numeric($this->Activity->timestamp())) {
            return '';
        }

        return Time::weekday((new LocalTime($this->Activity->timestamp()))->format('w'));
    }

    /**
     * @return \Runalyze\Activity\Duration
     */
    public function duration()
    {
        return $this->object($this->Duration, function (Activity\Entity $Activity): \Runalyze\Activity\Duration {
            return new Duration($Activity->duration());
        });
    }

    public function elapsedTime(): string
    {
        if ($this->Activity->elapsedTime() < $this->Activity->duration()) {
            return '-:--:--';
        }

        return Duration::format($this->Activity->elapsedTime());
    }

    /**
     * @param int|null $decimals
     */
    public function distance($decimals = null): string
    {
        if ($decimals === null) {
            $decimals = Configuration::ActivityView()->decimals();
        }

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
     *
     * @return \Runalyze\Activity\Pace
     */
    public function pace()
    {
        return $this->object($this->Pace, function (Activity\Entity $Activity): \Runalyze\Activity\Pace {
            return new Pace($Activity->duration(), $Activity->distance(), SportFactory::getSpeedUnitFor($Activity->sportid()));
        });
    }

    /**
     * Get the grade adjusted pace
     *
     * @return \Runalyze\Activity\Pace
     */
    public function GApace(\Runalyze\View\Activity\Context $context)
    {
        // calc the pace based on the continous data time&distance&evaluation
        $GaPaceCalculator = new GradeAdjustedPaceCalculator($context);
        $adjTime = $GaPaceCalculator->getAdjustedTime();

        return $this->object($this->GAPace, function (Activity\Entity $Activity) use (&$adjTime): \Runalyze\Activity\Pace {
            return new Pace($adjTime, $Activity->distance(), SportFactory::getSpeedUnitFor($Activity->sportid()));
        });
    }

    /**
     * @return \Runalyze\Data\Cadence\AbstractCadence
     */
    public function cadence()
    {
        return $this->object($this->Cadence, function (Activity\Entity $Activity) {
            if ($Activity->sportid() == Configuration::General()->runningSport()) {
                return new Cadence\Running($Activity->cadence());
            }

            return new Cadence\General($Activity->cadence());
        });
    }

    /**
     * @return \Runalyze\Activity\StrideLength
     */
    public function strideLength()
    {
        return $this->object($this->StrideLength, function (Activity\Entity $Activity): \Runalyze\Activity\StrideLength {
            return new StrideLength($Activity->strideLength());
        });
    }

    public function trimp(): string
    {
        $Stress = new Stresscolor($this->Activity->trimp());

        return $Stress->string();
    }

    /**
     * @param bool $valueOnly
     * @return int|null|string
     */
    public function rpe($valueOnly = false)
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

    /**
     * @return \Runalyze\Activity\Energy
     */
    public function energy()
    {
        return $this->object($this->Energy, function (Activity\Entity $Activity): \Runalyze\Activity\Energy {
            return new Energy($Activity->energy());
        });
    }

    /**
     * @return \Runalyze\Activity\HeartRate
     */
    public function hrMax()
    {
        return $this->object($this->HRmax, function (Activity\Entity $Activity): \Runalyze\Activity\HeartRate {
            return new HeartRate($Activity->hrMax(), GeneralContext::Athlete());
        });
    }

    /**
     * @return \Runalyze\Activity\HeartRate
     */
    public function hrAvg()
    {
        return $this->object($this->HRavg, function (Activity\Entity $Activity): \Runalyze\Activity\HeartRate {
            return new HeartRate($Activity->hrAvg(), GeneralContext::Athlete());
        });
    }

    /**
     * @return \Runalyze\Activity\HeartRate
     */
    public function hrAvgActive()
    {
        return $this->object($this->HRavgActive, function (Activity\Entity $Activity): \Runalyze\Activity\HeartRate {
            return new HeartRate($Activity->hrAvgActive(), GeneralContext::Athlete());
        });
    }

    /**
     * @return \Runalyze\Activity\Elevation
     */
    public function elevation()
    {
        return $this->object($this->Elevation, function (Activity\Entity $Activity): \Runalyze\Activity\Elevation {
            return new Elevation($Activity->elevation());
        });
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

    /**
     * @return WindChillFactor
     */
    public function windChillFactor()
    {
        return $this->object($this->WindChillFactor, function (Activity\Entity $Activity): \Runalyze\Data\Weather\WindChillFactor {
            return new WindChillFactor(
                $Activity->weather()
                    ->windSpeed(),
                new Temperature($Activity->weather()->temperature()->value()),
                new Pace($Activity->duration(), $Activity->distance())
            );
        });
    }

    /**
     * @return HeatIndex
     */
    public function heatIndex()
    {
        return $this->object($this->HeatIndex, function (Activity\Entity $Activity): \Runalyze\Data\Weather\HeatIndex {
            return new HeatIndex(
                new \Runalyze\Activity\Temperature($Activity->weather()->temperature()->value()),
                $Activity->weather()
                    ->humidity()
            );
        });
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

    /**
     * @return \Runalyze\Calculation\JD\LegacyEffectiveVO2max
     */
    public function vo2max()
    {
        $self = $this;

        return $this->object($this->VO2max, function (Activity\Entity $Activity) use ($self): \Runalyze\Calculation\JD\LegacyEffectiveVO2max {
            return new LegacyEffectiveVO2max($self->usedVO2maxValue(), new LegacyEffectiveVO2maxCorrector());
        });
    }

    /**
     * Value of used VO2max (uncorrected)
     *
     * @return float
     */
    public function usedVO2maxValue()
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

    /**
     * @param string $leftString
     * @param string $rightString
     * @param string $empty
     */
    protected function formatTwoPartValue($leftString, $rightString, $empty = '-'): string
    {
        if ($empty == $leftString && $empty == $rightString) {
            return '';
        }

        return $leftString . '/' . $rightString;
    }
}
