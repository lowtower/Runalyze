<?php

/**
 * This file contains class::SelfEvaluationPerceivedEffort
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for LapIntensity which represents the intensitiy of one lap.
 * In normal cases only ACTIVE exists. In swim cases or manual edit activities can have a REST.
 * In cases of Garmins trainings other cases are possible.
 *
 * The const's are also used in lib/runalyze.lib.activity.form.js, so if you change it, adapt the javascript.
 * #TSC
 *
 * @package Runalyze\Activity
 */
class LapIntensity extends AbstractEnum
{
    /**
     * For compatibility reasons active has an empty value.
     * @var string
     */
    private const ACTIVE = '';

    /**
     * @var string
     */
    private const REST = 'R';

    /**
     * @var string
     */
    private const WARMUP = 'W';

    /**
     * @var string
     */
    private const COOLDOWN = 'C';

    /**
     * @var string
     */
    private const RECOVERY = 'Y';

    /**
     * @var string
     */
    private const INTERVAL = 'I';

    /**
     * @var string
     */
    private const OTHER = 'O';

    /**
     * @var string
     */
    private $Value;

    /**
     * @var LapIntensity
     */
    private static $INST_ACTIVE;

    /**
     * @var LapIntensity
     */
    private static $INST_REST;

    public function __construct(string $value)
    {
        $this->Value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->Value;
    }

    public function isActive(): bool
    {
        return $this->Value == self::ACTIVE;
    }

    public function isNotRest(): bool
    {
        return ! $this->isRest();
    }

    public function isRest(): bool
    {
        return $this->Value == self::REST;
    }

    public function isEqualTo(self $other): bool
    {
        return $this->Value == $other->getValue();
    }

    /**
     * Converts the value from the FIT file to an enum.
     * See com.garmin.fit.Intensity
     *
     * @param float $fitValue fitValue
     * @return int internal enum
     * @throws \InvalidArgumentException
     */
    public static function fromFitToEnum($fitValue)
    {
        if (! is_numeric($fitValue) || (int) $fitValue < 0 || (int) $fitValue > 6) {
            throw new \InvalidArgumentException(sprintf('Provided workout_step intensity %s is invalid.', $fitValue));
        }

        switch ((int) $fitValue) {
            case 0:
                return self::getInstanceActive();
            case 1:
                return self::getInstanceRest();
            case 2:
                return new self(self::WARMUP);
            case 3:
                return new self(self::COOLDOWN);
            case 4:
                return new self(self::RECOVERY);
            case 5:
                return new self(self::INTERVAL);
            case 6:
                return new self(self::OTHER);
            default:
                return null;
        }
    }

    /**
     * Get all LapIntensity.
     */
    public static function getAll(): array
    {
        $r = [];
        for ($i = 0; $i <= 6; $i++) {
            $r[] = self::fromFitToEnum($i);
        }

        return $r;
    }

    /**
     * Converts the one chare value to an enum.
     *
     * @param string $value
     * @return LapIntensity|null
     * @throws \InvalidArgumentException
     */
    public static function fromValue($value)
    {
        if (self::isValidValue($value)) {
            return new self($value);
        }

        return null;
    }

    /**
     * @return string a label text
     */
    public function getLabel(): string
    {
        switch ($this->Value) {
            case '':
                return 'Active';
            case 'R':
                return 'Rest';
            case 'W':
                return 'Warmup';
            case 'C':
                return 'Cooldown';
            case 'Y':
                return 'Recovery';
            case 'I':
                return 'Interval';
            case 'O':
                return 'Other';
            default:
                return 'Unknown';
        }
    }

    /**
     * Gets a ACTIVE.
     *
     * @return LapIntensity
     */
    public static function getInstanceActive()
    {
        if (self::$INST_ACTIVE === null) {
            self::$INST_ACTIVE = new self(self::ACTIVE);
        }

        return self::$INST_ACTIVE;
    }

    /**
     * Gets a REST.
     *
     * @return LapIntensity
     */
    public static function getInstanceRest()
    {
        if (self::$INST_REST === null) {
            self::$INST_REST = new self(self::REST);
        }

        return self::$INST_REST;
    }

    /**
     * Gets a RECOVERY.
     */
    public static function getInstanceRecovery(): self
    {
        return new self(self::RECOVERY);
    }
}
