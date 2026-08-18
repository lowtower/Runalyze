<?php

namespace Runalyze\Parser\Activity\Converter;

class KmzConverter extends ZipConverter
{
    public function __construct()
    {
        parent::__construct(['kml']);
    }

    public function getConvertibleFileExtension(): string
    {
        return 'kmz';
    }

    protected function isFileExtensionAllowed($extension): bool
    {
        return 'kml' == mb_strtolower($extension);
    }
}
