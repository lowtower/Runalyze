<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup;

use Runalyze\Bundle\CoreBundle\Entity\EquipmentType as EquipmentTypeEntity;
use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

final class QueryGroups extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    /**
     * @var string
     */
    public const MONTH = 'month';

    /**
     * @var string
     */
    public const YEAR = 'year';

    /**
     * @var string
     */
    public const SPORT = 'sport';

    /**
     * @var string
     */
    public const TYPE = 'type';

    /**
     * @var string
     */
    public const EQUIPMENT_TYPE = 'equipment';

    public static function getEnumForEquipmentType(EquipmentTypeEntity $equipmentType): string
    {
        return self::EQUIPMENT_TYPE . '_' . $equipmentType->getId();
    }

    public static function getGroup(string $enum): QueryGroupInterface
    {
        if (substr($enum, 0, strlen(self::EQUIPMENT_TYPE) + 1) === self::EQUIPMENT_TYPE . '_') {
            return new EquipmentType((int) substr($enum, strlen(self::EQUIPMENT_TYPE) + 1));
        }

        return self::get($enum);
    }
}
