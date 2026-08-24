<?php

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\SmallIntType;

class TinyIntType extends SmallIntType
{
    /**
     * @var string
     */
    public const TINYINT = 'tinyint';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $declaration = parent::getSQLDeclaration($fieldDeclaration, $platform);

        if ($platform instanceof MySqlPlatform) {
            return str_replace('SMALLINT', 'TINYINT', $declaration);
        }

        return $declaration;
    }

    public function getName()
    {
        return self::TINYINT;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
