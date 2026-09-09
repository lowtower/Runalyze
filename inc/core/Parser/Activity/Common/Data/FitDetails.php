<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data;

class FitDetails
{
    /**
     * @var float|null [ml/kg/min]
     */
    public ?float $VO2maxEstimate = null;

    /**
     * @var int|null [min]
     */
    public ?int $RecoveryTime = null;

    public ?int $HrvAnalysis = null;

    /**
     * @var int|null [0 .. 7] (1=Recovery, 2=Base,3=Tempo, 4=Threshold, 5=VO2 Max, 6=Anaerobic, 7=Sprint)
     */
    public ?int $TrainingEffectBenefit = null;

    /**
     * @var float|null [0.0 .. 5.0]
     */
    public ?float $TrainingEffect = null;

    /**
     * @var float|null [0.0 .. 5.0]
     */
    public ?float $AnaerobicTrainingEffect = null; // #TSC: new anaerob

    public ?int $PerformanceCondition = null;

    public ?int $PerformanceConditionEnd = null;

    public ?int $LactateThresholdHR = null;

    /**
     * @var int|null [m/s]
     */
    public ?int $LactateThresholdPace = null;

    public ?int $TotalAscent = null;

    public ?int $TotalDescent = null;

    public ?int $SelfEvaluationFeeling = null;

    public ?int $SelfEvaluationPerceivedEffort = null;

    public ?int $LoadPeak = null;

    /**
     * @var int|null [s]
     */
    public ?int $RunTime = null;

    /**
     * @var int|null [s]
     */
    public ?int $WalkTime = null;

    /**
     * @var int|null [s]
     */
    public ?int $StandTime = null;

    /**
     * @var array|null [bpm] #TSC
     */
    public ?array $SecondsInHrZones = null;

    /**
     * @var int|null [ml]
     */
    public ?int $SweatLoss = null;
}
