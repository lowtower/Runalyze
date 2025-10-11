<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Activity\LapIntensity;

/**
 * Parser for workout_step's of a FIT file.
 * "workout_step" holds values to a detailed type of a lap (like warmup, recovery ...) and "custom target zones" for preconfigured trainings.
 * laps in the FIT file has a reference to a "workout_step".
 * #TSC
 */
class FitWorkoutStep {
    const ADD_TARGET_SPEED = 'FIT target speed';
    const ADD_TARGET_HR = 'FIT target HR';
    const ADD_TARGET_CADENCE = 'FIT target cadence';
    const ADD_TARGET_POWER = 'FIT target power';

    /** @var array array of LapIntensity|null can contain null values */
    protected $intensitys = [];
    /** @var array low/high of a target */
    protected $targets = [];

    protected $hasTarget = false;

    /**
     * has intensity data.
     * @param int $index
     * @return LapIntensity|null
     */
    public function getIntensity($index): ?LapIntensity {
        return $this->intensitys[$index] ?? null;
    }

    /**
     * get array for split-additionals.
     * @param int $index
     * @return array|null
     */
    public function getSplitAdditionals($index): ?array {
        return !empty($this->targets[$index]) ? $this->targets[$index] : null;
    }

    /**
     * Collect workout-step information.
     *
     * @param array $values
     */
    public function collectWorkoutstep(&$values) {
        // intensity (like warmup, cooldown ...)
        if (isset($values['intensity'][0])) {
            $this->intensitys[] = LapIntensity::fromFitToEnum($values['intensity'][0]);
        } else {
            // the workout steps of type "repeat_steps" has no intensity, but are needed in the array for correct indexing
            $this->intensitys[] = null;
        }

        // custom targets (pace or heart-rate) with low and high bounds
        $target = [];
        if (isset($values['custom_target_speed_low'][0])) {
            $target[self::ADD_TARGET_SPEED . ' low'] = round($values['custom_target_speed_low'][0] / 1000, 3); // unit m/s
            $this->hasTarget = true;
        } elseif (isset($values['custom_target_heart_rate_low'][0])) {
            $target[self::ADD_TARGET_HR . ' low'] = (int)$values['custom_target_heart_rate_low'][0];
            $this->hasTarget = true;
        } elseif (isset($values['custom_target_cadence_low'][0])) {
            $target[self::ADD_TARGET_CADENCE . ' low'] = (int)$values['custom_target_cadence_low'][0];
            $this->hasTarget = true;
        } elseif (isset($values['custom_target_power_low'][0])) {
            $target[self::ADD_TARGET_POWER . ' low'] = (int)$values['custom_target_power_low'][0];
            $this->hasTarget = true;
        }

        if (isset($values['custom_target_speed_high'][0])) {
            $target[self::ADD_TARGET_SPEED . ' high'] = round($values['custom_target_speed_high'][0] / 1000, 3); // unit m/s
            $this->hasTarget = true;
        } elseif (isset($values['custom_target_heart_rate_high'][0])) {
            $target[self::ADD_TARGET_HR . ' high'] = (int)$values['custom_target_heart_rate_high'][0];
            $this->hasTarget = true;
        } elseif (isset($values['custom_target_cadence_high'][0])) {
            $target[self::ADD_TARGET_CADENCE . ' high'] = (int)$values['custom_target_cadence_high'][0];
            $this->hasTarget = true;
        } elseif (isset($values['custom_target_power_high'][0])) {
            $target[self::ADD_TARGET_POWER . ' high'] = (int)$values['custom_target_power_high'][0];
            $this->hasTarget = true;
        }
        $this->targets[] = $target;
    }

    /**
     * @return bool
     */
    public function hasCustomTarget() {
        return $this->hasTarget;
    }
}
