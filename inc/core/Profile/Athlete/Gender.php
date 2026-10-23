<?php

declare(strict_types=1);

namespace Runalyze\Profile\Athlete;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Util\InterfaceChoosable;

class Gender extends AbstractEnum implements InterfaceChoosable
{
    public const int NONE = 0;

    public const int MALE = 1;

    public const int FEMALE = 2;

    /**
     * @param int $id id from internal enum
     */
    public static function stringFor(int $id): string
    {
        return match ($id) {
            self::NONE => __('not set'),
            self::MALE => __('male'),
            self::FEMALE => __('female'),
            default => throw new \InvalidArgumentException('Invalid gender id "' . $id . '".'),
        };
    }

    public static function getChoices(): array
    {
        return [
            self::stringFor(self::NONE) => self::NONE,
            self::stringFor(self::MALE) => self::MALE,
            self::stringFor(self::FEMALE) => self::FEMALE,
        ];
    }
}
