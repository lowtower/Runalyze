<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool;

final class UploadFilename
{
    public static function fromClientOriginalName(string $originalName): ?string
    {
        $fileName = basename(str_replace('\\', '/', $originalName));

        if (
            $fileName === ''
            || in_array($fileName, ['.', '..'], true)
            || preg_match('/[\x00-\x1F\x7F]/', $fileName) === 1
        ) {
            return null;
        }

        return $fileName;
    }
}
