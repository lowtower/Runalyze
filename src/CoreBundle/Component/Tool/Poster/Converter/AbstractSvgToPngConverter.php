<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter;

abstract class AbstractSvgToPngConverter
{
    protected array $Parameter = [];

    protected string $Command;

    /**
     * @param int|string $height [px]
     */
    abstract public function setHeight($height);

    /**
     * @param int|string $width [px]
     */
    abstract public function setWidth($width);

    /**
     * @param string $source absolute path to source file
     * @param string $target absolute path to target file
     * @return int exit code
     */
    abstract public function callConverter(string $source, string $target): int;
}
