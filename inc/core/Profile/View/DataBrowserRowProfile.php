<?php

declare(strict_types=1);

namespace Runalyze\Profile\View;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Util\InterfaceChoosable;

class DataBrowserRowProfile extends AbstractEnum implements InterfaceChoosable
{
    /**
     * @var int
     */
    const COMPLETE_ROW = 0;

    /**
     * @var int
     */
    const ONLY_ICON = 1;

    /**
     * @var int
     */
    const INHERIT_FROM_PARENT = 2;

    /**
     * @param int $id id from internal enum
     */
    static public function stringFor($id): string
    {
        switch ($id) {
            case self::COMPLETE_ROW:
                return __('complete row');
            case self::ONLY_ICON:
                return __('only icon');
            case self::INHERIT_FROM_PARENT:
                return __('Inherit from sport');
            default:
                throw new \InvalidArgumentException('Invalid databrowser row id "' . $id . '".');
        }
    }

    static public function getChoices(): array {
        return [
            self::stringFor(self::COMPLETE_ROW) => self::COMPLETE_ROW,
            self::stringFor(self::ONLY_ICON) => self::ONLY_ICON,
            self::stringFor(self::INHERIT_FROM_PARENT) => self::INHERIT_FROM_PARENT,
        ];
    }

    static public function getChoicesWithoutParent(): array {
        return [
            self::stringFor(self::COMPLETE_ROW) => self::COMPLETE_ROW,
            self::stringFor(self::ONLY_ICON) => self::ONLY_ICON,
        ];
    }
}
