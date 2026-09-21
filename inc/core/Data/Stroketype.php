<?php

namespace Runalyze\Data;

use Runalyze\Profile\FitSdk\StrokeTypeProfile;

class Stroketype
{
    /**
     * @var int
     */
    protected $Identifier;

    /**
     * @param int $identifier a class constant
     */
    public function __construct($identifier)
    {
        $this->set($identifier);
    }

    /**
     * @param int $identifier a class constant
     */
    public function set($identifier): void
    {
        if (in_array($identifier, self::completeList())) {
            $this->Identifier = $identifier;
        }
    }

    public static function completeList(): array
    {
        return StrokeTypeProfile::getEnum();
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->Identifier;
    }

    public function string(): string
    {
        return match ($this->Identifier) {
            StrokeTypeProfile::FREESTYLE => __('Freestyle'),
            StrokeTypeProfile::BACK => __('Backstroke'),
            StrokeTypeProfile::BREAST => __('Breaststroke'),
            StrokeTypeProfile::BUTTERFLY => __('Butterfly'),
            StrokeTypeProfile::DRILL => __('Drill'),
            StrokeTypeProfile::MIXED => __('Mixed'),
            StrokeTypeProfile::BREAK => __('Break'),
            default => '',
        };
    }

    public function shortString(): string
    {
        return match ($this->Identifier) {
            StrokeTypeProfile::FREESTYLE => __('Freestyle'),
            StrokeTypeProfile::BACK => __('Back'),
            StrokeTypeProfile::BREAST => __('Breast'),
            StrokeTypeProfile::BUTTERFLY => __('Butterfly'),
            StrokeTypeProfile::DRILL => __('Drill'),
            StrokeTypeProfile::MIXED => __('Mixed'),
            StrokeTypeProfile::BREAK => __('Break'),
            default => '',
        };
    }

    public function isBreak(): bool
    {
        return $this->Identifier == StrokeTypeProfile::BREAK;
    }
}
