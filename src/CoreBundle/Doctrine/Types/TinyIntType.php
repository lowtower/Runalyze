<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\SmallIntType;

class TinyIntType extends SmallIntType
{
    public const string TINYINT = 'tinyint';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $declaration = parent::getSQLDeclaration($column, $platform);

        if ($platform instanceof AbstractMySQLPlatform) {
            return str_replace('SMALLINT', 'TINYINT', $declaration);
        }

        return $declaration;
    }
}
