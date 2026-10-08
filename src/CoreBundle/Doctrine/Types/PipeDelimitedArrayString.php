<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class PipeDelimitedArrayString extends PipeDelimitedArray
{
    public const string PIPE_ARRAY = 'pipe_array_str';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        // #TSC the pipe array is also used with strings, so a | inside an element is replaced to keep the separator unambiguous
        $value = array_map(
            static fn (mixed $element): mixed => is_string($element) ? str_replace('|', ' ', $element) : $element,
            $value
        );

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

        // #TSC strings are kept as they are, only empty elements become null
        return array_map(
            static fn (string $element): ?string => $element !== '' ? $element : null,
            explode('|', $value)
        );
    }
}
