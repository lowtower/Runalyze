<?php

declare(strict_types=1);

namespace Runalyze\Parser\Common;

interface FileTypeConverterInterface
{
    /**
     * @return string|string[]
     */
    public function getConvertibleFileExtension();

    /**
     * @return string|string[]
     */
    public function getConvertedFileName(string $inputFile);

    /**
     * @return string|string[] name of output file(s)
     */
    public function convertFile(string $inputFile);
}
