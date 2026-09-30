<?php

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\SmallIntType;

class TinyIntType extends SmallIntType
{
    /**
     * @var string
     */
    public const TINYINT = 'tinyint';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $declaration = parent::getSQLDeclaration($fieldDeclaration, $platform);

        if ($platform instanceof MySQLPlatform) {
            return str_replace('SMALLINT', 'TINYINT', $declaration);
        }

        return $declaration;
    }

    public function getName(): string
    {
        return self::TINYINT;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
