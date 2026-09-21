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

    private static ?LapIntensity $INST_ACTIVE = null;

    private static ?LapIntensity $INST_REST = null;

    public function __construct(
        private string $Value
    ) {
    }

    public function getValue(): string
    {
        return $this->Value;
    }

    public function isActive(): bool
    {
        return $this->Value === self::ACTIVE;
    }

    public function isNotRest(): bool
    {
        return ! $this->isRest();
    }

    public function isRest(): bool
    {
        return $this->Value === self::REST;
    }

    public function isEqualTo(self $other): bool
    {
        return $this->Value === $other->getValue();
    }

    /**
     * Converts the value from the FIT file to an enum.
     * See com.garmin.fit.Intensity
     *
     * @return ?LapIntensity internal enum
     * @throws \InvalidArgumentException
     */
    public static function fromFitToEnum(float $fitValue): ?self
    {
        if ((int) $fitValue < 0 || (int) $fitValue > 6) {
            throw new \InvalidArgumentException(sprintf('Provided workout_step intensity %s is invalid.', $fitValue));
        }

        return match ((int) $fitValue) {
            0 => self::getInstanceActive(),
            1 => self::getInstanceRest(),
            2 => new self(self::WARMUP),
            3 => new self(self::COOLDOWN),
            4 => new self(self::RECOVERY),
            5 => new self(self::INTERVAL),
            6 => new self(self::OTHER),
        };
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
     * @throws \InvalidArgumentException
     */
    public static function fromValue(string $value): ?self
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
        return match ($this->Value) {
            '' => 'Active',
            'R' => 'Rest',
            'W' => 'Warmup',
            'C' => 'Cooldown',
            'Y' => 'Recovery',
            'I' => 'Interval',
            'O' => 'Other',
            default => 'Unknown',
        };
    }

    /**
     * Gets a ACTIVE.
     */
    public static function getInstanceActive(): self
    {
        self::$INST_ACTIVE ??= new self(self::ACTIVE);

        return self::$INST_ACTIVE;
    }

    /**
     * Gets a REST.
     */
    public static function getInstanceRest(): self
    {
        self::$INST_REST ??= new self(self::REST);

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
