<?php

declare(strict_types=1);

namespace Runalyze\Profile\Sport\Settings;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Util\InterfaceChoosable;

class SportRelevanceProfile extends AbstractEnum implements InterfaceChoosable
{
    public const int ALTERNATIVE_SPORT = 0;

    public const int MAIN_SPORT = 1;

    /**
     * @param int $id id from internal enum
     */
    public static function stringFor($id): string
    {
        return match ($id) {
            self::ALTERNATIVE_SPORT => __('Alternative sport'),
            self::MAIN_SPORT => __('Main Sport'),
            default => throw new \InvalidArgumentException('Invalid sport relevance id "' . $id . '".'),
        };
    }

    public static function getChoices(): array
    {
        return [
            self::stringFor(self::ALTERNATIVE_SPORT) => self::ALTERNATIVE_SPORT,
            self::stringFor(self::MAIN_SPORT) => self::MAIN_SPORT,
        ];
    }
}
