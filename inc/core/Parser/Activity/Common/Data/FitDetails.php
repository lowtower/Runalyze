<?php

namespace Runalyze\Parser\Activity\Common\Data;

class FitDetails
{
    /** @var float|null [ml/kg/min] */
    public $VO2maxEstimate;

    /** @var int|null [min] */
    public $RecoveryTime;

    /** @var int|null */
    public $HrvAnalysis;

    /** @var int|null [0 .. 7] (1=Recovery, 2=Base,3=Tempo, 4=Threshold, 5=VO2 Max, 6=Anaerobic, 7=Sprint) */
    public $TrainingEffectBenefit;

    /** @var float|null [0.0 .. 5.0] */
    public $TrainingEffect;

    /** @var float|null [0.0 .. 5.0] */
    public $AnaerobicTrainingEffect; // #TSC: new anaerob

    /** @var int|null */
    public $PerformanceCondition;

    /** @var int|null */
    public $PerformanceConditionEnd;

    /** @var int|null */
    public $LactateThresholdHR;

    /** @var int|null [m/s] */
    public $LactateThresholdPace;

    /** @var int|null */
    public $TotalAscent;

    /** @var int|null */
    public $TotalDescent;

    /** @var int|null */
    public $SelfEvaluationFeeling;

    /** @var int|null */
    public $SelfEvaluationPerceivedEffort;

    /** @var int|null */
    public $LoadPeak;

    /** @var int|null [s] */
    public $RunTime;

    /** @var int|null [s] */
    public $WalkTime;

    /** @var int|null [s] */
    public $StandTime;

    /** @var array|null [bpm] #TSC */
    public $SecondsInHrZones;

    /** @var int|null [ml] */
    public $SweatLoss;
}
