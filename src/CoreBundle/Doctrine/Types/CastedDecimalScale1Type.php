<?php

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;

class CastedDecimalScale1Type extends DecimalType
{
    /**
     * @var string
     */
    public const CASTED_DECIMAL_1 = 'casted_decimal_1';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return ($value === null) ? null : (float) $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($value === null) ? null : sprintf('%.1f', $value);
    }

    public function getName()
    {
        return self::CASTED_DECIMAL_1;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
