<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class PipeDelimitedArray extends Type
{
    public const string PIPE_ARRAY = 'pipe_array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        return implode('|', $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?array
    {
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return array_map(
            static fn (string $element): int|float|null => is_numeric($element) ? $element + 0 : null,
            explode('|', $value)
        );
    }
}
