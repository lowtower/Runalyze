<?php

declare(strict_types=1);

namespace Runalyze\Profile\View;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Util\InterfaceChoosable;

class DatasetPrivacyProfile extends AbstractEnum implements InterfaceChoosable
{
    /**
     * @var int
     */
    public const PRIVATE_KEY = 0;

    /**
     * @var int
     */
    public const PUBLIC_KEY = 1;

    /**
     * @param int $id id from internal enum
     */
    public static function stringFor($id): string
    {
        switch ($id) {
            case self::PRIVATE_KEY:
                return __('private');
            case self::PUBLIC_KEY:
                return __('public');
            default:
                throw new \InvalidArgumentException('Invalid databrowser row id "' . $id . '".');
        }
    }

    public static function getChoices(): array
    {
        return [
            self::stringFor(self::PRIVATE_KEY) => self::PRIVATE_KEY,
            self::stringFor(self::PUBLIC_KEY) => self::PUBLIC_KEY,
        ];
    }
}
