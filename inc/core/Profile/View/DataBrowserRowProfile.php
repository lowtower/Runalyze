<?php

declare(strict_types=1);

namespace Runalyze\Profile\View;

use InvalidArgumentException;
use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Util\InterfaceChoosable;

class DataBrowserRowProfile extends AbstractEnum implements InterfaceChoosable
{
    public const int COMPLETE_ROW = 0;

    public const int ONLY_ICON = 1;

    public const int INHERIT_FROM_PARENT = 2;

    /**
     * @throws InvalidArgumentException if $id does not match a known enum value
     */
    public static function stringFor(int $id): string
    {
        return match ($id) {
            self::COMPLETE_ROW => __('complete row'),
            self::ONLY_ICON => __('only icon'),
            self::INHERIT_FROM_PARENT => __('Inherit from sport'),
            default => throw new InvalidArgumentException('Invalid databrowser row id "' . $id . '".'),
        };
    }

    public static function getChoices(): array
    {
        return [
            self::stringFor(self::COMPLETE_ROW) => self::COMPLETE_ROW,
            self::stringFor(self::ONLY_ICON) => self::ONLY_ICON,
            self::stringFor(self::INHERIT_FROM_PARENT) => self::INHERIT_FROM_PARENT,
        ];
    }

    public static function getChoicesWithoutParent(): array
    {
        return [
            self::stringFor(self::COMPLETE_ROW) => self::COMPLETE_ROW,
            self::stringFor(self::ONLY_ICON) => self::ONLY_ICON,
        ];
    }
}
