<?php

declare(strict_types=1);

namespace Runalyze\Data;

use Runalyze\Profile\FitSdk\StrokeTypeProfile;

class Stroketype
{
    protected int $Identifier;

    public function __construct(int $identifier)
    {
        $this->set($identifier);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function set(int $identifier): void
    {
        if (! in_array($identifier, self::completeList(), true)) {
            throw new \InvalidArgumentException('Invalid stroke type identifier: ' . $identifier);
        }

        $this->Identifier = $identifier;
    }

    public static function completeList(): array
    {
        return StrokeTypeProfile::getEnum();
    }

    public function id(): int
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
        return $this->Identifier === StrokeTypeProfile::BREAK;
    }
}
