<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Poster;

use Symfony\Component\Filesystem\Filesystem;

class Availability
{
    /**
     * @param string $RsvgPath absolute path of rsvg[-convert]
     * @param string $InkscapePath absolute path of inkscape
     * @param string $Python3Path absolute path of Python3
     */
    public function __construct(
        protected string $RsvgPath,
        protected string $InkscapePath,
        protected string $Python3Path
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->isPythonAvailable() && $this->isRsvgConverterAvailable();
    }

    protected function isInkscapeAvailable(): bool
    {
        return new Filesystem()->exists($this->InkscapePath);
    }

    protected function isRsvgConverterAvailable(): bool
    {
        return new Filesystem()->exists($this->RsvgPath);
    }

    protected function isPythonAvailable(): bool
    {
        return new Filesystem()->exists($this->Python3Path);
    }
}
